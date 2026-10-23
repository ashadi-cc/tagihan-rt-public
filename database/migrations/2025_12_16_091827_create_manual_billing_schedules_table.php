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
        Schema::create('manual_billing_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billing_id');
            $table->json('user_ids');
            $table->date('scheduled_at');
            $table->boolean('is_generated')->default(false);
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('billing_id')->references('id')->on('billings')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Index for performance
            $table->index(['scheduled_at', 'is_generated']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_billing_schedules');
    }
};
