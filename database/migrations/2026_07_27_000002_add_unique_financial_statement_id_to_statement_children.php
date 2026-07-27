<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'balance_statements', 'income_statements', 'cash_flow_statements', 'analysis_reports',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            // Keep the newest row per parent. Duplicates are possible because
            // PullFinancialStatement used a plain create() with no $tries/$timeout
            // against config/queue.php's retry_after, so a slow job could be reserved
            // twice, and because AnalyzeFinancialStatement stacked a fresh
            // AnalysisReport on every re-run. hasOne() then picked one arbitrarily.
            //
            // Done in PHP rather than `delete ... where id not in (select max(id) ...)`
            // because MySQL rejects a subquery on the DELETE's own table (error 1093)
            // without a derived-table wrapper. Row counts here are tiny.
            $keep = DB::table($table)->selectRaw('max(id) as id')
                ->groupBy('financial_statement_id')
                ->pluck('id');

            DB::table($table)->whereNotIn('id', $keep)->delete();

            // Order matters on MySQL: InnoDB requires an index on a foreign-key
            // column, so the unique must exist BEFORE the plain index is dropped.
            // Two separate Schema::table() calls so the statements are emitted in
            // that order on every driver.
            //
            // NOT wrapped in a transaction — see the warning in 2026_07_27_000003.
            Schema::table($table, fn (Blueprint $t) => $t->unique('financial_statement_id'));
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex(['financial_statement_id']));
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->index('financial_statement_id'));
            Schema::table($table, fn (Blueprint $t) => $t->dropUnique(['financial_statement_id']));
        }
    }
};
