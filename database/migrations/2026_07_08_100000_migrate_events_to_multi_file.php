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
        Schema::table('events', function (Blueprint $table) {
            $table->json('files')->nullable()->after('persistent_event'); // Array of file paths with driver info
        });

        // Backfill existing single file into the new files column
        DB::table('events')->whereNotNull('file_path')->orderBy('id')->each(function ($event) {
            DB::table('events')->where('id', $event->id)->update([
                'files' => json_encode([[
                    'driver' => $event->file_driver,
                    'path' => $event->file_path,
                    'original_name' => basename($event->file_path),
                ]]),
            ]);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['file_driver', 'file_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('file_driver')->nullable()->after('persistent_event');
            $table->string('file_path')->nullable()->after('file_driver');
        });

        DB::table('events')->whereNotNull('files')->orderBy('id')->each(function ($event) {
            $files = json_decode($event->files, true);
            if (!empty($files[0])) {
                DB::table('events')->where('id', $event->id)->update([
                    'file_driver' => $files[0]['driver'] ?? null,
                    'file_path' => $files[0]['path'] ?? null,
                ]);
            }
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('files');
        });
    }
};
