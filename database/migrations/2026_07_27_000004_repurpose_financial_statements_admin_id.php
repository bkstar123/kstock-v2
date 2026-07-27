<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // *** DDL — NOT inside DB::transaction(). See 2026_07_27_000003 for why. ***

        // dropForeign FIRST: InnoDB refuses to drop an index a foreign key is using.
        // Laravel emits Blueprint commands in the order they are added.
        Schema::table('financial_statements', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropIndex(['admin_id']);
            $table->renameColumn('admin_id', 'last_pulled_by_admin_id');
        });

        // Separate call: the column must exist under its new name before it can be
        // changed and re-keyed.
        Schema::table('financial_statements', function (Blueprint $table) {
            $table->bigInteger('last_pulled_by_admin_id')->unsigned()->nullable()->change();
            $table->index('last_pulled_by_admin_id');
            // SET NULL, not CASCADE. Ownership lives in financial_statement_entries
            // now; deleting the last puller must NOT delete a statement other admins
            // still hold. Getting this wrong reintroduces the exact bug being fixed.
            $table->foreign('last_pulled_by_admin_id')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Backfill a non-null owner from the pivot before restoring NOT NULL +
        // CASCADE. Statements nobody holds cannot be represented in the old shape and
        // are dropped.
        DB::table('financial_statements')->whereNull('last_pulled_by_admin_id')->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $adminId = DB::table('financial_statement_entries')
                        ->where('financial_statement_id', $row->id)->min('admin_id');
                    $adminId
                        ? DB::table('financial_statements')->where('id', $row->id)
                            ->update(['last_pulled_by_admin_id' => $adminId])
                        : DB::table('financial_statements')->where('id', $row->id)->delete();
                }
            });

        Schema::table('financial_statements', function (Blueprint $table) {
            $table->dropForeign(['last_pulled_by_admin_id']);
            $table->dropIndex(['last_pulled_by_admin_id']);
            $table->renameColumn('last_pulled_by_admin_id', 'admin_id');
        });
        Schema::table('financial_statements', function (Blueprint $table) {
            $table->bigInteger('admin_id')->unsigned()->nullable(false)->change();
            $table->index('admin_id');
            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnDelete();
        });
    }
};
