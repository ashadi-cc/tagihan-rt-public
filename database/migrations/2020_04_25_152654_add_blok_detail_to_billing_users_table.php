<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBlokDetailToBillingUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('billing_users', function (Blueprint $table) {
            $table->string('blok_name', 10)->default('')->index(); 
            $table->integer('blok_number')->unsigned()->default(0)->index(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('billing_users', function (Blueprint $table) {
            $table->dropColumn('blok_name');
            $table->dropColumn('blok_number');
        });
    }
}
