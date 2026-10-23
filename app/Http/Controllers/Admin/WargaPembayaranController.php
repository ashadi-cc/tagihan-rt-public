<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Jobs\NotifVerifiedPembayaranJob;
use App\Jobs\NotifRejectedPembayaranJob;
use App\Jobs\PembayaranOtomatisDepositJob;

class WargaPembayaranController extends Controller 
{
    use \App\Http\Controllers\Admin\Util;

    public function index($userId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $pembayarans = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->whereNot('payment_type', 'user_deposit')
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return view('admin.warga.pembayaran', [
            'user' => $user,
            'pembayarans' => $pembayarans,
            'currentTagihan' => $user->getCurrentMonthTagihan(),
            'tunggakanTotal' => $user->getTunggakanTotal(),
        ]);
    }

    public function show($userId, $pembayaranId)
    {
        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $pembayaran = Pembayaran::where('organization_id', $admin->organization_id)
            ->where('user_id', $user->id)
            ->whereNot('payment_type', 'user_deposit')
            ->findOrFail($pembayaranId);

        $tagihanPendingList = []; 
        if ($pembayaran->status === 'pending') {
            if ($pembayaran->payment_type === 'current_billing') {
                // load month and year from payment_type_metadata the value is MM-YYYY
                $monthYear = $pembayaran->payment_type_metadata;
                [$month, $year] = explode('-', $monthYear);
                if (is_numeric($month) && is_numeric($year)) {
                    $tagihanPendingList = Tagihan::where('organization_id', $admin->organization_id)
                        ->where('user_id', $user->id)
                        ->whereIn('billing_type', ['rutin_perbulan'])
                        ->whereYear('billing_generated_date', $year)
                        ->whereMonth('billing_generated_date', $month)
                        ->whereRaw('billing_amount > payment_amount')
                        ->get();
                }
            }

            if ($pembayaran->payment_type === 'previous_billing') {
                 $currentMonth = now()->format('Y-m');
                    $tagihanPendingList = Tagihan::where('organization_id', $admin->organization_id)
                        ->where('user_id', $user->id)
                        ->where(function($query) use ($currentMonth) {
                            $query->whereRaw("DATE_FORMAT(billing_generated_date, '%Y-%m') < ?", [$currentMonth])
                                ->whereIn('billing_type', ['rutin_perbulan'])
                                ->orWhere('billing_type', 'tunggakan_warga');
                        })
                        ->whereRaw('billing_amount > payment_amount')
                        ->get();
            }

            if ($pembayaran->payment_type === 'manual_billing') {
                $tagihanPendingList = Tagihan::where('organization_id', $admin->organization_id)
                    ->where('user_id', $user->id)
                    ->where('billing_type', 'manual')
                    ->whereRaw('billing_amount > payment_amount')
                    ->get();
            }
        }

        $totalPendingAmount = 0;
        if (!empty($tagihanPendingList)) {
            // calculate total pending amount from tagihanPendingList (billing_amount - payment_amount)
            $totalPendingAmount = $tagihanPendingList->sum(function ($tagihan) {
                return $tagihan->billing_amount - $tagihan->payment_amount;
            });
        }

        $tagihans = $pembayaran->tagihans()->get();

        // Get available payments for moving payment
        $availablePayments = \App\Models\Payment::where('organization_id', $admin->organization_id)
            ->where('id', '!=', $pembayaran->payment_id)
            ->where('disabled', false)
            ->orderBy('name')
            ->get();

        return view('admin.warga.show_pembayaran', [
            'user' => $user,
            'pembayaran' => $pembayaran,
            'currentTagihan' => $user->getCurrentMonthTagihan(),
            'tunggakanTotal' => $user->getTunggakanTotal(),
            'tagihanPendingList' => $tagihanPendingList,
            'totalPendingAmount' => $totalPendingAmount,
            'tagihans' => $tagihans,
            'availablePayments' => $availablePayments,
        ]);
    }

    public function approve(Request $request, $userId, $pembayaranId)
    {
        $validated = $request->validate([
            'tagihan_ids' => 'required|array|min:1',
            'tagihan_ids.*' => 'integer|exists:tagihans,id',
        ]);

        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $pembayaran = Pembayaran::where('organization_id', $admin->organization_id)
            ->where('user_id', $user->id)
            ->whereNot('payment_type', 'user_deposit')
            ->findOrFail($pembayaranId);

        // Check if pembayaran is already verified or rejected
        if ($pembayaran->status !== 'pending') {
            return redirect()
                ->route('admin.warga.pembayaran.show', ['userID' => $userId, 'pembayaranID' => $pembayaranId])
                ->with('error', 'Pembayaran ini sudah diproses sebelumnya dan tidak dapat diverifikasi lagi');
        }

        // Approve pembayaran
        $pembayaran->status = 'verified';
        $pembayaran->verified_by_user_id = $admin->id;
        $pembayaran->verified_by_user_name = $admin->name;
        $pembayaran->verified_at = now();
        $pembayaran->save();

        // Process payment to tagihans
        $this->bayarTagihanFromPembayaran($pembayaran, auth()->user(), $validated['tagihan_ids']);

        // Create activity log
        $pembayaran->logs()->create([
            'organization_id' => $admin->organization_id,
            'username' => $admin->name,
            'description' => "Pembayaran #{$pembayaran->id} sebesar Rp " . number_format($pembayaran->payment_amount, 0, ',', '.') . " untuk warga '{$user->name}' ({$user->blok}) telah diverifikasi",
            'url_link' => '/admin/master/warga/' . $user->id . '/pembayaran/' . $pembayaran->id,
        ]);

        // Dispatch notification job
        NotifVerifiedPembayaranJob::dispatch($pembayaran->id);

        // if the payment has excess amount, replicate the payment as user_deposit with the excess amount and link to original payment
        if ($pembayaran->excess_amount > 0) {
            $depositPembayaran = $pembayaran->replicate();
            $depositPembayaran->payment_type = 'user_deposit';
            $depositPembayaran->payment_amount = $pembayaran->excess_amount;
            $depositPembayaran->excess_amount = 0;
            $depositPembayaran->status = 'verified';
            $depositPembayaran->verified_by_user_id = $admin->id;
            $depositPembayaran->verified_by_user_name = $admin->name;
            $depositPembayaran->verified_at = now();
            $depositPembayaran->notes = 'Deposit dari kelebihan pembayaran #' . $pembayaran->id;
            $depositPembayaran->save();
            PembayaranOtomatisDepositJob::dispatch($user->id);
            NotifVerifiedPembayaranJob::dispatch($depositPembayaran->id);
        }

        return redirect()
            ->route('admin.warga.pembayaran.show', ['userID' => $userId, 'pembayaranID' => $pembayaranId])
            ->with('success', 'Pembayaran berhasil diverifikasi');
    }

    public function reject(Request $request, $userId, $pembayaranId)
    {
        $validated = $request->validate([
            'reject_reason' => 'nullable|string|max:1000',
        ]);

        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $pembayaran = Pembayaran::where('organization_id', $admin->organization_id)
            ->where('user_id', $user->id)
            ->whereNot('payment_type', 'user_deposit')
            ->findOrFail($pembayaranId);

        // Check if pembayaran is already verified or rejected
        if ($pembayaran->status !== 'pending') {
            return redirect()
                ->route('admin.warga.pembayaran.show', ['userID' => $userId, 'pembayaranID' => $pembayaranId])
                ->with('error', 'Pembayaran ini sudah diproses sebelumnya dan tidak dapat ditolak lagi');
        }

        // Reject pembayaran
        $pembayaran->status = 'rejected';
        $pembayaran->verified_by_user_id = $admin->id;
        $pembayaran->verified_by_user_name = $admin->name;
        $pembayaran->verified_at = now();
        
        // Add reject reason to notes if provided
        if (!empty($validated['reject_reason'])) {
            $rejectNote = "\n\n[Alasan Penolakan: " . $validated['reject_reason'] . "]";
            $pembayaran->notes = ($pembayaran->notes ?? '') . $rejectNote;
        }
        
        $pembayaran->save();

        // Create activity log
        $logDescription = "Pembayaran #{$pembayaran->id} sebesar Rp " . number_format($pembayaran->payment_amount, 0, ',', '.') . " untuk warga '{$user->name}' ({$user->blok}) telah ditolak";
        if (!empty($validated['reject_reason'])) {
            $logDescription .= "\nAlasan: " . $validated['reject_reason'];
        }
        
        $pembayaran->logs()->create([
            'organization_id' => $admin->organization_id,
            'username' => $admin->name,
            'description' => $logDescription,
            'url_link' => '/admin/master/warga/' . $user->id . '/pembayaran/' . $pembayaran->id,
        ]);

        // Dispatch notification job
        NotifRejectedPembayaranJob::dispatch($pembayaran->id, $validated['reject_reason'] ?? null);

        return redirect()
            ->route('admin.warga.pembayaran.show', ['userID' => $userId, 'pembayaranID' => $pembayaranId])
            ->with('success', 'Pembayaran berhasil ditolak');
    }

    public function updateAmount(Request $request, $userId, $pembayaranId)
    {
        $validated = $request->validate([
            'new_payment_amount' => 'required|integer|min:1',
            'total_pending_amount' => 'required|integer|min:0',
            'notes' => 'required|string|max:1000',
        ]);

        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $pembayaran = Pembayaran::where('organization_id', $admin->organization_id)
            ->where('user_id', $user->id)
            ->whereNot('payment_type', 'user_deposit')
            ->findOrFail($pembayaranId);

        // Check if pembayaran is pending
        if ($pembayaran->status !== 'pending') {
            return redirect()
                ->route('admin.warga.pembayaran.show', ['userID' => $userId, 'pembayaranID' => $pembayaranId])
                ->with('error', 'Jumlah pembayaran hanya dapat diubah untuk status pending');
        }

        // Store old amount for logging
        $oldAmount = $pembayaran->payment_amount + ($pembayaran->excess_amount ?? 0);

        $paymentAmount = $validated['new_payment_amount'];
        $excessAmount = $paymentAmount - $validated['total_pending_amount'];
        if ($excessAmount > 0 ) {
            $paymentAmount = $validated['total_pending_amount'];
        }

        // Update payment amount
        $pembayaran->payment_amount = $paymentAmount;
        $pembayaran->excess_amount = $excessAmount > 0 ? $excessAmount : 0;
        $pembayaran->notes = ($pembayaran->notes ?? '') . "\n[Update Jumlah Pembayaran: " . $validated['notes'] . "]";
        $pembayaran->save();

        // Create activity log
        $pembayaran->logs()->create([
            'organization_id' => $admin->organization_id,
            'username' => $admin->name,
            'description' => "Jumlah pembayaran #{$pembayaran->id} diubah dari Rp " . number_format($oldAmount, 0, ',', '.') . " menjadi Rp " . number_format($validated['new_payment_amount'], 0, ',', '.') . "\nCatatan: " . $validated['notes'],
            'url_link' => '/admin/master/warga/' . $user->id . '/pembayaran/' . $pembayaran->id,
        ]);

        return redirect()
            ->route('admin.warga.pembayaran.show', ['userID' => $userId, 'pembayaranID' => $pembayaranId])
            ->with('success', 'Jumlah pembayaran berhasil diubah');
    }

    public function destroy($userId, $pembayaranId)
    {
        $admin = auth()->user();

        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $pembayaran = Pembayaran::where('organization_id', $admin->organization_id)
            ->where('user_id', $user->id)
            ->whereNot('payment_type', 'user_deposit')
            ->findOrFail($pembayaranId);

        if ($pembayaran->status !== 'pending') {
            return redirect()
                ->route('admin.warga.pembayaran', ['userID' => $userId])
                ->with('error', 'Hanya pembayaran dengan status pending yang dapat dihapus');
        }

        $pembayaran->logs()->create([
            'organization_id' => $admin->organization_id,
            'username' => $admin->name,
            'description' => "Pembayaran #{$pembayaran->id} sebesar Rp " . number_format($pembayaran->payment_amount, 0, ',', '.') . " untuk warga '{$user->name}' ({$user->blok}) telah dihapus",
            'url_link' => '/admin/master/warga/' . $user->id . '/pembayaran',
        ]);

        $pembayaran->delete();

        return redirect()
            ->route('admin.warga.pembayaran', ['userID' => $userId])
            ->with('success', 'Pembayaran berhasil dihapus');
    }

    public function create($userId)
    {
        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        // Get available payment methods
        $payments = \App\Models\Payment::where('organization_id', $admin->organization_id)
            ->where('disabled', false)
            ->orderBy('name')
            ->get();

        return view('admin.warga.create_pembayaran', [
            'user' => $user,
            'payments' => $payments,
        ]);
    }

    public function store($userId, Request $request)
    {
        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'payment_type' => 'required|in:current_billing,previous_billing,manual_billing',
            'payment_id' => 'required|exists:payments,id',
            'from_bank' => 'nullable|string|max:255',
            'from_account_number' => 'nullable|string|max:255',
            'from_account_name' => 'nullable|string|max:255',
            'amount_paid' => 'required|integer|min:1',
            'tagihan_ids' => 'required|array|min:1',
            'tagihan_ids.*' => 'required|exists:tagihans,id',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:1000',
            'auto_verify' => 'nullable|boolean',
        ]);

        // Verify payment method belongs to organization
        $payment = \App\Models\Payment::where('organization_id', $admin->organization_id)
            ->where('id', $validated['payment_id'])
            ->where('disabled', false)
            ->firstOrFail();

        // Get selected tagihan and validate
        $selectedTagihan = Tagihan::where('organization_id', $admin->organization_id)
            ->where('user_id', $user->id)
            ->whereIn('id', $validated['tagihan_ids'])
            ->whereRaw('billing_amount > payment_amount')
            ->get();

        if ($selectedTagihan->isEmpty()) {
            return back()
                ->withErrors(['tagihan_ids' => 'Tidak ada tagihan yang valid untuk dibayar'])
                ->withInput();
        }

        // Calculate total outstanding amount
        $totalOutstanding = $selectedTagihan->sum(function($tagihan) {
            return $tagihan->billing_amount - $tagihan->payment_amount;
        });

        // Validate amount
        if ($validated['amount_paid'] > $totalOutstanding) {
            return back()
                ->withErrors(['amount_paid' => 'Jumlah pembayaran melebihi total tagihan yang dipilih'])
                ->withInput();
        }

        // Prepare payment info metadata
        $paymentInfoMetadata = [
            'from_bank' => $validated['from_bank'] ?? '',
            'from_account_number' => $validated['from_account_number'] ?? '',
            'from_account_name' => $validated['from_account_name'] ?? '',
        ];

        // Create pembayaran record
        $pembayaran = new Pembayaran();
        $pembayaran->organization_id = $admin->organization_id;
        $pembayaran->user_id = $user->id;
        $pembayaran->user_name = $user->name;
        $pembayaran->user_blok = $user->blok;
        $pembayaran->payment_id = $validated['payment_id'];
        $pembayaran->payment_method = $payment->payment_type;
        $pembayaran->payment_name = $payment->name;
        $pembayaran->payment_metadata = $payment->metadata;
        $pembayaran->payment_info_metadata = $paymentInfoMetadata;
        $pembayaran->payment_amount = $validated['amount_paid'];
        $pembayaran->payment_type = $validated['payment_type'];
        $pembayaran->payment_date = now();
        $pembayaran->notes = $validated['notes'];
        $pembayaran->status = 'pending';
        $pembayaran->created_by_user_id = $admin->id;
        $pembayaran->created_by_user_name = $admin->name;

        // Set payment type metadata
        if ($validated['payment_type'] === 'current_billing') {
            $pembayaran->payment_type_metadata = now()->format('m-Y');
        } else if ($validated['payment_type'] === 'previous_billing') {
            $pembayaran->payment_type_metadata = 'previous_billing';
        } else {
            $pembayaran->payment_type_metadata = 'manual_billing';
        }

        // Set verification info if auto verify
        if ($request->has('auto_verify') && $request->auto_verify) {
            $pembayaran->status = 'verified';
            $pembayaran->verified_by_user_id = $admin->id;
            $pembayaran->verified_by_user_name = $admin->name;
            $pembayaran->verified_at = now();
        }

        $pembayaran->save();

        // Handle file upload to GCS
        if ($request->hasFile('payment_proof')) {
            $gcs = app(\App\Services\GoogleCloudStorage::class);
            $file = $request->file('payment_proof');

            // Generate filename
            $slug = \Illuminate\Support\Str::slug($user->name . '-pembayaran');
            $slug = substr($slug, 0, 30); // Max 30 chars
            $timestamp = now()->format('YmdHis');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'pembayaran/' . $slug . '-' . $timestamp . '.' . $extension;

            // Read file content and upload
            $fileStream = fopen($file->getRealPath(), 'r');
            $result = $gcs->put($fileName, $fileStream);

            if ($result) {
                $pembayaran->file_driver = 'gcs';
                $pembayaran->file_path = $fileName;
                $pembayaran->save();
            }
        }


        // If auto verify, process payment immediately
        if ($request->has('auto_verify') && $request->auto_verify) {
            try {
                $this->bayarTagihanFromPembayaran($pembayaran, auth()->user(), $selectedTagihan->pluck('id')->toArray());
                
                // Create success log
                $logDescription = "Pembayaran #{$pembayaran->id} sebesar Rp " . number_format($pembayaran->payment_amount, 0, ',', '.') . " untuk warga '{$user->name}' ({$user->blok}) berhasil dicatat dan diverifikasi otomatis";
                
            } catch (\Exception $e) {
                // Create error log but don't fail the entire process
                $logDescription = "Pembayaran #{$pembayaran->id} sebesar Rp " . number_format($pembayaran->payment_amount, 0, ',', '.') . " untuk warga '{$user->name}' ({$user->blok}) berhasil dicatat tetapi gagal diproses otomatis: " . $e->getMessage();
            }
        } else {
            $logDescription = "Pembayaran #{$pembayaran->id} sebesar Rp " . number_format($pembayaran->payment_amount, 0, ',', '.') . " untuk warga '{$user->name}' ({$user->blok}) berhasil dicatat dan menunggu verifikasi";
        }

        // Create activity log
        $pembayaran->logs()->create([
            'organization_id' => $admin->organization_id,
            'username' => $admin->name,
            'description' => $logDescription,
            'url_link' => '/admin/master/warga/' . $user->id . '/pembayaran/' . $pembayaran->id,
        ]);

        return redirect()
            ->route('admin.warga.pembayaran', ['userID' => $userId])
            ->with('success', 'Pembayaran berhasil dicatat' . ($request->has('auto_verify') && $request->auto_verify ? ' dan diverifikasi' : ''));
    }

    public function getTagihan($userId, Request $request)
    {
        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $type = $request->query('type');

        if ($type === 'current_billing') {
            // Get current month tagihan
            $tagihan = Tagihan::where('organization_id', $admin->organization_id)
                ->where('user_id', $user->id)
                ->where('billing_type', 'rutin_perbulan')
                ->whereYear('billing_generated_date', now()->year)
                ->whereMonth('billing_generated_date', now()->month)
                ->whereRaw('billing_amount > payment_amount')
                ->select('id', 'billing_name', 'billing_amount', 'payment_amount', 'billing_generated_date')
                ->orderBy('billing_generated_date')
                ->get();
                
        } else if ($type === 'previous_billing') {
            // Get previous months tagihan (tunggakan)
            $currentMonth = now()->format('Y-m');
            $tagihan = Tagihan::where('organization_id', $admin->organization_id)
                ->where('user_id', $user->id)
                ->where('billing_type', '!=', 'manual')
                ->where(function($query) use ($currentMonth) {
                    $query->whereRaw("DATE_FORMAT(billing_generated_date, '%Y-%m') < ?", [$currentMonth])
                        ->orWhere('billing_type', 'tunggakan_warga');
                })
                ->whereRaw('billing_amount > payment_amount')
                ->select('id', 'billing_name', 'billing_amount', 'payment_amount', 'billing_generated_date')
                ->orderBy('billing_generated_date')
                ->get();
        } else if ($type === 'manual') {
            $tagihan = Tagihan::where('organization_id', $admin->organization_id)
                ->where('user_id', $user->id)
                ->where('billing_type', 'manual')
                ->whereRaw('billing_amount > payment_amount')
                ->select('id', 'billing_name', 'billing_amount', 'payment_amount', 'billing_generated_date')
                ->orderBy('billing_generated_date')
                ->get();
        } else {
            return response()->json([]);
        }

        return response()->json($tagihan);
    }
}