<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAnalysisReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('analysis_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->longText('content');
            $table->bigInteger('financial_statement_id')->unsigned()->index();
            $table->timestamps();
            $table->foreign('financial_statement_id')->references('id')->on('financial_statements')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analysis_reports');
    }
}
