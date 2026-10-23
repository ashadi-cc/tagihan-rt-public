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
        Schema::table('payments', function (Blueprint $table) {
            // Remove qr_code field
            $table->dropColumn('qr_code');
            
            // Add new fields
            $table->string('payment_type')->nullable()->comment('bank_account, external_system, etc');
            $table->text('metadata')->nullable();
            $table->text('description')->nullable();
            $table->boolean('disabled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Restore qr_code field
            $table->string('qr_code')->nullable();
            
            // Drop new fields
            $table->dropColumn(['payment_type', 'metadata', 'description', 'disabled']);
        });
    }
};
