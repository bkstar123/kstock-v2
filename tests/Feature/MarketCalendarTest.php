<?php

namespace Tests\Feature;

use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'S', 'username' => 's', 'email' => 's@example.com',
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    // --- marketHolidays() helper: decode the JSON setting into an array ---

    public function test_market_holidays_decodes_json_setting()
    {
        config(['settings.market_holidays' => json_encode(['2026-09-02', '2026-04-30'])]);
        $this->assertSame(['2026-09-02', '2026-04-30'], marketHolidays());
    }

    public function test_market_holidays_returns_empty_for_missing_or_invalid()
    {
        config(['settings.market_holidays' => null]);
        $this->assertSame([], marketHolidays());

        config(['settings.market_holidays' => 'not-json']);
        $this->assertSame([], marketHolidays());
    }

    // --- Controller: persist picked dates, dropping invalid ones ---

    public function test_guest_cannot_save_calendar()
    {
        $this->post('/cms/settings/market-calendar', ['holidays' => '[]'])->assertRedirect();
    }

    public function test_saves_only_valid_dates_sorted_and_unique()
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admins')
            ->withoutMiddleware(Authorize::class) // bypass the can:settings.update gate
            ->from('/cms/settings')
            ->post('/cms/settings/market-calendar', [
                'holidays' => json_encode([
                    '2026-09-02', 'bad', '2026-13-40', '2026-04-30', '2026-09-02',
                ]),
            ])
            ->assertRedirect('/cms/settings');

        // Invalid/duplicate dropped; result sorted ascending.
        $this->assertDatabaseHas('settings', [
            'key'   => 'market_holidays',
            'value' => json_encode(['2026-04-30', '2026-09-02']),
        ]);
    }

    public function test_empty_submission_clears_holidays()
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admins')
            ->withoutMiddleware(Authorize::class)
            ->post('/cms/settings/market-calendar', ['holidays' => '[]']);

        $this->assertDatabaseHas('settings', [
            'key'   => 'market_holidays',
            'value' => json_encode([]),
        ]);
    }
}
