<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBalanceStatementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('balance_statements', function (Blueprint $table) {
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
        Schema::dropIfExists('balance_statements');
    }
}
