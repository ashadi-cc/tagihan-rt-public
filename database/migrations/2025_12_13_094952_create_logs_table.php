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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->text('description');
            $table->string('url_link')->nullable();
            $table->morphs('loggable'); // creates loggable_id and loggable_type
            $table->timestamps();

            $table->index('username'); // if filtering by username
            $table->index('created_at'); // if sorting by date frequently
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
