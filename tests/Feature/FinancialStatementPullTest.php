<?php

namespace Tests\Feature;

use App\Jobs\PullFinancialStatement;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Covers the "pull financial statement" endpoint, including the hardened
 * `symbol` validation (regression guard for the SSRF / path-injection fix).
 */
class FinancialStatementPullTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        // refresh() loads DB-default columns (status) that the bkscms-auth
        // middleware checks against Admin::ACTIVE.
        return Admin::create([
            'name'     => 'Puller',
            'username' => 'puller',
            'email'    => 'puller@example.com',
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    public function test_valid_symbol_creates_record_and_dispatches_job()
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin, 'admins')->post(route('cms.financial.statements.pull'), [
            'symbol'  => 'FPT',
            'year'    => 2022,
            'quarter' => 1,
        ]);

        $this->assertDatabaseHas('financial_statements', [
            'symbol'   => 'FPT',
            'year'     => 2022,
            'quarter'  => 1,
            'admin_id' => $admin->id,
        ]);
        Queue::assertPushed(PullFinancialStatement::class);
    }

    public function test_lowercase_symbol_is_stored_uppercased()
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin, 'admins')->post(route('cms.financial.statements.pull'), [
            'symbol'  => 'hvn',
            'year'    => 2022,
            'quarter' => 0,
        ]);

        $this->assertDatabaseHas('financial_statements', ['symbol' => 'HVN']);
    }

    #[DataProvider('maliciousSymbols')]
    public function test_malicious_symbol_is_rejected(string $symbol)
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin, 'admins')
            ->from(route('cms.financial.statements.index'))
            ->post(route('cms.financial.statements.pull'), [
                'symbol'  => $symbol,
                'year'    => 2022,
                'quarter' => 1,
            ])
            ->assertSessionHasErrors('symbol');

        $this->assertDatabaseCount('financial_statements', 0);
        Queue::assertNothingPushed();
    }

    public static function maliciousSymbols(): array
    {
        return [
            'path traversal'    => ['../../etc/passwd'],
            'query injection'   => ['FPT?limit=999'],
            'slash'             => ['FPT/full'],
            'whitespace'        => ['FPT OR 1'],
            'html/script'       => ['<script>'],
            'too long'          => [str_repeat('A', 21)],
        ];
    }

    public function test_invalid_year_and_quarter_are_rejected()
    {
        Queue::fake();
        $admin = $this->admin();

        $this->actingAs($admin, 'admins')
            ->from(route('cms.financial.statements.index'))
            ->post(route('cms.financial.statements.pull'), [
                'symbol'  => 'FPT',
                'year'    => 1500,   // out of 1900..2100
                'quarter' => 9,      // out of 0..4
            ])
            ->assertSessionHasErrors(['year', 'quarter']);

        Queue::assertNothingPushed();
    }

    public function test_guest_cannot_pull()
    {
        Queue::fake();

        $this->post(route('cms.financial.statements.pull'), [
            'symbol'  => 'FPT',
            'year'    => 2022,
            'quarter' => 1,
        ])->assertRedirect();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('financial_statements', 0);
    }
}
