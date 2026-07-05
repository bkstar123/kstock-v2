<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('symbols', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 20)->unique();      // ticker, e.g. FPT
            $table->string('name')->nullable();         // company / instrument name
            $table->string('exchange', 20)->nullable(); // HOSE / HNX / UPCOM
            $table->string('company_type', 40)->nullable();
            $table->string('industry_code', 40)->nullable();
            $table->string('icb_code', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('exchange');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('symbols');
    }
};
