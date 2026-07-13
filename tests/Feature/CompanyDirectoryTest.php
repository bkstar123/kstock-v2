<?php

namespace Tests\Feature;

use App\Models\FinancialStatement;
use App\Models\Symbol;
use App\Services\Contracts\Symbols as SymbolsInterface;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSymbols;
use Tests\TestCase;

class CompanyDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SymbolsInterface::class, new FakeSymbols());
    }

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Dir', 'username' => 'dir', 'email' => 'dir@example.com',
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    public function test_guest_is_redirected_from_directory()
    {
        $this->get('/cms/companies')->assertRedirect();
    }

    public function test_admin_sees_directory_and_search_filters()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        Symbol::create(['code' => 'VNM', 'name' => 'CTCP Sua Viet Nam', 'exchange' => 'HSX']);

        $admin = $this->admin();

        $this->actingAs($admin, 'admins')->get('/cms/companies')
            ->assertStatus(200)->assertSee('FPT')->assertSee('VNM');

        $this->actingAs($admin, 'admins')->get('/cms/companies?search=FPT')
            ->assertStatus(200)->assertSee('FPT')->assertDontSee('VNM');
    }

    public function test_store_adds_a_known_symbol()
    {
        $this->actingAs($this->admin(), 'admins')
            ->post('/cms/companies', ['symbol' => 'FPT'])
            ->assertRedirect(route('cms.companies.show', ['code' => 'FPT']));

        $this->assertDatabaseHas('symbols', ['code' => 'FPT', 'exchange' => 'HSX']);
    }

    public function test_store_rejects_an_unknown_symbol()
    {
        $this->actingAs($this->admin(), 'admins')
            ->from('/cms/companies')
            ->post('/cms/companies', ['symbol' => 'ZZZ'])
            ->assertRedirect('/cms/companies');

        $this->assertDatabaseCount('symbols', 0);
    }

    public function test_store_validates_malicious_symbol()
    {
        $this->actingAs($this->admin(), 'admins')
            ->from('/cms/companies')
            ->post('/cms/companies', ['symbol' => '../../etc'])
            ->assertSessionHasErrors('symbol');

        $this->assertDatabaseCount('symbols', 0);
    }

    public function test_show_known_company()
    {
        $this->actingAs($this->admin(), 'admins')->get('/cms/companies/FPT')
            ->assertStatus(200)
            ->assertSee('CTCP FPT')
            ->assertSee('P/E');

        // remember() should have upserted the master row
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_show_displays_pb_card_even_without_statements()
    {
        // P/B card label always renders; value is "—" when no book value is available.
        $this->actingAs($this->admin(), 'admins')->get('/cms/companies/FPT')
            ->assertStatus(200)->assertSee('P/B');
    }

    public function test_pb_is_derived_from_market_cap_and_latest_equity()
    {
        $admin = $this->admin();

        // Latest balance statement exposing total equity (item 302 = VCSH).
        $fs = FinancialStatement::create([
            'symbol' => 'FPT', 'admin_id' => $admin->id, 'year' => 2024, 'quarter' => 0,
        ]);
        $fs->balance_statement()->create([
            'content' => json_encode([[
                'id' => '302', 'name' => 'VCSH', 'parentID' => 0, 'expanded' => true,
                'level' => 1, 'field' => 'BS',
                'values' => [['year' => 2024, 'quarter' => 0, 'period' => '2024', 'value' => 34000000000000]],
            ]]),
        ]);

        // FakeSymbols market cap for FPT = 124,185,669,120,000 → P/B ≈ 3.65.
        $this->actingAs($admin, 'admins')->get('/cms/companies/FPT')
            ->assertStatus(200)->assertSee('3.65');
    }

    public function test_pb_excludes_nci_and_shows_book_period_with_stale_warning()
    {
        $admin = $this->admin();

        // 2024 annual report: total equity 34e12 incl. NCI 4e12 -> parent equity 30e12.
        $fs = FinancialStatement::create([
            'symbol' => 'FPT', 'admin_id' => $admin->id, 'year' => 2024, 'quarter' => 0,
        ]);
        $fs->balance_statement()->create([
            'content' => json_encode([
                ['id' => '302', 'name' => 'VCSH', 'parentID' => 0, 'expanded' => true, 'level' => 1, 'field' => 'BS',
                 'values' => [['year' => 2024, 'quarter' => 0, 'period' => '2024', 'value' => 34000000000000]]],
                ['id' => '3020114', 'name' => 'NCI', 'parentID' => 30201, 'expanded' => true, 'level' => 4, 'field' => 'BS',
                 'values' => [['year' => 2024, 'quarter' => 0, 'period' => '2024', 'value' => 4000000000000]]],
            ]),
        ]);

        // 124,185,669,120,000 / 30e12 = 4.14 (NCI excluded; would be 3.65 if not).
        $this->actingAs($admin, 'admins')->get('/cms/companies/FPT')
            ->assertStatus(200)
            ->assertSee('4.14')
            ->assertDontSee('3.65')
            ->assertSee('book 2024')                        // book period label
            ->assertSee('fa-exclamation-triangle', false);  // stale warning (2024 book, viewed later)
    }

    public function test_show_unknown_company_redirects_to_directory()
    {
        $this->actingAs($this->admin(), 'admins')->get('/cms/companies/ZZZ')
            ->assertRedirect(route('cms.companies.index'));
    }

    public function test_price_history_returns_json()
    {
        $this->actingAs($this->admin(), 'admins')
            ->getJson('/cms/companies/FPT/price-history?range=3m')
            ->assertStatus(200)
            ->assertJsonPath('code', 'FPT')
            ->assertJsonCount(2, 'ohlc');
    }
}
