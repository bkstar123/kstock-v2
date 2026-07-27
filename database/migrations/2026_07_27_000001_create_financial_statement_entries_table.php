<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_statement_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('admin_id')->unsigned()->index();
            $table->bigInteger('financial_statement_id')->unsigned()->index();
            $table->timestamps();

            // Doubles as the "my library" lookup index (admin_id leftmost).
            $table->unique(['admin_id', 'financial_statement_id']);
            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnDelete();
            $table->foreign('financial_statement_id')->references('id')
                  ->on('financial_statements')->cascadeOnDelete();
        });

        // Backfill: today every statement has exactly one owner, in
        // financial_statements.admin_id. Join to `admins` so a dangling admin_id
        // could not violate the new FK, and distinct() so we cannot produce a
        // duplicate (admin_id, financial_statement_id) pair — mirrors the guards in
        // 2026_07_25_000001_create_directory_entries_table.php.
        //
        // DML only, so a transaction is correct HERE. It would NOT be safe around
        // schema changes: on SQLite, Schema::table() on `financial_statements` is
        // compiled to a table rebuild that relies on `PRAGMA foreign_keys=OFF`, and
        // SQLite makes that pragma a no-op inside a transaction — the rebuild's
        // `drop table` would then cascade-delete every child row. See the warning in
        // 2026_07_27_000003.
        DB::transaction(function () {
            $now = now();
            DB::table('financial_statements')
                ->join('admins', 'admins.id', '=', 'financial_statements.admin_id')
                ->select('financial_statements.admin_id', 'financial_statements.id as fs_id')
                ->distinct()
                ->orderBy('financial_statements.id')
                ->chunk(500, function ($rows) use ($now) {
                    DB::table('financial_statement_entries')->insert(
                        collect($rows)->map(fn ($row) => [
                            'admin_id'               => $row->admin_id,
                            'financial_statement_id' => $row->fs_id,
                            'created_at'             => $now,
                            'updated_at'             => $now,
                        ])->all()
                    );
                });
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_statement_entries');
    }
};
