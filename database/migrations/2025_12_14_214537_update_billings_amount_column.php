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
            // Change amount column from float(8,2) to decimal(15,2)
            // This supports values up to 9,999,999,999,999.99 (almost 10 trillion)
            $table->decimal('amount', 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            // Revert back to original float(8,2)
            $table->float('amount', 8, 2)->default(0)->change();
        });
    }
};
