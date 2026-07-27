<?php

namespace Tests\Feature;

use App\Models\DirectoryEntry;
use App\Models\Symbol;
use App\Models\Watchlist;
use App\Services\Contracts\Symbols as SymbolsInterface;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSymbols;
use Tests\TestCase;

class WatchlistTest extends TestCase
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
            'name' => "W{$n}", 'username' => "w{$n}", 'email' => "w{$n}@example.com",
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    public function test_guest_is_redirected()
    {
        $this->get('/cms/watchlist')->assertRedirect();
    }

    public function test_follow_a_known_symbol()
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admins')->from('/cms/watchlist')
            ->post('/cms/watchlist', ['symbol' => 'fpt'])
            ->assertRedirect('/cms/watchlist');

        $this->assertDatabaseHas('watchlists', [
            'admin_id' => $admin->id, 'symbol_code' => 'FPT',
        ]);
    }

    public function test_follow_is_idempotent()
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admins')->post('/cms/watchlist', ['symbol' => 'FPT']);
        $this->actingAs($admin, 'admins')->post('/cms/watchlist', ['symbol' => 'FPT']);

        $this->assertDatabaseCount('watchlists', 1);
    }

    public function test_follow_unknown_symbol_is_rejected()
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admins')->from('/cms/watchlist')
            ->post('/cms/watchlist', ['symbol' => 'ZZZ'])
            ->assertRedirect('/cms/watchlist');

        $this->assertDatabaseCount('watchlists', 0);
    }

    public function test_index_shows_followed_symbols()
    {
        $admin = $this->admin();
        Watchlist::create(['admin_id' => $admin->id, 'symbol_code' => 'FPT']);

        $this->actingAs($admin, 'admins')->get('/cms/watchlist')
            ->assertStatus(200)->assertSee('FPT');
    }

    public function test_unfollow_removes_symbol()
    {
        $admin = $this->admin();
        Watchlist::create(['admin_id' => $admin->id, 'symbol_code' => 'FPT']);

        $this->actingAs($admin, 'admins')->from('/cms/watchlist')
            ->delete('/cms/watchlist/FPT')
            ->assertRedirect('/cms/watchlist');

        $this->assertDatabaseCount('watchlists', 0);
    }

    public function test_watchlist_still_shows_the_name_after_the_directory_entry_is_removed()
    {
        // End-to-end proof that removing from the directory is scoped: the shared
        // master row survives *because* the watchlist still references it, so the
        // Name cell stays populated instead of degrading to optional()'s blank.
        Symbol::create(['code' => 'FPT', 'name' => 'CTCP FPT', 'exchange' => 'HSX']);
        $admin = $this->admin();
        DirectoryEntry::create(['admin_id' => $admin->id, 'symbol_code' => 'FPT']);
        Watchlist::create(['admin_id' => $admin->id, 'symbol_code' => 'FPT']);

        $this->actingAs($admin, 'admins')->delete('/cms/companies/FPT');

        $this->actingAs($admin, 'admins')->get('/cms/watchlist')
            ->assertStatus(200)->assertSee('CTCP FPT');
        $this->assertDatabaseHas('symbols', ['code' => 'FPT']);
    }

    public function test_admin_does_not_see_another_admins_watchlist()
    {
        $a = $this->admin();
        $b = $this->admin();
        Watchlist::create(['admin_id' => $a->id, 'symbol_code' => 'FPT']);
        Watchlist::create(['admin_id' => $b->id, 'symbol_code' => 'VNM']);

        $this->actingAs($a, 'admins')->get('/cms/watchlist')
            ->assertStatus(200)->assertSee('FPT')->assertDontSee('VNM');
    }
}
