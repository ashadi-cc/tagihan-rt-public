<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rt_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('billing_id')->default(0)->after('organization_id');
            $table->index('billing_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rt_transactions', function (Blueprint $table) {
            $table->dropIndex(['billing_id']);
            $table->dropColumn('billing_id');
        });
    }
};
