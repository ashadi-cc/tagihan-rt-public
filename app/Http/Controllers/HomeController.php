<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tagihan;
use App\Models\Payment;
use App\Models\Pembayaran;
use App\Services\GoogleCloudStorage;
use App\Services\QrisApiService;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();

        $billings = Tagihan::where('organization_id', $user->organization_id)
            ->with('billing')
            ->where('user_id', $user->id)
            ->whereIn('billing_type', ['rutin_perbulan'])
            ->whereYear('billing_generated_date', now()->year)
            ->whereMonth('billing_generated_date', now()->month)
            ->orderBy('billing_priority', 'asc')
            ->orderBy('billing_name', 'asc')
            ->get();

        $manualBillings = Tagihan::where('organization_id', $user->organization_id)
            ->with('billing')
            ->where('user_id', $user->id)
            ->where('billing_type', 'manual')
            ->whereRaw('payment_amount < billing_amount')
            ->orderBy('billing_generated_date', 'asc')
            ->get();

        $sumPreviousBilling = $user->getTunggakanTotal(true);

        $paymentChannel = Payment::where('organization_id', $user->organization_id)
            ->where('disabled', false)
            ->get();

        // Check for existing payment confirmations
        $currentMonthYear = now()->format('m-Y');
        
        $existingCurrentPayment = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'current_billing')
            ->where('payment_type_metadata', $currentMonthYear)
            ->where('status', '!=', 'rejected')
            ->orderBy('id', 'desc')
            ->first();
        
        $existingPreviousPayment = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'previous_billing')
            ->where('payment_type_metadata', $currentMonthYear)
            ->where('status', '!=', 'rejected')
            ->orderBy('id', 'desc')
            ->first();

        // Manual billing isn't month-scoped: a pending confirmation must keep blocking
        // duplicate submissions even if it crosses a month boundary before admin verifies it.
        // A resolved (verified) confirmation is still only shown for the month it was submitted.
        $existingManualPayment = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'manual_billing')
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($currentMonthYear) {
                $query->where('status', 'pending')
                    ->orWhere('payment_type_metadata', $currentMonthYear);
            })
            ->orderBy('id', 'desc')
            ->first();

        return view('home', [
            'billings' => $billings,
            'sumPreviousBilling' => $sumPreviousBilling,
            'paymentChannel' => $paymentChannel,
            'existingCurrentPayment' => $existingCurrentPayment,
            'existingPreviousPayment' => $existingPreviousPayment,
            'existingManualPayment' => $existingManualPayment,
            'manualBillings' => $manualBillings,
        ]);
    }
    
    public function confirmPayment(Request $request)
    {
        $user = auth()->user();

        $payment = Payment::where('organization_id', $user->organization_id)
            ->findOrFail($request->input('payment_id'));

        $totalCurrentTagihan = $user->getCurrentMonthTagihan(true);
        $totalTunggakan = $user->getTunggakanTotal(true);
        $totalManualBilling = $user->getManualBillingTotal();

        // Check for existing payment confirmations
        $currentMonthYear = now()->format('m-Y'); // e.g., 12-2025
        
        $existingCurrentPayment = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'current_billing')
            ->where('payment_type_metadata', $currentMonthYear)
            ->where('status', '!=', 'rejected')
            ->orderBy('id', 'desc')
            ->first();
        
        $existingPreviousPayment = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'previous_billing')
            // ->where('payment_type_metadata', $currentMonthYear)
            ->where('status', '!=', 'rejected')
            ->orderBy('id', 'desc')
            ->first();

        $existingManualPayment = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'manual_billing')
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($currentMonthYear) {
                $query->where('status', 'pending')
                    ->orWhere('payment_type_metadata', $currentMonthYear);
            })
            ->orderBy('id', 'desc')
            ->first();

        if (!$existingPreviousPayment) {
            // we need to check for any older pending previous billing payments
            $existingPreviousPayment = Pembayaran::where('organization_id', $user->organization_id)
                ->where('user_id', $user->id)
                ->where('payment_type', 'current_billing')
                ->where('payment_type_metadata', '!=', $currentMonthYear)
                ->where('status', 'pending')
                ->orderBy('id', 'desc')
                ->first();
        }

        if ($existingPreviousPayment && ($existingPreviousPayment->status != 'pending')) {
            // check if the existing previous payment is not the current year and month on payment_date field. if yes set to null
            $paymentDateMonthYear = $existingPreviousPayment->payment_date->format('m-Y');
            if ($paymentDateMonthYear != $currentMonthYear) {
                $existingPreviousPayment = null;
            }
        }

        $hasRemainingCurrentTagihan = $totalCurrentTagihan > 0;
        if ($existingCurrentPayment && $existingCurrentPayment->status == 'pending') {
            $hasRemainingCurrentTagihan = false;
        }

        $hasRemainingTunggakan = $totalTunggakan > 0;
        if ($existingPreviousPayment && $existingPreviousPayment->status == 'pending') {
            $hasRemainingTunggakan = false;
        }

        $hasRemainingManualBilling = $totalManualBilling > 0;
        if ($existingManualPayment && $existingManualPayment->status == 'pending') {
            $hasRemainingManualBilling = false;
        }
        
        $existingPendingDeposit = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'user_deposit')
            ->where('status', 'pending')
            ->first();

        return view('confirm_payment', [
            'payment' => $payment,
            'totalCurrentTagihan' => $totalCurrentTagihan,
            'totalTunggakan' => $totalTunggakan,
            'existingCurrentPayment' => $existingCurrentPayment,
            'existingPreviousPayment' => $existingPreviousPayment,
            'existingPendingDeposit' => $existingPendingDeposit,
            'hasRemainingCurrentTagihan' => $hasRemainingCurrentTagihan,
            'hasRemainingTunggakan' => $hasRemainingTunggakan,
            'hasRemainingManualBilling' => $hasRemainingManualBilling,
            'totalManualBilling' => $totalManualBilling,
            'existingManualPayment' => $existingManualPayment,
        ]);
    }
    
    public function confirmPaymentForm(Request $request)
    {
        $user = auth()->user();
        
        $payment = Payment::where('organization_id', $user->organization_id)
            ->findOrFail($request->input('payment_id'));
        
        $type = $request->input('type'); // 'current_billing' or 'previous_billing' or 'manual_billing'
        
        if ($type == 'current_billing') {
            $amountToPay = $user->getCurrentMonthTagihan(true);
            $billingTypeLabel = 'Tagihan Bulan Ini';
        } elseif ($type == 'previous_billing') {
            $amountToPay = $user->getTunggakanTotal(true);
            $billingTypeLabel = 'Tunggakan';
        } else {
            $amountToPay = $user->getManualBillingTotal(true);
            $billingTypeLabel = 'Iuran Khusus';
        }
        
        // Calculate total amount with fee for QRIS payment
        $totalAmountWithFee = $amountToPay;
        $feeAmount = 0;
        
        if ($payment->payment_type == 'qr_payment' && $payment->metadata) {
            $metadata = is_string($payment->metadata) ? json_decode($payment->metadata, true) : $payment->metadata;
            
            if (isset($metadata['is_dynamic_qris']) && $metadata['is_dynamic_qris']) {
                if ($metadata['fee_type'] == 'percent') {
                    $feeAmount = ceil(($amountToPay * $metadata['fee_value']) / 100);
                } else {
                    $feeAmount = $metadata['fee_value'];
                }
                $totalAmountWithFee = $amountToPay + $feeAmount;
            }
        }
        
        return view('confirm_payment_form', [
            'user' => $user,
            'payment' => $payment,
            'type' => $type,
            'billingTypeLabel' => $billingTypeLabel,
            'amountToPay' => $amountToPay,
            'totalAmountWithFee' => $totalAmountWithFee,
            'feeAmount' => $feeAmount,
        ]);
    }
    
    public function submitPaymentConfirmation(Request $request)
    {
        $user = auth()->user();
        
        // Get payment information first to determine validation rules
        $payment = Payment::where('organization_id', $user->organization_id)
            ->findOrFail($request->input('payment_id'));
        
        // Different validation rules for QRIS vs Bank Transfer
        if ($payment->payment_type === 'qr_payment') {
            $validated = $request->validate([
                'payment_id' => 'required|exists:payments,id',
                'type' => 'required|in:current_billing,previous_billing,manual_billing',
                'amount_to_pay' => 'required|numeric|min:0',
                'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // Required for QRIS
                'notes' => 'nullable|string|max:1000',
            ]);
            
            // For QRIS, amount_paid equals amount_to_pay (including fees)
            $validated['amount_paid'] = $validated['amount_to_pay'];
        } else {
            $validated = $request->validate([
                'payment_id' => 'required|exists:payments,id',
                'type' => 'required|in:current_billing,previous_billing,manual_billing',
                'from_bank' => 'required|string|max:255',
                'from_account_number' => 'required|string|max:255',
                'from_account_name' => 'required|string|max:255',
                'amount_paid' => 'required|numeric|min:0',
                'amount_to_pay' => 'required|numeric|min:0',
                'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'notes' => 'nullable|string|max:1000',
            ]);
            
            // Validate that amount_paid is not greater than amount_to_pay (only for bank transfer)
            // Ignore this validation. we allow users to pay more than the amount to pay, because it can be used as deposit for next billing if the amount paid exceeds the amount to pay
            // if ($validated['amount_paid'] > $validated['amount_to_pay']) {
            //     return back()->withErrors([
            //         'amount_paid' => 'Jumlah bayar tidak boleh melebihi jumlah tagihan'
            //     ])->withInput();
            // }
        }
        
        // Check for existing payment confirmations
        $currentMonthYear = now()->format('m-Y');
        
        $existingPaymentQuery = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', $validated['type'])
            ->where('status', 'pending');

        // Manual billing isn't month-scoped, so a pending confirmation must keep blocking
        // duplicate submissions even if it was submitted in a previous month.
        if ($validated['type'] !== 'manual_billing') {
            $existingPaymentQuery->where('payment_type_metadata', $currentMonthYear);
        }

        $existingPayment = $existingPaymentQuery->first();

        if ($existingPayment) {
            $typeLabel = $validated['type'] == 'current_billing' ? 'tagihan bulan ini' : ($validated['type'] == 'manual_billing' ? 'iuran khusus' : 'tunggakan');
            return back()->withErrors([
                'type' => 'Anda sudah memiliki konfirmasi pembayaran untuk ' . $typeLabel . ' yang sedang diproses (Status: ' . $existingPayment->getStatusLabel() . ')'
            ])->withInput();
        }
        
        // Prepare payment info metadata
        if ($payment->payment_type === 'qr_payment') {
            // For QRIS, only store payment method type
            $paymentInfoMetadata = [
                'payment_method' => 'qr_payment',
                'qr_payment_type' => $payment->name,
            ];
        } else {
            // For bank transfer, store bank details
            $paymentInfoMetadata = [
                'from_bank' => $validated['from_bank'],
                'from_account_number' => $validated['from_account_number'],
                'from_account_name' => $validated['from_account_name'],
            ];
        }

        $paymentAmount = $validated['amount_paid'];
        $excessAmount = $paymentAmount - $validated['amount_to_pay'];
        if ($excessAmount > 0) {
            $paymentAmount = $validated['amount_to_pay']; 
        }
        
        // Create pembayaran record
        $pembayaran = new Pembayaran();
        $pembayaran->organization_id = $user->organization_id;
        $pembayaran->payment_id = $payment->id;
        $pembayaran->payment_method = $payment->payment_type;
        $pembayaran->payment_name = $payment->name;
        $pembayaran->payment_metadata = $payment->metadata;
        $pembayaran->payment_info_metadata = $paymentInfoMetadata;
        $pembayaran->payment_type = $validated['type'];
        $pembayaran->payment_type_metadata = now()->format('m-Y');
        $pembayaran->payment_amount = $paymentAmount;
        if ($excessAmount > 0) {
            $pembayaran->excess_amount = $excessAmount;
        }
        $pembayaran->payment_date = now();
        $pembayaran->user_id = $user->id;
        $pembayaran->user_name = $user->name;
        $pembayaran->user_blok = $user->blok;
        $pembayaran->created_by_user_id = $user->id;
        $pembayaran->created_by_user_name = $user->name;
        $pembayaran->status = 'pending';
        $pembayaran->notes = $validated['notes'];
        
        // Handle file upload to GCS
        if ($request->hasFile('payment_proof')) {
            $gcs = app(GoogleCloudStorage::class);
            $file = $request->file('payment_proof');
            
            // Generate filename
            $slug = \Illuminate\Support\Str::slug($user->name . '-' . $validated['type']);
            $slug = substr($slug, 0, 30); // Max 30 chars
            $timestamp = now()->format('YmdHis');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'pembayaran/' . $slug . '-' . $timestamp . '.' . $extension;
            
            // Read file contents and upload
            $fileStream = fopen($file->getRealPath(), 'r');
            $result = $gcs->put($fileName, $fileStream);
            
            if ($result) {
                $pembayaran->file_driver = 'gcs';
                $pembayaran->file_path = $fileName;
            }
        }
        
        $pembayaran->save();
        
        return redirect()->route('home')->with('success', 'Konfirmasi pembayaran berhasil disimpan dan menunggu verifikasi admin');
    }
    
    public function depositForm(Request $request)
    {
        $user = auth()->user();
        
        $payment = Payment::where('organization_id', $user->organization_id)
            ->findOrFail($request->input('payment_id'));
        
        return view('deposit_form', [
            'payment' => $payment,
            'user' => $user,
        ]);
    }
    
    public function submitDeposit(Request $request)
    {
        $user = auth()->user();
        
        // Get payment information first to determine validation rules
        $payment = Payment::where('organization_id', $user->organization_id)
            ->findOrFail($request->input('payment_id'));
        
        // Different validation rules for QRIS vs Bank Transfer
        if ($payment->payment_type === 'qr_payment') {
            $validated = $request->validate([
                'payment_id' => 'required|exists:payments,id',
                'amount_paid' => 'required|numeric|min:1',
                'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // Required for QRIS
                'notes' => 'nullable|string|max:1000',
            ]);
        } else {
            $validated = $request->validate([
                'payment_id' => 'required|exists:payments,id',
                'from_bank' => 'required|string|max:255',
                'from_account_number' => 'required|string|max:255',
                'from_account_name' => 'required|string|max:255',
                'amount_paid' => 'required|numeric|min:1',
                'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'notes' => 'nullable|string|max:1000',
            ]);
        }

        $existingPendingDeposit = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'user_deposit')
            ->where('status', 'pending')
            ->first();
        
        if ($existingPendingDeposit) {
            return redirect()->route('home')->with('error', 'Anda masih memiliki deposit yang belum diverifikasi.');
        }
        
        // Prepare payment info metadata
        if ($payment->payment_type === 'qr_payment') {
            // For QRIS, only store payment method type
            $paymentInfoMetadata = [
                'payment_method' => 'qr_payment',
                'qr_payment_type' => $payment->name,
            ];
        } else {
            // For bank transfer, store bank details
            $paymentInfoMetadata = [
                'from_bank' => $validated['from_bank'],
                'from_account_number' => $validated['from_account_number'],
                'from_account_name' => $validated['from_account_name'],
            ];
        }
        
        // Create pembayaran record
        $pembayaran = new Pembayaran();
        $pembayaran->organization_id = $user->organization_id;
        $pembayaran->payment_id = $payment->id;
        $pembayaran->payment_method = $payment->payment_type;
        $pembayaran->payment_name = $payment->name;
        $pembayaran->payment_metadata = $payment->metadata;
        $pembayaran->payment_info_metadata = $paymentInfoMetadata;
        $pembayaran->payment_type = 'user_deposit';
        $pembayaran->payment_type_metadata = now()->format('m-Y');
        $pembayaran->payment_amount = $validated['amount_paid'];
        $pembayaran->payment_date = now();
        $pembayaran->user_id = $user->id;
        $pembayaran->user_name = $user->name;
        $pembayaran->user_blok = $user->blok;
        $pembayaran->created_by_user_id = $user->id;
        $pembayaran->created_by_user_name = $user->name;
        $pembayaran->status = 'pending';
        $pembayaran->notes = $validated['notes'];
        
        // Handle file upload to GCS
        if ($request->hasFile('payment_proof')) {
            $gcs = app(GoogleCloudStorage::class);
            $file = $request->file('payment_proof');
            
            // Generate filename
            $slug = \Illuminate\Support\Str::slug($user->name . '-deposit');
            $slug = substr($slug, 0, 30); // Max 30 chars
            $timestamp = now()->format('YmdHis');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'pembayaran/' . $slug . '-' . $timestamp . '.' . $extension;
            
            // Read file contents and upload
            $fileStream = fopen($file->getRealPath(), 'r');
            $result = $gcs->put($fileName, $fileStream);
            
            if ($result) {
                $pembayaran->file_driver = 'gcs';
                $pembayaran->file_path = $fileName;
            }
        }
        
        $pembayaran->save();
        
        return redirect()->route('home')->with('success', 'Konfirmasi deposit berhasil disimpan dan menunggu verifikasi admin');
    }
    
    /**
     * Serve QRIS image (static or dynamic)
     */
    public function serveQrisImage(Request $request)
    {
        $user = auth()->user();
        
        $paymentId = $request->input('payment_id');
        $amount = $request->input('amount', 0);
        $type = $request->input('type', 'current'); // current_billing or previous_billing
        
        $payment = Payment::where('organization_id', $user->organization_id)
            ->where('id', $paymentId)
            ->firstOrFail();

        if ($payment->payment_type !== 'qr_payment') {
            abort(404, 'Not a QR payment method');
        }

        $metadata = is_string($payment->metadata) ? json_decode($payment->metadata, true) : $payment->metadata;

        // Check if dynamic QRIS
        $isDynamic = isset($metadata['is_dynamic_qris']) && $metadata['is_dynamic_qris'];
        
        if ($isDynamic && $amount > 0) {
            // For dynamic QRIS, generate QR code with amount using file-based cache
            // Create unique cache key based on: payment_id, user_id, type, amount
            $cacheFileName = "qris_dynamic_{$paymentId}_{$user->id}_{$type}_{$amount}.png";
            $cachePath = "qr_cache/{$cacheFileName}";
            
            // Check if cached file exists and is fresh (less than 1 hour old)
            if (\Storage::disk('public')->exists($cachePath)) {
                $cacheTime = \Storage::disk('public')->lastModified($cachePath);
                $age = time() - $cacheTime;
                
                // If cache is still fresh (less than 1 hour), serve it
                if ($age < 3600) {
                    $file = \Storage::disk('public')->get($cachePath);
                    return response($file, 200, [
                        'Content-Type' => 'image/png',
                        'Content-Disposition' => 'inline',
                        'Cache-Control' => 'public, max-age=3600',
                    ]);
                }
            }
            
            // Generate new dynamic QRIS
            try {
                $qrTextPath = $metadata['qr_text_path'] ?? null;
                
                if (!$qrTextPath || !\Storage::disk('public')->exists($qrTextPath)) {
                    abort(500, 'QRIS text file not found. Cannot generate dynamic QR.');
                }
                
                // Read static QRIS text
                $staticQrisText = \Storage::disk('public')->get($qrTextPath);
                
                // Use QrisApiService to generate dynamic QRIS
                $qrisApiService = new QrisApiService();
                $result = $qrisApiService->generateDynamicQris(
                    staticQris: trim($staticQrisText),
                    amount: $amount
                    // Note: fee is not passed to API, it's already calculated in $amount
                );
                
                $dynamicQrisText = $result['dynamicQris'];
                
                // Use Python script to convert QRIS text to image
                $outputFullPath = \Storage::disk('public')->path($cachePath);
                
                // Ensure cache directory exists
                $cacheDir = dirname($outputFullPath);
                if (!file_exists($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                
                // Run Python script to create QR image from text
                $pythonPath = config('venv.venv.path');
                if (!file_exists($pythonPath)) {
                    $pythonPath = 'python3'; // Fallback to system python
                }
                
                $scriptPath = base_path('scripts/create_qris.py');
                $command = escapeshellcmd("{$pythonPath} {$scriptPath}") . ' ' . 
                          escapeshellarg($dynamicQrisText) . ' ' . 
                          escapeshellarg($outputFullPath);
                
                $output = [];
                $returnCode = 0;
                exec($command . ' 2>&1', $output, $returnCode);
                
                if ($returnCode !== 0) {
                    \Log::error('QRIS image generation failed', [
                        'command' => $command,
                        'output' => implode("\n", $output),
                        'return_code' => $returnCode,
                    ]);
                    
                    // Fallback to static QR
                    if (isset($metadata['qr_image_path']) && \Storage::disk('public')->exists($metadata['qr_image_path'])) {
                        $file = \Storage::disk('public')->get($metadata['qr_image_path']);
                        $mimeType = \Storage::disk('public')->mimeType($metadata['qr_image_path']);
                        
                        return response($file, 200, [
                            'Content-Type' => $mimeType,
                            'Content-Disposition' => 'inline',
                            'Cache-Control' => 'public, max-age=3600',
                        ]);
                    }
                    
                    abort(500, 'Failed to generate QRIS image');
                }
                
                // Serve the generated image
                if (\Storage::disk('public')->exists($cachePath)) {
                    $file = \Storage::disk('public')->get($cachePath);
                    
                    return response($file, 200, [
                        'Content-Type' => 'image/png',
                        'Content-Disposition' => 'inline',
                        'Cache-Control' => 'public, max-age=3600',
                    ]);
                }
                
                abort(500, 'Generated QRIS file not found');
                
            } catch (\Exception $e) {
                \Log::error('Dynamic QRIS generation error', [
                    'error' => $e->getMessage(),
                    'payment_id' => $paymentId,
                    'amount' => $amount,
                ]);
                
                // Fallback to static QR on error
                if (isset($metadata['qr_image_path']) && \Storage::disk('public')->exists($metadata['qr_image_path'])) {
                    $file = \Storage::disk('public')->get($metadata['qr_image_path']);
                    $mimeType = \Storage::disk('public')->mimeType($metadata['qr_image_path']);
                    
                    return response($file, 200, [
                        'Content-Type' => $mimeType,
                        'Content-Disposition' => 'inline',
                        'Cache-Control' => 'public, max-age=3600',
                    ]);
                }
                
                abort(500, 'QRIS generation failed');
            }
        } else {
            // For static QRIS, just serve the image
            if (!isset($metadata['qr_image_path'])) {
                abort(404, 'QR image not found');
            }
            
            if (!\Storage::disk('public')->exists($metadata['qr_image_path'])) {
                abort(404, 'QR image file not found');
            }
            
            $file = \Storage::disk('public')->get($metadata['qr_image_path']);
            $mimeType = \Storage::disk('public')->mimeType($metadata['qr_image_path']);
            
            // Use payment updated_at timestamp for cache control
            $lastModified = $payment->updated_at ?? now();
            
            return response($file, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'public, max-age=3600',
                'Last-Modified' => $lastModified->toRfc7231String(),
                'ETag' => md5($paymentId . '-' . $lastModified->timestamp),
            ]);
        }
    }
}
