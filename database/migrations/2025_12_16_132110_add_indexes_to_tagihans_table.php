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
        Schema::table('tagihans', function (Blueprint $table) {
            // Composite index for duplicate checking (most common query)
            $table->index(['organization_id', 'billing_id', 'user_id', 'billing_generated_date'], 'idx_tagihan_duplicate_check');
            
            // Individual indexes for common queries
            $table->index('billing_id', 'idx_tagihan_billing_id');
            $table->index('billing_type', 'idx_tagihan_billing_type');
            $table->index('billing_generated_date', 'idx_tagihan_generated_date');
            $table->index('user_id', 'idx_tagihan_user_id');
            $table->index('organization_id', 'idx_tagihan_organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tagihans', function (Blueprint $table) {
            $table->dropIndex('idx_tagihan_duplicate_check');
            $table->dropIndex('idx_tagihan_billing_type');
            $table->dropIndex('idx_tagihan_billing_id');
            $table->dropIndex('idx_tagihan_generated_date');
            $table->dropIndex('idx_tagihan_user_id');
            $table->dropIndex('idx_tagihan_organization_id');
        });
    }
};
