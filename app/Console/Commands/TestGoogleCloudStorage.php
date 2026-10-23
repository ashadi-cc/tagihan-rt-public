<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleCloudStorage;

class TestGoogleCloudStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gcs:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Google Cloud Storage by uploading a text file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing Google Cloud Storage connection...');
        
        try {
            $gcs = app(GoogleCloudStorage::class);
            
            // Create test file
            $testContent = "Test file uploaded at: " . now()->format('Y-m-d H:i:s');
            $fileName = 'test-' . time() . '.txt';
            $path = 'test/' . $fileName;
            
            $this->info("Uploading: {$fileName}");
            
            // Upload
            $result = $gcs->put($path, $testContent);
            
            if (!$result) {
                $this->error("✗ Upload failed");
                return 1;
            }
            
            $this->info("✓ Upload successful");
            
            // Verify
            if ($gcs->exists($path)) {
                $this->info("✓ File verified in bucket");
                $this->info("✓ Size: " . $gcs->size($path) . " bytes");
                $this->info("✓ URL: " . $gcs->url($path));
                
                // Read file content
                $content = $gcs->get($path);
                $this->info("✓ Content: " . $content);
                
                $this->info("\n✓ Google Cloud Storage is working correctly!");
                return 0;
            } else {
                $this->error("✗ File not found after upload");
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
            return 1;
        }
    }
}
