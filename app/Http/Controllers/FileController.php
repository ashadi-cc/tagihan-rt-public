<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleCloudStorage;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    protected $gcs;

    public function __construct(GoogleCloudStorage $gcs)
    {
        $this->gcs = $gcs;
    }

    /**
     * Display/serve a file
     * Supports both GCS and local storage
     */
    public function show($driver, Request $request)
    {
        try {
            // Decode the path
            $path = urldecode($request->path);
            
            if ($driver === 'gcs') {
                // Serve from Google Cloud Storage
                return $this->gcs->response($path);
            } elseif ($driver === 'local') {
                // Serve from local storage
                if (!Storage::disk('local')->exists($path)) {
                    return response('File not found', 404);
                }
                
                $file = Storage::disk('local')->get($path);
                $mimeType = Storage::disk('local')->mimeType($path);
                
                return response($file, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', 'inline; filename="' . basename($path) . '"');
            }
            
            return response('Invalid driver', 400);
            
        } catch (\Exception $e) {
            \Log::error('File Serve Error: ' . $e->getMessage());
            return response('Error serving file', 500);
        }
    }
}
