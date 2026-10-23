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
        Schema::table('billings', function (Blueprint $table) {
            // Add new fields
            $table->unsignedBigInteger('organization_id')->nullable()->after('id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->text('description')->nullable()->after('name');
            $table->string('type')->nullable()->after('description')->comment('option should be user, global');
            $table->string('billing_type')->nullable()->after('type')->comment('option rutin_perbulan, manual');
            $table->boolean('disabled')->default(false)->after('billing_type');
            
            // Remove auto_per_month field
            $table->dropColumn('auto_per_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['organization_id']);
            
            // Remove added fields
            $table->dropColumn(['organization_id', 'description', 'type', 'billing_type', 'disabled']);
            
            // Restore auto_per_month field
            $table->boolean('auto_per_month')->default(false);
        });
    }
};
