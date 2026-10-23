<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLogsToBillingUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('billing_users', function (Blueprint $table) {
            $table->string('changed_by', 20)->default('')->index();
            $table->integer('changed_user_id')->unsigned()->default(0)->index();
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
            $table->dropColumn('changed_by'); 
            $table->dropColumn('changed_user_id');
        });
    }
}
