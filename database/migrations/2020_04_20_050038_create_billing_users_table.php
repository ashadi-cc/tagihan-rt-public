<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillingUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned()->index();
            $table->bigInteger('billing_id')->unsigned()->index();
            $table->string('user_blok', 12)->index();
            $table->string('billing_name', 100)->index(); 
            $table->integer('month')->unsigned()->index(); 
            $table->integer('year')->unsigned()->index();
            $table->float('amount', 10, 2)->default(0);
            $table->string('status', 10)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_users');
    }
}
