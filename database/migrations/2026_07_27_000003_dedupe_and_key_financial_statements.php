<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------------
        // 1. Collapse duplicate (symbol, year, quarter) rows.
        //
        // Keeper = the row with the most complete child set, tie-broken on the
        // newest id. Children are deliberately NOT reparented across rows: a
        // statement's four children are one coherent window snapshot (limits+5
        // periods ending at the requested period) from ONE pull, and stitching a
        // January balance sheet onto a June income statement would mix original and
        // restated figures. Anything the collapse drops is one "Pull" click away —
        // a re-pull now refreshes the shared row in place.
        //
        // DML only, so a transaction is correct HERE. See the warning below.
        // ---------------------------------------------------------------------
        DB::transaction(function () {
            $duplicates = DB::table('financial_statements')
                ->select('symbol', 'year', 'quarter')
                ->groupBy('symbol', 'year', 'quarter')
                ->havingRaw('count(*) > 1')
                ->get();

            foreach ($duplicates as $group) {
                $ids = DB::table('financial_statements as fs')
                    ->where('fs.symbol', $group->symbol)
                    ->where('fs.year', $group->year)
                    ->where('fs.quarter', $group->quarter)
                    ->selectRaw(
                        'fs.id, '
                        . '(select count(*) from balance_statements b where b.financial_statement_id = fs.id) '
                        . '+ (select count(*) from income_statements i where i.financial_statement_id = fs.id) '
                        . '+ (select count(*) from cash_flow_statements c where c.financial_statement_id = fs.id) '
                        . '+ (select count(*) from analysis_reports a where a.financial_statement_id = fs.id) as completeness'
                    )
                    ->orderByDesc('completeness')
                    ->orderByDesc('fs.id')
                    ->pluck('fs.id');

                $keeper = (int) $ids->first();
                $losers = $ids->slice(1)->map(fn ($id) => (int) $id)->values();
                if ($losers->isEmpty()) {
                    continue;
                }

                // Move the losers' holders onto the keeper, skipping admins who
                // already hold it (the pivot's unique(admin_id, statement_id)).
                $already = DB::table('financial_statement_entries')
                    ->where('financial_statement_id', $keeper)
                    ->pluck('admin_id');

                DB::table('financial_statement_entries')
                    ->whereIn('financial_statement_id', $losers)
                    ->whereNotIn('admin_id', $already)
                    ->update(['financial_statement_id' => $keeper, 'updated_at' => now()]);

                // Whatever is left is a duplicate hold; drop it, then the rows.
                DB::table('financial_statement_entries')
                    ->whereIn('financial_statement_id', $losers)->delete();

                foreach (['analysis_reports', 'balance_statements', 'income_statements', 'cash_flow_statements'] as $child) {
                    DB::table($child)->whereIn('financial_statement_id', $losers)->delete();
                }
                DB::table('financial_statements')->whereIn('id', $losers)->delete();
            }
        });

        // ---------------------------------------------------------------------
        // 2. Schema. DDL, therefore OUTSIDE every transaction.
        //
        // *** DO NOT WRAP ANY OF THIS IN DB::transaction() ***
        //
        // On SQLite, Schema::table() on `financial_statements` compiles to a full
        // table rebuild (SQLiteGrammar::compileAlter) that drops and recreates the
        // table between PRAGMA foreign_keys OFF/ON. SQLite makes that PRAGMA A
        // NO-OP INSIDE A TRANSACTION, so wrapping this would run
        // `drop table financial_statements` with FK enforcement still ON and
        // CASCADE-DELETE EVERY balance/income/cash-flow/analysis row. Verified
        // empirically against a copy of the real database: 38 analysis_reports
        // became 0. Laravel does not wrap migrations in a transaction on SQLite or
        // MySQL (Grammar::$transactions === false), so the only way to hit this is
        // to add one by hand.
        // ---------------------------------------------------------------------

        // Matches the `symbol` validation regex, symbols.code and the two pivot
        // symbol_code columns, all of which are already varchar(20). Live max
        // length is 3. Also keeps the composite key comfortably inside InnoDB's
        // index limit on any row format.
        Schema::table('financial_statements', function (Blueprint $table) {
            $table->string('symbol', 20)->change();
        });

        Schema::table('financial_statements', function (Blueprint $table) {
            $table->unique(['symbol', 'year', 'quarter']);
        });
    }

    public function down(): void
    {
        // Partially reversible: the schema is restored, the collapsed duplicate rows
        // are not — their blobs were deleted. Documented rather than silent.
        Schema::table('financial_statements', function (Blueprint $table) {
            $table->dropUnique(['symbol', 'year', 'quarter']);
        });
        Schema::table('financial_statements', function (Blueprint $table) {
            $table->string('symbol')->change();
        });
    }
};
