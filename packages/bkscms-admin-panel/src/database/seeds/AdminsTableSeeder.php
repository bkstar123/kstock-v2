<?php
/**
 * AdminsTableSeeder
 *
 * @author: tuanha
 * @last-mod: 02-Nov-2019
 */
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // NB: no DB::transaction() wrapper here. TRUNCATE is a DDL statement that
        // implicitly commits on MySQL, which breaks a surrounding transaction
        // ("There is no active transaction" on commit). Running the reset + inserts
        // directly works on MySQL and SQLite alike, and keeps TRUNCATE's AUTO_INCREMENT
        // reset so hardcoded id references in the other seeders stay valid.
        Schema::disableForeignKeyConstraints();
        DB::table('profiles')->truncate();
        DB::table('admins')->truncate();
        Schema::enableForeignKeyConstraints();
        // Credentials are env-configurable via SUPERADMIN_* / ADMIN_*
        // environment variables. Set these for any deployment beyond
        // local development.
        DB::table('admins')->insert([
            'name' => 'Super Administrator',
            'username' => env('SUPERADMIN_USERNAME', 'superadmin'),
            'email' => env('SUPERADMIN_EMAIL', 'superadmin@example.com'),
            'password' => bcrypt(env('SUPERADMIN_PASSWORD', 'superadmin1@')),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('admins')->insert([
            'name' => 'Administrator',
            'username' => env('ADMIN_USERNAME', 'administrator'),
            'email' => env('ADMIN_EMAIL', 'administrator@example.com'),
            'password' => bcrypt(env('ADMIN_PASSWORD', 'administrator1@')),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
