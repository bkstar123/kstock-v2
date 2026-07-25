<?php

use Bkstar123\BksCMS\AdminPanel\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('admin_id')->unsigned()->index();
            $table->string('symbol_code', 20);
            $table->timestamps();

            $table->unique(['admin_id', 'symbol_code']);
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
        });

        // Backfill: the directory used to be a single global list (the `symbols`
        // master table). Assign that existing list to the superadmin(s) so the
        // current curator keeps it; every other admin starts with an empty
        // directory. Regular admins never had a personal list before, so there is
        // nothing to preserve for them.
        // Join to `admins` so orphaned pivot rows (superadmins that were later
        // deleted without cascading) are excluded — inserting an entry for a
        // non-existent admin_id would violate the FK. distinct() guards against
        // any duplicate pivot rows producing duplicate (admin_id, symbol_code).
        $superadminIds = DB::table('admin_role')
            ->join('admins', 'admins.id', '=', 'admin_role.admin_id')
            ->where('admin_role.role_id', Role::SUPERADMINS)
            ->distinct()
            ->pluck('admin_role.admin_id');
        $codes = DB::table('symbols')->pluck('code');
        if ($superadminIds->isNotEmpty() && $codes->isNotEmpty()) {
            $now = now();
            $rows = [];
            foreach ($superadminIds as $adminId) {
                foreach ($codes as $code) {
                    $rows[] = [
                        'admin_id'    => $adminId,
                        'symbol_code' => $code,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('directory_entries')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_entries');
    }
};
