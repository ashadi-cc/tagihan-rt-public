<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // Organization reference
            $table->unsignedBigInteger('organization_id');
            
            // Payment reference (nullable - may not be linked to a payment method)
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('payment_method')->nullable(); // bank_transfer, cash, etc
            $table->string('payment_name')->nullable(); // copy from payment name
            $table->json('payment_metadata')->nullable(); // json data from payment
            
            // Payment details
            $table->string('payment_type'); // current_billing, previous_billing, deposit, etc
            $table->string('payment_type_metadata')->nullable(); // additional metadata about payment type
            $table->decimal('payment_amount', 15, 2);
            $table->date('payment_date'); // only date, exclude time
            
            // User reference (payer)
            $table->unsignedBigInteger('user_id');
            $table->string('user_name'); // copy from user.name
            $table->string('user_blok')->nullable(); // copy from user.blok
            
            // Creator reference
            $table->unsignedBigInteger('created_by_user_id');
            $table->string('created_by_user_name'); // copy from user.name who create this record
            
            // Verification details
            $table->string('status')->default('pending'); // pending, verified, rejected, etc
            $table->unsignedBigInteger('verified_by_user_id')->nullable(); // user who verify this payment
            $table->string('verified_by_user_name')->nullable(); // copy from user.name who verify this payment
            $table->timestamp('verified_at')->nullable(); // datetime when this payment is verified
            
            // Additional information
            $table->text('notes')->nullable(); // additional notes about the payment
            
            // File storage
            $table->string('file_driver')->nullable(); // driver for file storage (local, s3, etc)
            $table->string('file_path')->nullable(); // path to the payment proof file
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better query performance
            $table->index('organization_id');
            $table->index('payment_id');
            $table->index('user_id');
            $table->index('created_by_user_id');
            $table->index('verified_by_user_id');
            $table->index('status');
            $table->index('payment_date');
            $table->index('payment_type');
            $table->index('payment_method');
            $table->index('payment_type_metadata');
            $table->index(['organization_id', 'user_id']); // composite index for common queries
            $table->index(['organization_id', 'payment_date']); // composite index for date range queries
            $table->index(['organization_id', 'status']); // composite index for status filtering
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pembayarans');
    }
};
