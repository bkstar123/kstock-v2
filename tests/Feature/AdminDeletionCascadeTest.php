<?php

namespace Tests\Feature;

use App\Models\AnalysisReport;
use App\Models\DirectoryEntry;
use App\Models\FinancialStatement;
use App\Models\FinancialStatementEntry;
use App\Models\Symbol;
use App\Models\Watchlist;
use App\Services\Contracts\Symbols as SymbolsInterface;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSymbols;
use Tests\TestCase;

/**
 * Deleting an admin must take their orphaned `symbols` rows with them.
 *
 * Driven at the model level ($admin->delete(), Admin::destroy([...])) because that
 * is exactly what AdminController::destroy/massiveDestroy do, and it avoids
 * dragging in the admin-panel permission plumbing.
 */
class AdminDeletionCascadeTest extends TestCase
{
    use RefreshDatabase;

    private int $adminSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SymbolsInterface::class, new FakeSymbols());
    }

    private function admin(): Admin
    {
        $n = ++$this->adminSeq;
        return Admin::create([
            'name' => "Cas{$n}", 'username' => "cas{$n}", 'email' => "cas{$n}@example.com",
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    public function test_deleting_an_admin_purges_their_orphaned_symbols()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);

        $a->delete();

        // Doubles as proof that SQLite honours ON DELETE CASCADE under
        // RefreshDatabase — the whole deleted()-event design depends on it.
        $this->assertDatabaseCount('directory_entries', 0);
        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
    }

    public function test_deleting_an_admin_spares_symbols_another_admin_still_references()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        $b = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        DirectoryEntry::create(['admin_id' => $b->id, 'symbol_code' => 'FPT']);

        $a->delete();

        $this->assertDatabaseHas('directory_entries', ['admin_id' => $b->id, 'symbol_code' => 'FPT']);
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_deleting_an_admin_purges_a_symbol_they_only_watchlisted()
    {
        // Proves the candidate set spans all three referencing tables, not just
        // directory_entries: this ticker was never in anyone's directory.
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        Watchlist::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);

        $a->delete();

        $this->assertDatabaseCount('watchlists', 0);
        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
    }

    public function test_deleting_an_admin_purges_a_symbol_referenced_only_by_their_statements()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        $fs = FinancialStatement::create([
            'symbol' => 'FPT', 'year' => 2024, 'quarter' => 0, 'last_pulled_by_admin_id' => $a->id,
        ]);
        FinancialStatementEntry::create(['admin_id' => $a->id, 'financial_statement_id' => $fs->id]);

        $a->delete();

        $this->assertDatabaseCount('financial_statement_entries', 0);
        $this->assertDatabaseCount('financial_statements', 0);
        // THE ORDERING ASSERTION. Statements are what SymbolCatalog::isReferenced()
        // counts, so if deleted() released symbols BEFORE purging statements, FPT
        // would still look referenced by a row about to vanish, and would survive.
        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
    }

    public function test_deleting_an_admin_spares_a_statement_another_admin_still_holds()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        $b = $this->admin();
        $fs = FinancialStatement::create([
            'symbol' => 'FPT', 'year' => 2024, 'quarter' => 0, 'last_pulled_by_admin_id' => $b->id,
        ]);
        FinancialStatementEntry::create(['admin_id' => $a->id, 'financial_statement_id' => $fs->id]);
        FinancialStatementEntry::create(['admin_id' => $b->id, 'financial_statement_id' => $fs->id]);
        AnalysisReport::create(['financial_statement_id' => $fs->id, 'content' => '[]']);

        $a->delete();

        $this->assertDatabaseHas('financial_statements', ['id' => $fs->id]);
        $this->assertDatabaseHas('analysis_reports', ['financial_statement_id' => $fs->id]);
        $this->assertDatabaseCount('financial_statement_entries', 1);
        // Still referenced by a surviving statement, so the ticker stays too.
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_deleting_an_admin_spares_symbols_they_never_referenced()
    {
        // Guards against over-aggressive GC: only the deleted admin's footprint is
        // a candidate, not the whole catalog.
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        Symbol::create(['code' => 'VNM', 'name' => 'CTCP Sua Viet Nam', 'exchange' => 'HSX']);
        $a = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);

        $a->delete();

        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
        $this->assertDatabaseHas('symbols', ['code' => 'VNM']);
    }

    public function test_massive_destroy_purges_after_the_last_referencing_admin_goes()
    {
        // Admin::destroy() loads each model and calls delete(), so the observer
        // fires per admin: A's deleted() spares FPT (B still holds it), B's purges.
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        $b = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        DirectoryEntry::create(['admin_id' => $b->id, 'symbol_code' => 'FPT']);

        Admin::destroy([$a->id, $b->id]);

        $this->assertDatabaseCount('directory_entries', 0);
        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
    }

    public function test_deleting_an_admin_with_no_tickers_is_a_clean_no_op()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();

        $a->delete();

        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
        $this->assertDatabaseMissing('admins', ['id' => $a->id]);
    }
}
