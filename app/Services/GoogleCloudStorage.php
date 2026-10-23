<?php

namespace App\Services;

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\Bucket;

class GoogleCloudStorage
{
    protected $storageClient;
    protected $bucket;
    protected $bucketName;
    protected $pathPrefix;

    public function __construct()
    {
        $this->storageClient = new StorageClient([
            'projectId' => config('filesystems.disks.gcs.project_id'),
            'keyFilePath' => config('filesystems.disks.gcs.key_file'),
        ]);

        $this->bucketName = config('filesystems.disks.gcs.bucket');
        $this->pathPrefix = config('filesystems.disks.gcs.path_prefix', '');
        $this->bucket = $this->storageClient->bucket($this->bucketName);
    }

    /**
     * Get full path with prefix
     */
    protected function getFullPath($path)
    {
        if (empty($this->pathPrefix)) {
            return $path;
        }
        
        // Remove leading slash from path if exists
        $path = ltrim($path, '/');
        
        // Add prefix with trailing slash if not exists
        $prefix = rtrim($this->pathPrefix, '/') . '/';
        
        return $prefix . $path;
    }

    /**
     * Store a file
     */
    public function put($path, $contents, $options = [])
    {
        try {
            $fullPath = $this->getFullPath($path);
            
            $object = $this->bucket->upload($contents, array_merge([
                'name' => $fullPath,
            ], $options));

            return $object ? true : false;
        } catch (\Exception $e) {
            \Log::error('GCS Upload Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Store a file from uploaded file
     */
    public function putFile($path, $file, $options = [])
    {
        try {
            $fileStream = fopen($file->getRealPath(), 'r');
            
            // Use custom name from options if provided, otherwise use original filename
            $fileName = $options['name'] ?? ($path . '/' . $file->getClientOriginalName());
            unset($options['name']); // Remove from options to avoid duplication
            
            $fullPath = $this->getFullPath($fileName);
            
            $object = $this->bucket->upload($fileStream, array_merge([
                'name' => $fullPath,
            ], $options));

            fclose($fileStream);

            return $object->name();
        } catch (\Exception $e) {
            \Log::error('GCS Upload File Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get file contents (works for text and binary files like images)
     * Note: Loads entire file into memory
     */
    public function get($path)
    {
        try {
            $fullPath = $this->getFullPath($path);
            $object = $this->bucket->object($fullPath);
            return $object->downloadAsString();
        } catch (\Exception $e) {
            \Log::error('GCS Get Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download file to local storage
     * Better for large files
     */
    public function download($path, $destination)
    {
        try {
            $fullPath = $this->getFullPath($path);
            $object = $this->bucket->object($fullPath);
            $object->downloadToFile($destination);
            return true;
        } catch (\Exception $e) {
            \Log::error('GCS Download Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a stream resource for the file
     * Memory efficient for large files
     */
    public function readStream($path)
    {
        try {
            $fullPath = $this->getFullPath($path);
            $object = $this->bucket->object($fullPath);
            return $object->downloadAsStream()->detach();
        } catch (\Exception $e) {
            \Log::error('GCS Stream Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Return a response for downloading/displaying file in browser
     */
    public function response($path, $filename = null, $headers = [])
    {
        try {
            $fullPath = $this->getFullPath($path);
            $object = $this->bucket->object($fullPath);
            $content = $object->downloadAsString();
            $info = $object->info();
            
            $filename = $filename ?? basename($path);
            $mimeType = $info['contentType'] ?? 'application/octet-stream';
            
            return response($content, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->withHeaders($headers);
        } catch (\Exception $e) {
            \Log::error('GCS Response Error: ' . $e->getMessage());
            return response('File not found', 404);
        }
    }

    /**
     * Check if file exists
     */
    public function exists($path)
    {
        try {
            $fullPath = $this->getFullPath($path);
            $object = $this->bucket->object($fullPath);
            return $object->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete a file
     */
    public function delete($path)
    {
        try {
            $fullPath = $this->getFullPath($path);
            $object = $this->bucket->object($fullPath);
            $object->delete();
            return true;
        } catch (\Exception $e) {
            \Log::error('GCS Delete Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get public URL for a file
     */
    public function url($path)
    {
        $fullPath = $this->getFullPath($path);
        return sprintf(
            'https://storage.googleapis.com/%s/%s',
            $this->bucketName,
            $fullPath
        );
    }

    /**
     * List files in a directory
     */
    public function files($directory = '')
    {
        try {
            $fullPath = $this->getFullPath($directory);
            $objects = $this->bucket->objects([
                'prefix' => $fullPath,
            ]);

            $files = [];
            foreach ($objects as $object) {
                $files[] = $object->name();
            }

            return $files;
        } catch (\Exception $e) {
            \Log::error('GCS List Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get file size
     */
    public function size($path)
    {
        try {
            $fullPath = $this->getFullPath($path);
            $object = $this->bucket->object($fullPath);
            $info = $object->info();
            return $info['size'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get last modified time
     */
    public function lastModified($path)
    {
        try {
            $fullPath = $this->getFullPath($path);
            $object = $this->bucket->object($fullPath);
            $info = $object->info();
            return isset($info['updated']) ? strtotime($info['updated']) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
