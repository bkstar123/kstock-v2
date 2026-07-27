<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeFinancialStatement;
use App\Jobs\PullFinancialStatement;
use App\Models\BalanceStatement;
use App\Models\FinancialStatement;
use App\Models\FinancialStatementEntry;
use App\Services\Contracts\Symbols as SymbolsInterface;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakeSymbols;
use Tests\TestCase;

/**
 * A re-pull REFRESHES a shared statement in place rather than appending a second set
 * of children. `content` is a window snapshot ending at the requested period, so a
 * later pull legitimately returns restated data — the "Pull" button must stay
 * meaningful without duplicating ~330 KB of blobs.
 */
class StatementRefreshTest extends TestCase
{
    use RefreshDatabase;

    private FakeSymbols $api;
    private int $adminSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->api = new FakeSymbols();
        $this->app->instance(SymbolsInterface::class, $this->api);
    }

    private function admin(): Admin
    {
        $n = ++$this->adminSeq;
        return Admin::create([
            'name' => "Ref{$n}", 'username' => "ref{$n}", 'email' => "ref{$n}@example.com",
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    public function test_a_repull_replaces_the_children_instead_of_appending()
    {
        // PARTIAL fake, and it must be partial: a bare Queue::fake() intercepts
        // dispatchSync too, so PullFinancialStatement would silently never run.
        // Naming only the nested job lets the outer one execute for real.
        Queue::fake([AnalyzeFinancialStatement::class]);
        $admin = $this->admin();
        $fs = FinancialStatement::create([
            'symbol' => 'FPT', 'year' => 2024, 'quarter' => 1, 'last_pulled_by_admin_id' => $admin->id,
        ]);
        $payload = ['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1];

        PullFinancialStatement::dispatchSync($payload, $fs->id, $admin);
        $first = BalanceStatement::where('financial_statement_id', $fs->id)->firstOrFail();

        $this->api->pullNonce = 7;   // the provider now returns different figures
        PullFinancialStatement::dispatchSync($payload, $fs->id, $admin);

        foreach (['balance_statements', 'income_statements', 'cash_flow_statements'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
        $second = BalanceStatement::where('financial_statement_id', $fs->id)->firstOrFail();
        $this->assertSame($first->id, $second->id, 'the same row must be updated');
        $this->assertNotSame($first->content, $second->content, 'the content must be refreshed');
        Queue::assertPushed(AnalyzeFinancialStatement::class, 2);
    }

    public function test_a_pull_that_yields_nothing_discards_the_new_statement()
    {
        Queue::fake([AnalyzeFinancialStatement::class]);
        $admin = $this->admin();
        // ZZZ is unknown to the provider, so every call returns 'null'.
        $fs = FinancialStatement::create([
            'symbol' => 'ZZZ', 'year' => 2024, 'quarter' => 1, 'last_pulled_by_admin_id' => $admin->id,
        ]);
        FinancialStatementEntry::create(['admin_id' => $admin->id, 'financial_statement_id' => $fs->id]);

        try {
            PullFinancialStatement::dispatchSync(['symbol' => 'ZZZ', 'year' => 2024, 'quarter' => 1], $fs->id, $admin);
            $this->fail('the job should have thrown');
        } catch (\RuntimeException $e) {
            // expected
        }

        // The old code left a childless parent row behind forever on every failure.
        $this->assertDatabaseCount('financial_statements', 0);
        $this->assertDatabaseCount('financial_statement_entries', 0);
        Queue::assertNotPushed(AnalyzeFinancialStatement::class);
    }

    public function test_a_failed_refresh_does_not_destroy_existing_data()
    {
        // The discard is keyed on "no data at all", so a refresh that fails leaves
        // the previous pull intact — a failed refresh must never destroy data other
        // admins are still reading.
        Queue::fake([AnalyzeFinancialStatement::class]);
        $admin = $this->admin();
        $fs = FinancialStatement::create([
            'symbol' => 'FPT', 'year' => 2024, 'quarter' => 1, 'last_pulled_by_admin_id' => $admin->id,
        ]);
        FinancialStatementEntry::create(['admin_id' => $admin->id, 'financial_statement_id' => $fs->id]);
        PullFinancialStatement::dispatchSync(['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1], $fs->id, $admin);
        $this->assertDatabaseCount('balance_statements', 1);

        // Now ask for a period the provider has no data for.
        try {
            PullFinancialStatement::dispatchSync(['symbol' => 'FPT', 'year' => 1999, 'quarter' => 4], $fs->id, $admin);
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertDatabaseHas('financial_statements', ['id' => $fs->id]);
        $this->assertDatabaseCount('balance_statements', 1);
        $this->assertDatabaseCount('financial_statement_entries', 1);
    }

    public function test_analysis_report_is_replaced_not_stacked_on_recompute()
    {
        $admin = $this->admin();
        $fs = FinancialStatement::create([
            'symbol' => 'FPT', 'year' => 2024, 'quarter' => 1, 'last_pulled_by_admin_id' => $admin->id,
        ]);
        PullFinancialStatement::dispatchSync(['symbol' => 'FPT', 'year' => 2024, 'quarter' => 1], $fs->id, $admin);
        $this->assertDatabaseCount('analysis_reports', 1);

        AnalyzeFinancialStatement::dispatchSync($fs->id, $admin, 'indirect');

        // Nothing used to delete the previous report, so re-analysis stacked rows and
        // hasOne() picked one arbitrarily.
        $this->assertDatabaseCount('analysis_reports', 1);
    }
}
