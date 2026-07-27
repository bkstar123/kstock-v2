<?php

namespace Tests\Feature;

use App\Models\DirectoryEntry;
use App\Models\FinancialStatement;
use App\Models\Symbol;
use App\Models\Watchlist;
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

    public function test_superadmin_sees_only_their_own_directory()
    {
        // The directory is a personal working list, not an administrative view of
        // the catalog: a symbol in nobody's directory is invisible even to a
        // superadmin. (Financial statements deliberately keep superadmin-sees-all.)
        Symbol::create(['code' => 'HPG', 'name' => 'CTCP Tap doan Hoa Phat', 'exchange' => 'HSX']);
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);

        $super = $this->superadmin();
        DirectoryEntry::create(['admin_id' => $super->id, 'symbol_code' => 'FPT']);

        $this->actingAs($super, 'admins')->get('/cms/companies')
            ->assertStatus(200)->assertSee('FPT')->assertDontSee('HPG');
    }

    public function test_superadmin_does_not_see_another_admins_directory()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        Symbol::create(['code' => 'VNM', 'name' => 'CTCP Sua Viet Nam', 'exchange' => 'HSX']);

        $super = $this->superadmin();
        $other = $this->admin();
        DirectoryEntry::create(['admin_id' => $super->id, 'symbol_code' => 'FPT']);
        DirectoryEntry::create(['admin_id' => $other->id, 'symbol_code' => 'VNM']);

        $this->actingAs($super, 'admins')->get('/cms/companies')
            ->assertStatus(200)->assertSee('FPT')->assertDontSee('VNM');
    }

    public function test_exchange_facet_is_scoped_to_the_actors_directory()
    {
        // The dropdown must not offer exchanges the admin has no symbols on.
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        Symbol::create(['code' => 'SHS', 'name' => 'CTCP CK Sai Gon Ha Noi', 'exchange' => 'HNX']);

        $admin = $this->admin();
        DirectoryEntry::create(['admin_id' => $admin->id, 'symbol_code' => 'FPT']);

        $this->actingAs($admin, 'admins')->get('/cms/companies')
            ->assertStatus(200)->assertSee('HSX')->assertDontSee('HNX');
    }

    public function test_directory_renders_a_bootstrap_modal_not_a_js_confirm()
    {
        $symbol = Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);

        $this->actingAs($a, 'admins')->get('/cms/companies')
            ->assertStatus(200)
            ->assertSee('removing-modal-' . $symbol->id, false)
            ->assertSee('deleting-form-' . $symbol->id, false)
            ->assertSee('modal-header bg-danger', false)   // inherits the modern.css theme rules
            ->assertDontSee('return confirm(', false);
    }

    public function test_company_page_renders_a_bootstrap_modal_not_a_js_confirm()
    {
        $a = $this->admin();
        // show() upserts the master row via remember(); add the directory entry so
        // the "In directory" button (and therefore the modal) renders.
        $this->actingAs($a, 'admins')->get('/cms/companies/FPT');
        $symbol = Symbol::where('code', 'FPT')->firstOrFail();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);

        $this->actingAs($a, 'admins')->get('/cms/companies/FPT')
            ->assertStatus(200)
            ->assertSee('removing-modal-' . $symbol->id, false)
            ->assertSee('deleting-form-' . $symbol->id, false)
            ->assertDontSee('return confirm(', false);
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
        // The last assertion is the reference-counting requirement itself: the
        // master row is spared *because* B still references it. Do not "simplify"
        // it away — without it, nothing pins that behaviour.
        $this->assertDatabaseMissing('directory_entries', ['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        $this->assertDatabaseHas('directory_entries', ['admin_id' => $b->id, 'symbol_code' => 'FPT']);
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_destroy_purges_the_master_row_when_nothing_else_references_it()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);

        $this->actingAs($a, 'admins')->from('/cms/companies')
            ->delete('/cms/companies/FPT')
            ->assertRedirect('/cms/companies');

        $this->assertDatabaseCount('directory_entries', 0);
        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
    }

    public function test_destroy_keeps_the_actors_watchlist_entry_and_the_master_row()
    {
        // Removing from the directory touches ONLY the directory entry.
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        Watchlist::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);

        $this->actingAs($a, 'admins')->from('/cms/companies')->delete('/cms/companies/FPT');

        $this->assertDatabaseMissing('directory_entries', ['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        $this->assertDatabaseHas('watchlists', ['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_destroy_keeps_the_actors_financial_statements_and_the_master_row()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        FinancialStatement::create([
            'symbol' => 'FPT', 'year' => 2024, 'quarter' => 0, 'last_pulled_by_admin_id' => $a->id,
        ]);

        $this->actingAs($a, 'admins')->from('/cms/companies')->delete('/cms/companies/FPT');

        $this->assertDatabaseMissing('directory_entries', ['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        $this->assertDatabaseHas('financial_statements', ['symbol' => 'FPT']);
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_destroy_from_the_company_page_redirects_to_the_directory()
    {
        // Guards the resurrection vector: back() would re-enter show(), whose
        // remember() call would re-create the row destroy() just purged.
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $a = $this->admin();
        DirectoryEntry::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);

        $this->actingAs($a, 'admins')
            ->from(route('cms.companies.show', ['code' => 'FPT']))
            ->delete('/cms/companies/FPT')
            ->assertRedirect(route('cms.companies.index'));

        $this->assertDatabaseMissing('symbols', ['code' => 'FPT']);
    }

    public function test_destroy_by_a_non_owner_does_not_purge_the_master_row()
    {
        // The security fix. FPT is an orphan master row with zero references — the
        // maximally hostile case: without the $deleted guard in destroy(),
        // release() finds no references and purges a row the caller never owned.
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $stranger = $this->admin();

        $this->actingAs($stranger, 'admins')->from('/cms/companies')
            ->delete('/cms/companies/FPT')
            ->assertRedirect('/cms/companies');

        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
        $this->assertDatabaseCount('directory_entries', 0);
    }

    public function test_destroy_is_a_no_op_for_an_unknown_symbol()
    {
        $this->actingAs($this->admin(), 'admins')->from('/cms/companies')
            ->delete('/cms/companies/ZZZ')
            ->assertRedirect('/cms/companies');

        $this->assertDatabaseCount('directory_entries', 0);
        $this->assertDatabaseCount('symbols', 0);
    }

    public function test_guest_cannot_destroy_an_entry()
    {
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $admin = $this->admin();
        DirectoryEntry::create(['admin_id' => $admin->id, 'symbol_code' => 'FPT']);

        $this->delete('/cms/companies/FPT')->assertRedirect();

        $this->assertDatabaseHas('directory_entries', ['admin_id' => $admin->id, 'symbol_code' => 'FPT']);
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
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
