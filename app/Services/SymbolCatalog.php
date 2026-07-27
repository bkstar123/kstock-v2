<?php
/**
 * SymbolCatalog - keeps the local `symbols` master table in sync with the external API.
 *
 * The external API (with the current token) exposes no full-universe list endpoint, so
 * the catalog is populated on demand: whenever a ticker is viewed, watchlisted,
 * pulled, or passed to `symbols:sync`, its master row is fetched and upserted.
 */
namespace App\Services;

use App\Models\DirectoryEntry;
use App\Models\FinancialStatement;
use App\Models\Symbol;
use App\Models\Watchlist;
use App\Services\Contracts\Symbols as SymbolsInterface;
use Illuminate\Support\Facades\DB;

class SymbolCatalog
{
    /**
     * @var SymbolsInterface
     */
    protected $api;

    public function __construct(SymbolsInterface $api)
    {
        $this->api = $api;
    }

    /**
     * Return the local Symbol row if present, otherwise fetch it from the API
     * and store it. Returns null for an unknown/invalid ticker.
     */
    public function remember(string $code): ?Symbol
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        $existing = Symbol::where('code', $code)->first();
        if ($existing) {
            return $existing;
        }

        return $this->sync($code);
    }

    /**
     * Fetch fresh master data from the API and upsert it. Returns the Symbol,
     * or null if the API has no such ticker.
     */
    public function sync(string $code): ?Symbol
    {
        $code = strtoupper(trim($code));
        $data = $this->api->getSymbol($code);
        if (empty($data) || empty($data['symbol'])) {
            return null;
        }

        return Symbol::updateOrCreate(
            ['code' => strtoupper($data['symbol'])],
            [
                'name'          => $data['name'] ?? null,
                'exchange'      => $data['exchange'] ?? null,
                'company_type'  => $data['type'] ?? null,
                'industry_code' => $data['industryCode'] ?? null,
                'icb_code'      => $data['icbCode'] ?? null,
                'is_active'     => (bool) ($data['isListing'] ?? true),
            ]
        );
    }

    /**
     * Reference-count a ticker and drop its master `symbols` row when nothing
     * refers to it any more. Returns true only if a row was actually deleted.
     *
     * `symbols` is a SHARED table (one row per ticker, no owner column), so a row
     * may only go when no admin still references it through any of the three
     * referencing tables. In the common single-admin case the ticker genuinely
     * disappears; it survives only when deleting it would blank out another
     * admin's watchlist or statement listing.
     */
    public function release(string $code): bool
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return false;
        }

        return DB::transaction(function () use ($code) {
            if ($this->isReferenced($code)) {
                return false;
            }

            // Plain equality is fine on this side: every write path upper-cases
            // `symbols.code` via the model mutator, and a miss merely leaves an
            // orphan row that the directory listing cannot reach anyway.
            return Symbol::where('code', $code)->delete() > 0;
        });
    }

    /**
     * Release several tickers at once. Returns how many master rows were purged.
     *
     * @param  iterable<string>  $codes
     */
    public function releaseMany(iterable $codes): int
    {
        return DB::transaction(function () use ($codes) {
            $purged = 0;
            foreach ($codes as $code) {
                if ($this->release($code)) {
                    $purged++;
                }
            }

            return $purged;
        });
    }

    /**
     * Is this ticker still referenced by any admin, anywhere?
     *
     * Comparisons go through UPPER() on purpose. All four models upper-case their
     * code via a mutator, but raw writes bypass that (the directory_entries
     * migration backfills with DB::table()->insert()), and SQLite — the test and
     * local-dev driver — compares strings case-SENSITIVELY where MySQL's
     * utf8mb4_unicode_ci does not. Missing a lower-cased reference here would
     * delete a row that is still in use, so this side must fail safe.
     */
    public function isReferenced(string $code): bool
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return false;
        }

        return DirectoryEntry::whereRaw('UPPER(symbol_code) = ?', [$code])->exists()
            || Watchlist::whereRaw('UPPER(symbol_code) = ?', [$code])->exists()
            || FinancialStatement::whereRaw('UPPER(symbol) = ?', [$code])->exists();
    }
}
