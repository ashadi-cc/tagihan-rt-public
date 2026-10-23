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
        Schema::create('tagihans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('billing_id')->nullable();
            $table->string('billing_name');
            $table->string('billing_type');
            $table->decimal('billing_amount', 15, 2);
            $table->integer('billing_priority')->default(0);
            $table->decimal('payment_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->string('created_by_user_name')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('billing_id')->references('id')->on('billings')->onDelete('set null');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihans');
    }
};
