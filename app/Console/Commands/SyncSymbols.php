<?php
/**
 * symbols:sync - populate/refresh the local symbols master table.
 *
 * The external API exposes no full-universe endpoint, so we refresh the tickers we
 * already know about: any codes passed as arguments, every symbol that has a
 * pulled FinancialStatement, and every watchlisted symbol. Each is resolved
 * via SymbolCatalog (GET /symbols/{code}).
 */
namespace App\Console\Commands;

use App\Models\FinancialStatement;
use App\Models\Watchlist;
use App\Services\SymbolCatalog;
use Illuminate\Console\Command;

class SyncSymbols extends Command
{
    protected $signature = 'symbols:sync {codes?* : Extra ticker codes to import/refresh}';

    protected $description = 'Refresh the local symbols master table from the external API';

    public function handle(SymbolCatalog $catalog): int
    {
        $codes = collect($this->argument('codes'))
            ->merge(FinancialStatement::query()->distinct()->pluck('symbol'))
            ->merge(Watchlist::query()->distinct()->pluck('symbol_code'))
            ->map(fn ($c) => strtoupper(trim((string) $c)))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            $this->warn('No symbols to sync. Pass ticker codes, e.g. `php artisan symbols:sync FPT VNM HPG`.');
            return self::SUCCESS;
        }

        $synced = 0;
        foreach ($codes as $code) {
            $symbol = $catalog->sync($code);
            if ($symbol) {
                $synced++;
                $this->line("  <info>✓</info> {$symbol->code}  {$symbol->name}");
            } else {
                $this->line("  <comment>✗</comment> {$code} (not found)");
            }
        }

        $this->info("Synced {$synced}/{$codes->count()} symbol(s).");

        return self::SUCCESS;
    }
}
