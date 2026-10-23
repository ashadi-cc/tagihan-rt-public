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
        Schema::table('pembayarans', function (Blueprint $table) {
            // Allow NULL for created_by_user_id and created_by_user_name
            // This is needed for system-generated pembayaran records (e.g., deposit_usage)
            $table->unsignedBigInteger('created_by_user_id')->nullable()->change();
            $table->string('created_by_user_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            // Revert back to NOT NULL (if needed)
            $table->unsignedBigInteger('created_by_user_id')->nullable(false)->change();
            $table->string('created_by_user_name')->nullable(false)->change();
        });
    }
};
