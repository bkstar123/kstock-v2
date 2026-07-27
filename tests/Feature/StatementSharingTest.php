<?php

namespace Tests\Feature;

use App\Jobs\PullFinancialStatement;
use App\Models\FinancialStatement;
use App\Services\Contracts\Symbols as SymbolsInterface;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Bkstar123\BksCMS\AdminPanel\Role;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakeSymbols;
use Tests\TestCase;

/**
 * `financial_statements` is keyed on (symbol, year, quarter) and shared: two admins
 * pulling the same period get ONE row plus two pivot rows, instead of two full copies
 * of ~330 KB of JSON.
 */
class StatementSharingTest extends TestCase
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
            'name' => "Shr{$n}", 'username' => "shr{$n}", 'email' => "shr{$n}@example.com",
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    public function test_two_admins_pulling_the_same_period_share_one_row()
    {
        Queue::fake();
        $a = $this->admin();
        $b = $this->admin();
        $payload = ['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1];

        $this->actingAs($a, 'admins')->post(route('cms.financial.statements.pull'), $payload);
        $this->actingAs($b, 'admins')->post(route('cms.financial.statements.pull'), $payload);

        $this->assertDatabaseCount('financial_statements', 1);
        $this->assertDatabaseCount('financial_statement_entries', 2);
        // Both pulls still dispatch: the second one refreshes the shared row.
        Queue::assertPushed(PullFinancialStatement::class, 2);
    }

    public function test_the_same_admin_pulling_twice_does_not_duplicate()
    {
        Queue::fake();
        $a = $this->admin();
        $payload = ['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1];

        $this->actingAs($a, 'admins')->post(route('cms.financial.statements.pull'), $payload);
        $this->actingAs($a, 'admins')->post(route('cms.financial.statements.pull'), $payload);

        $this->assertDatabaseCount('financial_statements', 1);
        $this->assertDatabaseCount('financial_statement_entries', 1);
    }

    public function test_the_second_pull_says_it_is_a_refresh()
    {
        Queue::fake();
        $a = $this->admin();
        $b = $this->admin();
        $payload = ['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1];

        $this->actingAs($a, 'admins')->post(route('cms.financial.statements.pull'), $payload);
        $this->actingAs($b, 'admins')
            ->post(route('cms.financial.statements.pull'), $payload)
            ->assertSessionHas('flash_notification', fn ($flash) => str_contains($flash['message'] ?? '', 'already in KSTOCK'));
    }

    public function test_different_periods_of_the_same_symbol_stay_separate()
    {
        Queue::fake();
        // One admin per pull: RequestByUserThrottling is keyed on
        // (method, path, user id) with a 10s window, so the SAME admin posting three
        // times in a row would have the last two silently throttled away.
        foreach ([1, 2, 0] as $quarter) {
            $this->actingAs($this->admin(), 'admins')
                ->post(route('cms.financial.statements.pull'), ['symbol' => 'FPT', 'year' => 2024, 'quarter' => $quarter]);
        }

        $this->assertDatabaseCount('financial_statements', 3);
        $this->assertDatabaseCount('financial_statement_entries', 3);
    }

    public function test_the_natural_key_is_enforced_at_the_database_level()
    {
        // The unique index is the only real arbiter against two admins racing —
        // RequestByUserThrottling is per user + path and gives no cross-user cover.
        $a = $this->admin();
        FinancialStatement::create(['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1, 'last_pulled_by_admin_id' => $a->id]);

        $this->expectException(QueryException::class);
        FinancialStatement::create(['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1, 'last_pulled_by_admin_id' => $a->id]);
    }

    public function test_a_lowercase_pull_resolves_to_the_same_shared_row()
    {
        Queue::fake();
        $a = $this->admin();
        $b = $this->admin();

        $this->actingAs($a, 'admins')->post(route('cms.financial.statements.pull'), ['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1]);
        $this->actingAs($b, 'admins')->post(route('cms.financial.statements.pull'), ['symbol' => 'fpt', 'year' => 2024, 'quarter' => 1]);

        $this->assertDatabaseCount('financial_statements', 1);
        $this->assertDatabaseCount('financial_statement_entries', 2);
    }

    private function superadmin(): Admin
    {
        $admin = $this->admin();
        $role = Role::firstOrCreate(['role' => 'Super Administrators'], ['description' => 'test']);
        if ($role->id !== Role::SUPERADMINS) {
            $role->id = Role::SUPERADMINS;
            $role->save();
        }
        $admin->roles()->attach(Role::SUPERADMINS);
        return $admin->refresh();
    }

    public function test_a_regular_admin_sees_when_it_was_refreshed_but_not_by_whom()
    {
        Queue::fake();
        $puller = $this->admin();
        $viewer = $this->admin();
        $payload = ['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1];
        // Viewer pulls FIRST (so they hold it), puller pulls SECOND (so they are the
        // last refresher). Get this order wrong and last_pulled_by is the viewer
        // themselves, and the assertion below passes for the wrong reason.
        $this->actingAs($viewer, 'admins')->post(route('cms.financial.statements.pull'), $payload);
        $this->actingAs($puller, 'admins')->post(route('cms.financial.statements.pull'), $payload);

        $this->actingAs($viewer, 'admins')->get('/cms/financial-statements')
            ->assertStatus(200)
            ->assertSee('FPT')
            ->assertDontSee($puller->email)
            ->assertSee('ago', false);           // the "x minutes ago" timestamp
    }

    public function test_a_superadmin_sees_who_refreshed_it()
    {
        Queue::fake();
        $super = $this->superadmin();
        $puller = $this->admin();
        $payload = ['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1];
        $this->actingAs($puller, 'admins')->post(route('cms.financial.statements.pull'), $payload);

        $this->actingAs($super, 'admins')->get('/cms/financial-statements')
            ->assertStatus(200)
            ->assertSee($puller->email);
    }

    public function test_the_company_page_hides_the_puller_from_a_regular_admin()
    {
        Queue::fake();
        $puller = $this->admin();
        $viewer = $this->admin();
        $payload = ['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1];
        $this->actingAs($viewer, 'admins')->post(route('cms.financial.statements.pull'), $payload);
        $this->actingAs($puller, 'admins')->post(route('cms.financial.statements.pull'), $payload);

        $this->actingAs($viewer, 'admins')->get('/cms/companies/FPT')
            ->assertStatus(200)
            ->assertDontSee($puller->email);
    }
}
