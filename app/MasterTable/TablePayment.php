<?php 

namespace App\MasterTable; 

use App\Models\Payment; 
use App\Services\QrisApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;


class TablePayment implements TableInterface
{
    use Table;

    public function setHeaderCaption() :array 
    {
        return [
            'Nama',
            'Tipe',
            'Detail',
            'Aktif',
            'Uang Masuk(Bulan Ini)',
        ];
    }

    public function setHeaderData() : array 
    {
        return [
            'id',
            'name',
            'payment_type',
            'metadata',
            'active',
            'total_verified_current_month',
        ];
    }

    public function getData(Request $request)
    {
        $query = Payment::where('organization_id', auth()->user()->organization_id)->orderBy('name', 'asc');
        
        if (trim($request->get('q')) != "") {
            $search = '%'. trim($request->get('q')) . '%'; 
            $query->where('name', 'like', $search);
        }

        $data = $query->orderBy('id')->get()->toArray();

        return array_map(function($value)
        {
            $payment = Payment::find($value['id']);
            $totalVerifiedCurrentMonth = $payment ? $payment->totalVerifiedAmount(date('m'), date('Y')) : 0;
            $urlTotalVerifiedCurrentMonth = route('admin.report.payment', [
                'payment_id' => $value['id'],
                'month' => date('m'),
                'year' => date('Y'),
            ]);
            $totalVerifiedCurrentMonthColumn = '<a href="' . $urlTotalVerifiedCurrentMonth . '" class="text-success font-weight-bold">Rp ' . number_format($totalVerifiedCurrentMonth, 0, ',', '.') . ' <i class="fa fa-arrow-right"></i></a>';
            return [
                'id' => $value['id'],
                'name' => $value['name'], 
                'payment_type' => $value['payment_type'], 
                'metadata' => $this->formatMetadata($value['metadata']), 
                'active' => $value['disabled'] ? '<span class="badge badge-danger">Tidak</span>' : '<span class="badge badge-success">Ya</span>',
                'total_verified_current_month' => $totalVerifiedCurrentMonthColumn,
            ];

        }, $data);
    }

    public function delete($paymentId)
    {
        try {
            $payment = Payment::findOrFail($paymentId);
            
            // Clean up QR files if this is a QR payment
            if ($payment->payment_type === 'qr_payment' && $payment->metadata) {
                $this->deleteQrFiles($payment->metadata);
            }
            
            $payment->delete(); 

            return true;
        } catch(\Exception $e) {
            Log::error($e->getMessage());

            return false;
        }
    }

    public function edit(Request $request, $idRecord)
    {
        $organization = auth()->user()->organization;
        $payment = Payment::where('organization_id', $organization->id)->findOrFail($idRecord);

        // Track changes for logging
        $changes = [];

        $validate = [
            'nama' => 'required',
            'payment_type' => 'required|string',
        ];

        // Additional validation for bank_account type
        if ($request->payment_type === 'bank_account') {
            $validate['bank_name'] = 'required|string';
            $validate['account_name'] = 'required|string';
            $validate['account_number'] = 'required|string';
        }
        
        // Additional validation for qr_payment type
        if ($request->payment_type === 'qr_payment') {
            // Only require QR image if it's a new upload or switching to QR payment
            if ($payment->payment_type !== 'qr_payment' || $request->hasFile('qr_image')) {
                $validate['qr_image'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
            }
            
            // If dynamic QRIS is checked
            if ($request->has('is_dynamic_qris')) {
                $validate['fee_type'] = 'required|in:rupiah,percent';
                $validate['fee_value'] = 'required|numeric|min:0';
            }
        }

        $request->validate($validate);

        if ($payment->name != $request->nama) {
            $changes[] = "Nama diubah dari '{$payment->name}' menjadi '{$request->nama}'";
        }

        if ($payment->payment_type != $request->payment_type) {
            $oldType = $payment->payment_type ?: '(kosong)';
            $changes[] = "Tipe pembayaran diubah dari '{$oldType}' menjadi '{$request->payment_type}'";
        }

        if ($payment->description != $request->description) {
            $changes[] = "Deskripsi diubah";
        }

        $payment->name = $request->nama;
        $payment->payment_type = $request->payment_type;
        $payment->description = $request->description;
        $payment->disabled = $request->has('aktif') ? false : true;

        // Handle metadata based on payment type
        if ($request->payment_type === 'bank_account') {
            $oldMetadata = is_array($payment->metadata) ? json_encode($payment->metadata) : '(kosong)';

            $metadata = [
                'bank_name' => $request->bank_name,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
            ];

            $newMetadata = json_encode($metadata);
            if ($oldMetadata != $newMetadata) {
                $changes[] = "Metadata diperbarui";
            }

            $payment->metadata = $metadata;
        } elseif ($request->payment_type === 'qr_payment') {
            // If new QR image is uploaded, delete old files and upload new one
            if ($request->hasFile('qr_image')) {
                // Delete old QR files if exists
                if (isset($payment->metadata['qr_image_path'])) {
                    $this->deleteQrFiles($payment->metadata);
                }
                
                // Upload new QR image
                try {
                    $metadata = $this->handleQrPaymentUpload($request);
                    $changes[] = "QR Code diperbarui";
                } catch (\Exception $e) {
                    return redirect()->back()->withErrors(['qr_image' => $e->getMessage()])->withInput();
                }
            } else {
                // Keep existing QR files, only update dynamic QRIS settings
                $metadata = $payment->metadata ?? [];
                
                // Update dynamic QRIS settings
                if ($request->has('is_dynamic_qris')) {
                    $metadata['is_dynamic_qris'] = true;
                    $metadata['fee_type'] = $request->fee_type;
                    $metadata['fee_value'] = $request->fee_value;
                    $changes[] = "Dynamic QRIS diaktifkan";
                } else {
                    $metadata['is_dynamic_qris'] = false;
                    unset($metadata['fee_type']);
                    unset($metadata['fee_value']);
                    $changes[] = "Dynamic QRIS dinonaktifkan";
                }
            }
            
            $payment->metadata = $metadata;
        } else {
            $payment->metadata = null;
        }

        $payment->save();

        // Create log if there are changes
        if (!empty($changes)) {
            $description = "Data channel pembayaran '{$payment->name}' diupdate:\n" . implode("\n", $changes);
            $payment->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => $description,
                'url_link' => '/admin/master/payment/edit/' . $payment->id,
            ]);
        }
    }

    public function create(Request $request)
    {
        $organization = auth()->user()->organization;

        $validate = [
            'nama' => 'required',
            'payment_type' => 'required|string',
        ];

        // Additional validation for bank_account type
        if ($request->payment_type === 'bank_account') {
            $validate['bank_name'] = 'required|string';
            $validate['account_name'] = 'required|string';
            $validate['account_number'] = 'required|string';
        }

        // Additional validation for qr_payment type
        if ($request->payment_type === 'qr_payment') {
            $validate['qr_image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
            
            // If dynamic QRIS is checked
            if ($request->has('is_dynamic_qris')) {
                $validate['fee_type'] = 'required|in:rupiah,percent';
                $validate['fee_value'] = 'required|numeric|min:0';
            }
        }

        $request->validate($validate);

        $payment = new Payment();
        $payment->organization_id = $organization->id;
        $payment->name = $request->nama;
        $payment->payment_type = $request->payment_type;
        $payment->description = $request->description;
        $payment->disabled = $request->has('aktif') ? false : true;

        // Handle metadata based on payment type
        if ($request->payment_type === 'bank_account') {
            $payment->metadata = [
                'bank_name' => $request->bank_name,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
            ];
        } elseif ($request->payment_type === 'qr_payment') {
            try {
                $metadata = $this->handleQrPaymentUpload($request);
                $payment->metadata = $metadata;
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['qr_image' => $e->getMessage()])->withInput();
            }
        }

        $payment->save();

        // Create log
        $payment->logs()->create([
            'organization_id' => auth()->user()->organization_id,
            'username' => auth()->user()->name,
            'description' => "Channel pembayaran baru '{$payment->name}' telah ditambahkan dengan tipe {$payment->payment_type}",
            'url_link' => '/admin/master/payment/edit/' . $payment->id,
        ]);
    }

    /**
     * Handle QR payment image upload and text extraction
     */
    private function handleQrPaymentUpload(Request $request)
    {
        $metadata = [];

        if ($request->hasFile('qr_image')) {
            $image = $request->file('qr_image');
            
            // Generate unique filename
            $filename = 'qr_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            // Store image in storage/app/public/qr_images
            $imagePath = $image->storeAs('qr_images', $filename, 'public');
            
            // Extract text from QR code
            $extractedText = $this->extractQrCodeText($image->getRealPath());
            
            // Validate if it's a valid QRIS code
            if (!$this->isValidQris($extractedText)) {
                throw new \Exception('QR Code yang diupload bukan QRIS yang valid. Silakan upload QR Code QRIS yang benar.');
            }
            
            // Store extracted text to file
            $textFilename = 'qr_' . time() . '_' . uniqid() . '.txt';
            $textPath = 'qr_texts/' . $textFilename;
            \Storage::disk('public')->put($textPath, $extractedText);
            
            // Build metadata
            $metadata['qr_image_path'] = $imagePath;
            $metadata['qr_text_path'] = $textPath;
            
            // Add dynamic QRIS info if enabled
            if ($request->has('is_dynamic_qris')) {
                $metadata['is_dynamic_qris'] = true;
                $metadata['fee_type'] = $request->fee_type;
                $metadata['fee_value'] = $request->fee_value;
            } else {
                $metadata['is_dynamic_qris'] = false;
            }
        }

        return $metadata;
    }

    /**
     * Extract text from QR code image using QRIS API
     */
    private function extractQrCodeText($imagePath)
    {
        try {
            $qrisApiService = new QrisApiService();
            $result = $qrisApiService->extractQrisFromImage($imagePath);
            
            // Return the QRIS text from the API response
            return $result['qrisText'];
            
        } catch (\Exception $e) {
            Log::error('QR Code extraction failed: ' . $e->getMessage());
            throw new \Exception('Gagal membaca QR Code. Pastikan gambar QR Code jelas dan dapat terbaca. Detail: ' . $e->getMessage());
        }
    }

    /**
     * Validate if the extracted text is a valid QRIS code
     */
    private function isValidQris($text)
    {
        if (empty($text)) {
            return false;
        }
        
        // QRIS code typically starts with specific identifiers
        // Common QRIS patterns:
        // - Starts with "00020101" or "00020201" (EMV QR format)
        // - Contains merchant account information
        // - Has specific field tags like "26", "51", "52", "53", "54", etc.
        
        // Basic validation: Check if it starts with proper EMV QR format
        if (preg_match('/^000201/', $text) || preg_match('/^000101/', $text)) {
            return true;
        }
        
        // Additional check: Contains typical QRIS identifiers
        if (strpos($text, 'ID.CO.QRIS') !== false || 
            strpos($text, 'COM.GOJEK') !== false ||
            strpos($text, 'COM.DANA') !== false ||
            strpos($text, 'COM.OVO') !== false) {
            return true;
        }
        
        // Check if it has the basic structure of EMV QR with tag-length-value format
        // QRIS codes have a specific pattern of 2-digit tags followed by 2-digit lengths
        if (preg_match('/^\d{4}/', $text) && strlen($text) > 50) {
            // Additional validation: check for common QRIS tags
            // Tag 26 (Merchant Account Information), Tag 51 (Merchant Category Code), etc.
            if (strpos($text, '26') !== false || strpos($text, '51') !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function formatMetadata($metadata)
    {
        if (is_array($metadata) && !empty($metadata)) {
            $formatted = [];
            
            // Special handling for QR payment metadata
            if (isset($metadata['qr_image_path'])) {
                // Note: We can't use route() helper here as we don't have payment ID in this context
                // So we'll just show a simple text indicator
                $formatted[] = 'QR Image: Tersedia';
                
                if (isset($metadata['is_dynamic_qris']) && $metadata['is_dynamic_qris']) {
                    $formatted[] = 'Dynamic QRIS: Ya';
                    if (isset($metadata['fee_type']) && isset($metadata['fee_value'])) {
                        $feeDisplay = $metadata['fee_type'] === 'percent' 
                            ? $metadata['fee_value'] . '%' 
                            : 'Rp ' . number_format($metadata['fee_value'], 0, ',', '.');
                        $formatted[] = 'Fee: ' . $feeDisplay;
                    }
                } else {
                    $formatted[] = 'Dynamic QRIS: Tidak';
                }
            } else {
                // Default formatting for other payment types
                foreach ($metadata as $key => $value) {
                    if (!in_array($key, ['qr_text_path'])) { // Skip internal paths
                        $formatted[] = ucwords(str_replace('_', ' ', $key)) . ': ' . $value;
                    }
                }
            }
            
            return '<small>' . implode('<br>', $formatted) . '</small>';
        }
        return '-';
    }

    /**
     * Delete QR files from storage
     */
    private function deleteQrFiles($metadata)
    {
        if (!is_array($metadata)) {
            return;
        }

        try {
            // Delete QR image
            if (isset($metadata['qr_image_path']) && Storage::disk('public')->exists($metadata['qr_image_path'])) {
                Storage::disk('public')->delete($metadata['qr_image_path']);
            }

            // Delete QR text file
            if (isset($metadata['qr_text_path']) && Storage::disk('public')->exists($metadata['qr_text_path'])) {
                Storage::disk('public')->delete($metadata['qr_text_path']);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete QR files: ' . $e->getMessage());
        }
    }

}