<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class QrisApiService
{
    private $baseUrl;
    private $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.qris_api.url', 'http://localhost:54100');
        $this->apiKey = config('services.qris_api.key');

        if (empty($this->apiKey)) {
            throw new Exception('QRIS API key not configured. Please set QRIS_API_KEY in .env file.');
        }
    }

    /**
     * Extract QRIS text from uploaded image file
     *
     * @param string $imagePath Path to the image file
     * @return array Response containing qrisText and merchantName
     * @throws Exception
     */
    public function extractQrisFromImage($imagePath)
    {
        try {
            if (!file_exists($imagePath)) {
                throw new Exception("Image file not found: {$imagePath}");
            }

            // Get mime type
            $mimeType = mime_content_type($imagePath);
            
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
            ])->attach(
                'image',
                file_get_contents($imagePath),
                basename($imagePath),
                ['Content-Type' => $mimeType]
            )->post("{$this->baseUrl}/api/qris/extract");

            if (!$response->successful()) {
                $errorMessage = $response->json('message') ?? 'Unknown error';
                Log::error('QRIS API extract failed', [
                    'status' => $response->status(),
                    'message' => $errorMessage,
                    'response' => $response->body(),
                    'mime_type' => $mimeType,
                ]);
                throw new Exception("QRIS API error: {$errorMessage}");
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                throw new Exception($data['message'] ?? 'Failed to extract QRIS');
            }

            return $data['data'];
        } catch (Exception $e) {
            Log::error('QRIS extraction error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate dynamic QRIS from static QRIS text
     *
     * @param string $staticQris Static QRIS text
     * @param float|null $amount Payment amount
     * @param string|null $feeType Fee type: "Persentase" or "Rupiah"
     * @param float|null $feeValue Fee value
     * @return array Response containing dynamicQris, merchantName, amount, etc.
     * @throws Exception
     */
    public function generateDynamicQris($staticQris, $amount = null, $feeType = null, $feeValue = null)
    {
        try {
            $payload = [
                'staticQris' => $staticQris,
            ];

            if ($amount !== null) {
                $payload['amount'] = $amount;
            }

            if ($feeType !== null) {
                $payload['feeType'] = $feeType;
            }

            if ($feeValue !== null) {
                $payload['feeValue'] = $feeValue;
            }

            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/api/qris/generate", $payload);

            if (!$response->successful()) {
                $errorMessage = $response->json('message') ?? 'Unknown error';
                Log::error('QRIS API generate failed', [
                    'status' => $response->status(),
                    'message' => $errorMessage,
                    'response' => $response->body()
                ]);
                throw new Exception("QRIS API error: {$errorMessage}");
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                throw new Exception($data['message'] ?? 'Failed to generate dynamic QRIS');
            }

            return $data['data'];
        } catch (Exception $e) {
            Log::error('QRIS generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if QRIS API is healthy
     *
     * @return bool
     */
    public function healthCheck()
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/qris/health");
            return $response->successful() && $response->json('success') === true;
        } catch (Exception $e) {
            Log::warning('QRIS API health check failed: ' . $e->getMessage());
            return false;
        }
    }
}
