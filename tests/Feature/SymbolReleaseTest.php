<?php

namespace Tests\Feature;

use App\Models\DirectoryEntry;
use App\Models\FinancialStatement;
use App\Models\Symbol;
use App\Models\Watchlist;
use App\Services\Contracts\Symbols as SymbolsInterface;
use App\Services\SymbolCatalog;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeSymbols;
use Tests\TestCase;

/**
 * SymbolCatalog::release() — reference-counted deletion of the shared master row.
 *
 * `symbols` has no owner column, so a row may only be dropped when nothing in
 * `directory_entries`, `watchlists` or `financial_statements` still points at it.
 */
class SymbolReleaseTest extends TestCase
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
            'name' => "Rel{$n}", 'username' => "rel{$n}", 'email' => "rel{$n}@example.com",
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    private function catalog(): SymbolCatalog
    {
        return app(SymbolCatalog::class);
    }

    public function test_release_purges_an_unreferenced_symbol()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);

        $this->assertTrue($this->catalog()->release('FPT'));
        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
    }

    public function test_release_spares_a_symbol_referenced_by_a_watchlist()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        Watchlist::create(['admin_id' => $this->admin()->id, 'symbol_code' => 'FPT']);

        $this->assertFalse($this->catalog()->release('FPT'));
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_release_spares_a_symbol_referenced_by_a_financial_statement()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        FinancialStatement::create([
            'symbol' => 'FPT', 'year' => 2024, 'quarter' => 0, 'last_pulled_by_admin_id' => $this->admin()->id,
        ]);

        $this->assertFalse($this->catalog()->release('FPT'));
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_release_spares_a_symbol_referenced_by_another_admins_directory()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        DirectoryEntry::create(['admin_id' => $this->admin()->id, 'symbol_code' => 'FPT']);

        $this->assertFalse($this->catalog()->release('FPT'));
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_release_is_case_insensitive_about_references()
    {
        // THE LOAD-BEARING TEST. A raw insert bypasses the model's upper-casing
        // mutator, and SQLite compares strings case-sensitively, so a naive
        // where('symbol_code', 'FPT') would miss this row and wrongly purge a
        // ticker that is still in use. isReferenced() must compare via UPPER().
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        DB::table('watchlists')->insert([
            'admin_id'   => $this->admin()->id,
            'symbol_code' => 'fpt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse($this->catalog()->release('FPT'));
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_release_normalises_the_code_it_is_given()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);

        $this->assertTrue($this->catalog()->release('  fpt '));
        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
    }

    public function test_release_is_a_no_op_for_unknown_or_empty_codes()
    {
        $this->assertFalse($this->catalog()->release('ZZZ'));
        $this->assertFalse($this->catalog()->release('   '));
    }

    public function test_release_many_returns_the_purge_count_and_skips_referenced_codes()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        Symbol::create(['code' => 'VNM', 'name' => 'CTCP Sua Viet Nam', 'exchange' => 'HSX']);
        Watchlist::create(['admin_id' => $this->admin()->id, 'symbol_code' => 'VNM']);

        $this->assertSame(1, $this->catalog()->releaseMany(['FPT', 'VNM']));
        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
        $this->assertDatabaseHas('symbols', ['code' => 'VNM']);
    }
}
