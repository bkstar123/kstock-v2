<?php

namespace Tests\Feature;

use App\Models\DirectoryEntry;
use App\Models\FinancialStatement;
use App\Models\Watchlist;
use App\Services\Contracts\Symbols as SymbolsInterface;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSymbols;
use Tests\TestCase;

class SyncSymbolsTest extends TestCase
{
    use RefreshDatabase;

    private int $adminSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        // The container instance is shared with the artisan call.
        $this->app->instance(SymbolsInterface::class, new FakeSymbols());
    }

    private function admin(): Admin
    {
        $n = ++$this->adminSeq;
        return Admin::create([
            'name' => "Syn{$n}", 'username' => "syn{$n}", 'email' => "syn{$n}@example.com",
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    public function test_sync_refreshes_directory_only_symbols()
    {
        // Regression: directory_entries was missing from the sync union, so a
        // symbol only ever added to someone's directory was never refreshed and
        // its name/exchange/is_active went stale forever.
        DirectoryEntry::create(['admin_id' => $this->admin()->id, 'symbol_code' => 'FPT']);

        $this->artisan('symbols:sync')->assertExitCode(0);

        $this->assertDatabaseHas('symbols', ['code' => 'FPT', 'name' => 'CTCP FPT']);
    }

    public function test_sync_still_covers_watchlists_and_statements()
    {
        $a = $this->admin();
        Watchlist::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        FinancialStatement::create([
            'symbol' => 'VNM', 'year' => 2024, 'quarter' => 0, 'last_pulled_by_admin_id' => $a->id,
        ]);

        $this->artisan('symbols:sync')->assertExitCode(0);

        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
        $this->assertDatabaseHas('symbols', ['code' => 'VNM']);
    }

    public function test_sync_does_not_resurrect_an_unreferenced_symbol()
    {
        // The sync union is exactly the three tables isReferenced() checks, so a
        // ticker release() legitimately purged can never come back through the
        // scheduled run.
        Watchlist::create(['admin_id' => $this->admin()->id, 'symbol_code' => 'VNM']);

        $this->artisan('symbols:sync')->assertExitCode(0);

        $this->assertDatabaseHas('symbols', ['code' => 'VNM']);
        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
    }

    public function test_sync_with_no_known_symbols_is_a_clean_no_op()
    {
        $this->artisan('symbols:sync')
            ->expectsOutputToContain('No symbols to sync')
            ->assertExitCode(0);

        $this->assertDatabaseCount('symbols', 0);
    }
}
