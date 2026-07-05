<?php

namespace Tests\Feature;

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

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'W', 'username' => 'w', 'email' => 'w@example.com',
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
}
