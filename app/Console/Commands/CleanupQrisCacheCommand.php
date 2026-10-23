<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupQrisCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qris:cleanup-cache {--hours=24 : Delete files older than this many hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old dynamic QRIS cached image files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $threshold = Carbon::now()->subHours($hours)->timestamp;
        
        $this->info("Cleaning up QRIS cache files older than {$hours} hours...");
        
        $disk = Storage::disk('public');
        $cachePath = 'qr_cache';
        
        if (!$disk->exists($cachePath)) {
            $this->warn('Cache directory does not exist.');
            return 0;
        }
        
        $files = $disk->files($cachePath);
        $deletedCount = 0;
        $totalSize = 0;
        
        foreach ($files as $file) {
            // Only process dynamic QRIS cache files
            if (!str_contains($file, 'qris_dynamic_')) {
                continue;
            }
            
            $lastModified = $disk->lastModified($file);
            
            if ($lastModified < $threshold) {
                $size = $disk->size($file);
                $totalSize += $size;
                
                $disk->delete($file);
                $deletedCount++;
                
                $this->line("Deleted: {$file} (" . $this->formatBytes($size) . ")");
            }
        }
        
        if ($deletedCount > 0) {
            $this->info("\nDeleted {$deletedCount} cached file(s), freed " . $this->formatBytes($totalSize));
        } else {
            $this->info('No old cache files found.');
        }
        
        return 0;
    }
    
    /**
     * Format bytes to human-readable size
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
