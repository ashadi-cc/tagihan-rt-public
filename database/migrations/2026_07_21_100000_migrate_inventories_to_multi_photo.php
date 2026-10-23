<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('description'); // Array of photo paths with driver info
        });

        // Backfill existing single photo into the new photos column
        DB::table('inventories')->whereNotNull('file_path')->orderBy('id')->each(function ($inventory) {
            DB::table('inventories')->where('id', $inventory->id)->update([
                'photos' => json_encode([[
                    'driver' => $inventory->file_driver,
                    'path' => $inventory->file_path,
                    'original_name' => basename($inventory->file_path),
                ]]),
            ]);
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['file_driver', 'file_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('file_driver')->nullable()->after('description');
            $table->string('file_path')->nullable()->after('file_driver');
        });

        DB::table('inventories')->whereNotNull('photos')->orderBy('id')->each(function ($inventory) {
            $photos = json_decode($inventory->photos, true);
            if (!empty($photos[0])) {
                DB::table('inventories')->where('id', $inventory->id)->update([
                    'file_driver' => $photos[0]['driver'] ?? null,
                    'file_path' => $photos[0]['path'] ?? null,
                ]);
            }
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
