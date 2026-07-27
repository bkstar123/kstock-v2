<?php
/**
 * AdminSymbolObserver - garbage-collect a deleted admin's ticker and statement
 * footprint.
 *
 * `directory_entries`, `watchlists` and `financial_statement_entries` all carry an
 * `admin_id` FK with ON DELETE CASCADE, so the database erases the admin's own rows
 * for free. What it cannot do is drop the two SHARED, reference-counted resources
 * those rows were the last reference to:
 *
 *   - `symbols` master rows (no owner column, no FK into it, by design)
 *   - `financial_statements` rows and their four child blobs (ownership lives in
 *     the pivot, so the row itself survives the admin's deletion)
 *
 * Registered in AppServiceProvider::boot(), alongside the admin-panel package's own
 * AdminObserver (which handles roles and the profile). Laravel supports several
 * observers per model — but note it gives NO ordering guarantee between them, which
 * is why statement GC lives here rather than in a sibling observer (see deleted()).
 */
namespace App\Observers;

use App\Models\DirectoryEntry;
use App\Models\FinancialStatement;
use App\Models\FinancialStatementEntry;
use App\Models\Watchlist;
use App\Services\StatementLibrary;
use App\Services\SymbolCatalog;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Throwable;

class AdminSymbolObserver
{
    /**
     * Ticker codes collected in deleting(), consumed in deleted(), keyed by admin id.
     *
     * STATIC on purpose. Model::observe() registers a "Class@method" string
     * listener, and Illuminate\Events\Dispatcher::createClassCallable resolves the
     * observer out of the container on EVERY dispatch — so the deleting() instance
     * and the deleted() instance are different objects. An instance property would
     * silently lose the codes.
     *
     * @var array<int, array<int, string>>
     */
    private static array $pending = [];

    /**
     * Statement ids the admin held, snapshotted in deleting(), consumed in deleted().
     * STATIC for the same reason $pending is — see the note on that property.
     *
     * @var array<int, array<int, int>>
     */
    private static array $pendingStatements = [];

    protected SymbolCatalog $catalog;

    protected StatementLibrary $library;

    public function __construct(SymbolCatalog $catalog, StatementLibrary $library)
    {
        $this->catalog = $catalog;
        $this->library = $library;
    }

    /**
     * Snapshot every ticker this admin references, BEFORE the delete runs.
     *
     * This half must run in `deleting`: once the admin row is gone the ON DELETE
     * CASCADE has already wiped the three referencing tables, and there is no way
     * left to tell which tickers the admin had.
     *
     * All three tables are collected, not just the directory: the cascade removes
     * all three, so any of them can hold the last reference. An admin who only ever
     * watchlisted a ticker (never added it to their directory) still leaves it
     * fully orphaned on deletion.
     */
    public function deleting(Admin $admin): void
    {
        try {
            // Statements are shared now, so their ownership is the pivot rather than
            // an admin_id column — and the pivot is exactly what the FK cascade is
            // about to erase, so it must be read here.
            $statementIds = FinancialStatementEntry::where('admin_id', $admin->id)
                ->pluck('financial_statement_id')
                ->all();
            self::$pendingStatements[$admin->id] = $statementIds;

            self::$pending[$admin->id] = collect()
                ->merge(DirectoryEntry::where('admin_id', $admin->id)->pluck('symbol_code'))
                ->merge(Watchlist::where('admin_id', $admin->id)->pluck('symbol_code'))
                ->merge(FinancialStatement::whereIn('id', $statementIds)->pluck('symbol'))
                ->map(fn ($code) => strtoupper(trim((string) $code)))
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (Throwable $e) {
            // Catalog housekeeping must never be able to block an account deletion.
            self::$pendingStatements[$admin->id] = [];
            self::$pending[$admin->id] = [];
            report($e);
        }
    }

    /**
     * Reference-count each candidate now that the admin — and, via the FK cascade,
     * all of their directory/watchlist/statement rows — is really gone.
     *
     * This half must run in `deleted`: at `deleting` time the admin's own rows are
     * still present, so every candidate would look referenced and nothing would be
     * collected. Model::delete() runs performDeleteOnModel() (and therefore the FK
     * cascade) before firing `deleted`, so the counts here are accurate.
     */
    public function deleted(Admin $admin): void
    {
        $statementIds = self::$pendingStatements[$admin->id] ?? [];
        $codes        = self::$pending[$admin->id] ?? [];
        unset(self::$pendingStatements[$admin->id], self::$pending[$admin->id]);

        // ORDER IS LOAD-BEARING AND MUST NOT BE SWAPPED.
        //
        // SymbolCatalog::isReferenced() counts `financial_statements` globally. The
        // admin's pivot rows are already gone (FK cascade), but the statements they
        // were the last holder of are still here until releaseMany() runs. Release
        // symbols first and every ticker looks referenced by a row that is about to
        // vanish — so no symbol is ever collected, silently, forever.
        //
        // Statements first, symbols second. Pinned by
        // tests/Feature/AdminDeletionCascadeTest::test_deleting_an_admin_purges_a_symbol_referenced_only_by_their_statements
        try {
            if (!empty($statementIds)) {
                $this->library->releaseMany($statementIds);
            }
        } catch (Throwable $e) {
            report($e);
        }

        if (empty($codes)) {
            return;
        }

        try {
            $this->catalog->releaseMany($codes);
        } catch (Throwable $e) {
            // An orphaned `symbols` row is unreachable from the directory listing
            // (index() filters through directory_entries) and otherwise harmless.
            // Never fail an account deletion over it.
            report($e);
        }
    }
}
