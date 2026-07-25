<?php

namespace Tests\Feature;

use App\Models\DirectoryEntry;
use App\Models\Symbol;
use App\Services\Contracts\Symbols as SymbolsInterface;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Bkstar123\BksCMS\AdminPanel\Role;
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

    private int $adminSeq = 0;

    private function admin(): Admin
    {
        $n = ++$this->adminSeq;
        return Admin::create([
            'name' => "Dir{$n}", 'username' => "dir{$n}", 'email' => "dir{$n}@example.com",
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    private function superadmin(): Admin
    {
        $admin = $this->admin();
        $role = Role::firstOrCreate(['role' => 'Super Administrators'], ['description' => 'test']);
        // The gate keys on Role::SUPERADMINS (id 1); pin the id so hasRole() matches.
        if ($role->id !== Role::SUPERADMINS) {
            $role->id = Role::SUPERADMINS;
            $role->save();
        }
        $admin->roles()->attach(Role::SUPERADMINS);
        return $admin->refresh();
    }

    public function test_guest_is_redirected_from_directory()
    {
        $this->get('/cms/companies')->assertRedirect();
    }

    public function test_admin_sees_only_their_own_directory_and_search_filters()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        Symbol::create(['code' => 'VNM', 'name' => 'CTCP Sua Viet Nam', 'exchange' => 'HSX']);

        $admin = $this->admin();
        DirectoryEntry::create(['admin_id' => $admin->id, 'symbol_code' => 'FPT']);
        DirectoryEntry::create(['admin_id' => $admin->id, 'symbol_code' => 'VNM']);

        $this->actingAs($admin, 'admins')->get('/cms/companies')
            ->assertStatus(200)->assertSee('FPT')->assertSee('VNM');

        $this->actingAs($admin, 'admins')->get('/cms/companies?search=FPT')
            ->assertStatus(200)->assertSee('FPT')->assertDontSee('VNM');
    }

    public function test_admin_does_not_see_another_admins_directory()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        Symbol::create(['code' => 'VNM', 'name' => 'CTCP Sua Viet Nam', 'exchange' => 'HSX']);

        $a = $this->admin();
        $b = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        DirectoryEntry::create(['admin_id' => $b->id, 'symbol_code' => 'VNM']);

        $this->actingAs($a, 'admins')->get('/cms/companies')
            ->assertStatus(200)->assertSee('FPT')->assertDontSee('VNM');
    }

    public function test_superadmin_sees_the_whole_catalog()
    {
        // A symbol in nobody's directory is still visible to a superadmin.
        Symbol::create(['code' => 'HPG', 'name' => 'CTCP Tap doan Hoa Phat', 'exchange' => 'HSX']);

        $this->actingAs($this->superadmin(), 'admins')->get('/cms/companies')
            ->assertStatus(200)->assertSee('HPG');
    }

    public function test_store_adds_a_known_symbol_to_the_actors_directory()
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admins')
            ->post('/cms/companies', ['symbol' => 'FPT'])
            ->assertRedirect(route('cms.companies.show', ['code' => 'FPT']));

        $this->assertDatabaseHas('symbols', ['code' => 'FPT', 'exchange' => 'HSX']);
        $this->assertDatabaseHas('directory_entries', ['admin_id' => $admin->id, 'symbol_code' => 'FPT']);
    }

    public function test_destroy_removes_only_the_actors_entry_and_keeps_the_master_row()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        $b = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        DirectoryEntry::create(['admin_id' => $b->id, 'symbol_code' => 'FPT']);

        $this->actingAs($a, 'admins')->from('/cms/companies')
            ->delete('/cms/companies/FPT')
            ->assertRedirect('/cms/companies');

        // Only A's entry is gone; B's entry and the shared master row survive.
        $this->assertDatabaseMissing('directory_entries', ['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        $this->assertDatabaseHas('directory_entries', ['admin_id' => $b->id, 'symbol_code' => 'FPT']);
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_destroy_is_a_no_op_for_an_unentered_symbol()
    {
        $this->actingAs($this->admin(), 'admins')->from('/cms/companies')
            ->delete('/cms/companies/ZZZ')
            ->assertRedirect('/cms/companies');

        $this->assertDatabaseCount('directory_entries', 0);
    }

    public function test_guest_cannot_destroy_an_entry()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $admin = $this->admin();
        DirectoryEntry::create(['admin_id' => $admin->id, 'symbol_code' => 'FPT']);

        $this->delete('/cms/companies/FPT')->assertRedirect();

        $this->assertDatabaseHas('directory_entries', ['admin_id' => $admin->id, 'symbol_code' => 'FPT']);
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

    public function test_pb_card_uses_api_value_without_needing_a_statement()
    {
        // P/B now comes straight from the financial-indicators API (FakeSymbols FPT
        // P/B = 3.10) — no pulled statement or derivation, and no book/stale UI.
        $this->actingAs($this->admin(), 'admins')->get('/cms/companies/FPT')
            ->assertStatus(200)
            ->assertSee('P/B')
            ->assertSee('3.10')
            ->assertDontSee('· book');
    }

    public function test_valuation_block_shows_fair_value_and_breakdown()
    {
        // FakeSymbols FPT: composedPrice 70,300 (VND) -> 70.3; latest close 130.5
        // -> current 130,500 -> overvalued (~46% above fair value).
        $this->actingAs($this->admin(), 'admins')->get('/cms/companies/FPT')
            ->assertStatus(200)
            ->assertSee('70.3')                          // blended fair value (nghìn)
            ->assertSee('DCF')                           // method breakdown
            ->assertSee('Graham 1')
            ->assertSee('Cao hơn giá trị hợp lý')        // downside badge
            ->assertSee('không phải khuyến nghị đầu tư'); // disclaimer
    }

    public function test_valuation_unavailable_when_all_methods_null()
    {
        // FakeSymbols VNM returns an all-null estimated-price (like a bank/insurer).
        $this->actingAs($this->admin(), 'admins')->get('/cms/companies/VNM')
            ->assertStatus(200)
            ->assertSee('Định giá không khả dụng');
    }

    public function test_profile_shows_industry_multiples_history_and_business_areas()
    {
        $this->actingAs($this->admin(), 'admins')->get('/cms/companies/FPT')
            ->assertStatus(200)
            // P/E, P/S, P/B — company value vs industryValue
            ->assertSee('Định giá so với ngành')
            ->assertSee('12.48')->assertSee('12.67')   // P/E company / industry
            ->assertSee('1.82')->assertSee('1.58')     // P/S company / industry
            ->assertSee('2.75')                        // P/B industry
            // history + business areas (entities decoded, tags stripped to bullet lines)
            ->assertSee('Lĩnh vực kinh doanh')
            ->assertSee('Công nghệ')
            ->assertSee('Lịch sử hình thành')
            ->assertSee('1988: Thành lập');
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
