<?php
/**
 * SymbolCatalog - keeps the local `symbols` master table in sync with the external API.
 *
 * The external API (with the current token) exposes no full-universe list endpoint, so
 * the catalog is populated on demand: whenever a ticker is viewed, watchlisted,
 * pulled, or passed to `symbols:sync`, its master row is fetched and upserted.
 */
namespace App\Services;

use App\Models\Symbol;
use App\Services\Contracts\Symbols as SymbolsInterface;

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
}
