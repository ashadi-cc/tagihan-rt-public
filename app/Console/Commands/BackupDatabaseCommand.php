<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleCloudStorage;
use Carbon\Carbon;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup 
                            {--keep-local : Keep the local backup file after upload}
                            {--folder=backups : GCS folder to store backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup database to Google Cloud Storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = microtime(true);
        $this->info('Starting database backup...');

        try {
            // Get database configuration
            $connection = config('database.default');
            $dbConfig = config("database.connections.{$connection}");

            if (!$dbConfig) {
                $this->error("Database connection '{$connection}' not found!");
                return 1;
            }

            // Generate backup filename with timestamp
            $timestamp = Carbon::now()->format('Y-m-d_His');
            $dbName = $dbConfig['database'];
            $filename = "backup_{$dbName}_{$timestamp}.sql.gz";
            $tmpPath = sys_get_temp_dir() . '/' . $filename;

            // Create database dump based on driver
            $this->info("Creating database dump for {$connection} ({$dbName})...");
            
            if ($dbConfig['driver'] === 'mysql') {
                $success = $this->backupMysql($dbConfig, $tmpPath);
            } elseif ($dbConfig['driver'] === 'pgsql') {
                $success = $this->backupPostgres($dbConfig, $tmpPath);
            } else {
                $this->error("Unsupported database driver: {$dbConfig['driver']}");
                return 1;
            }

            if (!$success) {
                $this->error('Failed to create database backup!');
                $this->cleanupFile($tmpPath);
                return 1;
            }

            // Get file size for reporting
            $fileSize = filesize($tmpPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            $this->info("Backup file created: {$fileSizeMB} MB");

            // Upload to Google Cloud Storage
            $this->info('Uploading to Google Cloud Storage...');
            $gcsFolder = $this->option('folder');
            $gcsPath = trim($gcsFolder, '/') . '/' . $filename;
            
            $uploaded = $this->uploadToGCS($tmpPath, $gcsPath);

            if (!$uploaded) {
                $this->error('Failed to upload backup to Google Cloud Storage!');
                $this->cleanupFile($tmpPath);
                return 1;
            }

            $this->info("✓ Backup uploaded to GCS: {$gcsPath}");

            // Cleanup local file unless --keep-local is specified
            if (!$this->option('keep-local')) {
                $this->cleanupFile($tmpPath);
                $this->info('Local backup file removed');
            } else {
                $this->info("Local backup kept at: {$tmpPath}");
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✓ Backup completed successfully in {$duration} seconds");

            return 0;

        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            \Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Cleanup on error
            if (isset($tmpPath) && file_exists($tmpPath)) {
                $this->cleanupFile($tmpPath);
            }
            
            return 1;
        }
    }

    /**
     * Backup MySQL database using mysqldump with gzip compression
     * Streams directly to compressed file to minimize memory usage
     */
    protected function backupMysql(array $config, string $outputPath): bool
    {
        $host = $config['host'];
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        // Use MYSQL_PWD environment variable for password (safer than command line)
        $envVars = [
            'MYSQL_PWD' => $password,
        ];

        // Build mysqldump command with gzip compression
        // Using pipes to stream mysqldump directly to gzip (no intermediate file)
        // add --skip-column-statistics for MySQL 8+
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --single-transaction --quick --lock-tables=false --routines --triggers %s | gzip -c > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        // Build full command with environment variables
        $envString = '';
        foreach ($envVars as $key => $value) {
            $envString .= sprintf('%s=%s ', $key, escapeshellarg($value));
        }
        $fullCommand = $envString . $command;

        // Debug: Show the command (with password masked)
        // $this->info('Executing: MYSQL_PWD=*** ' . $command);

        // Execute command
        $output = [];
        $returnCode = 0;
        exec($fullCommand . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('mysqldump failed: ' . implode("\n", $output));
            \Log::error('mysqldump command failed', [
                'output' => $output,
                'return_code' => $returnCode,
            ]);
            return false;
        }

        // Verify the output file exists and is a valid gzip file
        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            $this->error('Backup file was not created or is empty');
            return false;
        }

        // Verify it's a valid gzip file (check magic bytes)
        $handle = fopen($outputPath, 'rb');
        $header = fread($handle, 2);
        fclose($handle);
        
        if ($header !== "\x1f\x8b") {
            $this->error('Created file is not a valid gzip file');
            return false;
        }

        return true;
    }

    /**
     * Backup PostgreSQL database using pg_dump with gzip compression
     * Streams directly to compressed file to minimize memory usage
     */
    protected function backupPostgres(array $config, string $outputPath): bool
    {
        $host = $config['host'];
        $port = $config['port'] ?? 5432;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        // Set PGPASSWORD environment variable for password
        putenv("PGPASSWORD={$password}");

        // Build pg_dump command with gzip compression
        $command = sprintf(
            'pg_dump --host=%s --port=%s --username=%s --format=plain --no-owner --no-acl %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        // Execute command
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        // Clear password from environment
        putenv('PGPASSWORD');

        if ($returnCode !== 0) {
            $this->error('pg_dump failed: ' . implode("\n", $output));
            \Log::error('pg_dump command failed', [
                'output' => $output,
                'return_code' => $returnCode,
            ]);
            return false;
        }

        return file_exists($outputPath) && filesize($outputPath) > 0;
    }

    /**
     * Upload file to Google Cloud Storage using streaming
     * Memory efficient - reads file in chunks
     */
    protected function uploadToGCS(string $filePath, string $gcsPath): bool
    {
        try {
            $gcs = app(GoogleCloudStorage::class);
            
            // Open file stream for reading
            $fileStream = fopen($filePath, 'r');
            
            if (!$fileStream) {
                $this->error('Failed to open file for reading');
                return false;
            }

            // Upload using stream (more memory efficient than loading entire file)
            // Note: GCS library consumes and closes the stream internally
            $result = $gcs->put($gcsPath, $fileStream);

            return $result;

        } catch (\Exception $e) {
            $this->error('GCS upload error: ' . $e->getMessage());
            \Log::error('GCS backup upload failed', [
                'error' => $e->getMessage(),
                'file' => $filePath,
                'gcs_path' => $gcsPath,
            ]);
            return false;
        }
    }

    /**
     * Clean up temporary file
     */
    protected function cleanupFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}
