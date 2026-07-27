<?php

namespace Tests\Feature;

use App\Models\AnalysisReport;
use App\Models\BalanceStatement;
use App\Models\CashFlowStatement;
use App\Models\FinancialStatement;
use App\Models\FinancialStatementEntry;
use App\Models\IncomeStatement;
use App\Services\StatementLibrary;
use Bkstar123\BksCMS\AdminPanel\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * StatementLibrary — reference-counted ownership of the SHARED financial_statements
 * rows. A statement and its ~330 KB of child blobs may only be deleted once no admin
 * holds it any more.
 */
class StatementReleaseTest extends TestCase
{
    use RefreshDatabase;

    private int $adminSeq = 0;

    private function admin(): Admin
    {
        $n = ++$this->adminSeq;
        return Admin::create([
            'name' => "Lib{$n}", 'username' => "lib{$n}", 'email' => "lib{$n}@example.com",
            'password' => bcrypt('secret123'),
        ])->refresh();
    }

    private function library(): StatementLibrary
    {
        return app(StatementLibrary::class);
    }

    /** A statement with a full set of children, held by nobody yet. */
    private function statement(string $symbol = 'FPT', int $year = 2024, int $quarter = 1): FinancialStatement
    {
        $fs = FinancialStatement::create([
            'symbol' => $symbol, 'year' => $year, 'quarter' => $quarter,
            'last_pulled_by_admin_id' => $this->admin()->id,
        ]);
        BalanceStatement::create(['financial_statement_id' => $fs->id, 'content' => '[]']);
        IncomeStatement::create(['financial_statement_id' => $fs->id, 'content' => '[]']);
        CashFlowStatement::create(['financial_statement_id' => $fs->id, 'content' => '[]']);
        AnalysisReport::create(['financial_statement_id' => $fs->id, 'content' => '[]']);

        return $fs;
    }

    public function test_attach_is_idempotent()
    {
        $fs = $this->statement();
        $a = $this->admin();

        $this->library()->attach($fs, $a->id);
        $this->library()->attach($fs, $a->id);

        $this->assertDatabaseCount('financial_statement_entries', 1);
    }

    public function test_detach_reports_whether_a_hold_was_actually_removed()
    {
        $fs = $this->statement();
        $a = $this->admin();
        $this->library()->attach($fs, $a->id);

        // The return value is the guard callers use before purging — an admin who
        // never held the statement must not be able to trigger the purge.
        $this->assertTrue($this->library()->detach($fs->id, $a->id));
        $this->assertFalse($this->library()->detach($fs->id, $a->id));
        $this->assertFalse($this->library()->detach($fs->id, $this->admin()->id));
    }

    public function test_release_spares_a_statement_another_admin_still_holds()
    {
        $fs = $this->statement();
        $a = $this->admin();
        $b = $this->admin();
        $this->library()->attach($fs, $a->id);
        $this->library()->attach($fs, $b->id);

        $this->library()->detach($fs->id, $a->id);

        $this->assertFalse($this->library()->release($fs->id));
        $this->assertDatabaseHas('financial_statements', ['id' => $fs->id]);
        $this->assertDatabaseHas('analysis_reports', ['financial_statement_id' => $fs->id]);
        $this->assertDatabaseCount('financial_statement_entries', 1);
    }

    public function test_release_by_the_last_holder_purges_the_statement_and_all_four_children()
    {
        $fs = $this->statement();
        $a = $this->admin();
        $this->library()->attach($fs, $a->id);

        $this->library()->detach($fs->id, $a->id);

        $this->assertTrue($this->library()->release($fs->id));
        $this->assertDatabaseMissing('financial_statements', ['id' => $fs->id]);
        foreach (['balance_statements', 'income_statements', 'cash_flow_statements', 'analysis_reports'] as $table) {
            $this->assertDatabaseMissing($table, ['financial_statement_id' => $fs->id]);
        }
    }

    public function test_purge_removes_children_even_with_foreign_keys_disabled()
    {
        // purge() deletes children explicitly rather than trusting the FK cascade,
        // because SQLite only enforces it while PRAGMA foreign_keys is on and that is
        // a config toggle (config/database.php `foreign_key_constraints`).
        $fs = $this->statement();
        DB::statement('PRAGMA foreign_keys = OFF');

        $this->assertTrue($this->library()->purge($fs->id));

        DB::statement('PRAGMA foreign_keys = ON');
        foreach (['balance_statements', 'income_statements', 'cash_flow_statements', 'analysis_reports'] as $table) {
            $this->assertDatabaseMissing($table, ['financial_statement_id' => $fs->id]);
        }
        $this->assertDatabaseMissing('financial_statements', ['id' => $fs->id]);
    }

    public function test_purge_ignores_holders()
    {
        // The administrative escape hatch: superadmin massive-destroy must work on
        // statements other admins still hold.
        $fs = $this->statement();
        $this->library()->attach($fs, $this->admin()->id);

        $this->assertTrue($this->library()->purge($fs->id));

        $this->assertDatabaseCount('financial_statement_entries', 0);
        $this->assertDatabaseMissing('financial_statements', ['id' => $fs->id]);
    }

    public function test_release_many_returns_the_purge_count_and_skips_held_statements()
    {
        $held = $this->statement('FPT');
        $orphan = $this->statement('VNM');
        $this->library()->attach($held, $this->admin()->id);

        $this->assertSame(1, $this->library()->releaseMany([$held->id, $orphan->id]));
        $this->assertDatabaseHas('financial_statements', ['id' => $held->id]);
        $this->assertDatabaseMissing('financial_statements', ['id' => $orphan->id]);
    }

    public function test_release_is_a_no_op_for_an_unknown_statement()
    {
        $this->assertFalse($this->library()->release(999999));
    }
}
