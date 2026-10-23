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
        Schema::dropIfExists('billing_pembayaran');

        Schema::create('tagihan_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tagihan_id');
            $table->unsignedBigInteger('pembayaran_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('tagihan_id')->references('id')->on('tagihans')->onDelete('cascade');
            $table->foreign('pembayaran_id')->references('id')->on('pembayarans')->onDelete('cascade');

            // Unique constraint to prevent duplicate entries
            $table->unique(['tagihan_id', 'pembayaran_id']);
            // Indexes for better query performance
            $table->index('tagihan_id');
            $table->index('pembayaran_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan_pembayaran');
    }
};
