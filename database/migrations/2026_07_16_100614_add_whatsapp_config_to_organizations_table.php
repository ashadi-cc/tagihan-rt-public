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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('whatsapp_admin_group_id')->nullable();
            $table->string('whatsapp_member_group_id')->nullable();
            $table->string('whatsapp_notif_startup_number')->nullable();
            $table->boolean('whatsapp_enable_registration')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_admin_group_id',
                'whatsapp_member_group_id',
                'whatsapp_notif_startup_number',
                'whatsapp_enable_registration',
            ]);
        });
    }
};
