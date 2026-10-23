<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pembayaran;
use App\Models\Payment;
use App\Services\GoogleCloudStorage;
use App\Jobs\PembayaranOtomatisDepositJob;
use App\Jobs\NotifVerifiedPembayaranJob;
use App\Jobs\NotifRejectedPembayaranJob;

class WargaDepositController extends Controller 
{
    public function index($userId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        // Get all deposits for this user
        $deposits = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'user_deposit')
            ->orderBy('id', 'desc')
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        // Calculate total verified deposit
        $totalDeposit = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'user_deposit')
            ->where('status', 'verified')
            ->sum('payment_amount');

        // calculate total used deposit from pembayaran_otomatis_deposit
        $totalUsedDeposit = Pembayaran::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'deposit_usage')
            ->where('status', 'verified')
            ->sum('payment_amount');

        return view('admin.warga.deposit', [
            'user' => $user,
            'deposits' => $deposits,
            'totalDeposit' => $totalDeposit,
            'currentTagihan' => $user->getCurrentMonthTagihan(),
            'tunggakanTotal' => $user->getTunggakanTotal(),
            'totalUsedDeposit' => $totalUsedDeposit,
        ]);
    }

    public function create($userId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        // Get available payment methods
        $payments = Payment::where('organization_id', auth()->user()->organization_id)
            ->where('disabled', false)
            ->orderBy('name')
            ->get();

        return view('admin.warga.create_deposit', [
            'user' => $user,
            'payments' => $payments,
            'currentTagihan' => $user->getCurrentMonthTagihan(),
            'tunggakanTotal' => $user->getTunggakanTotal(),
        ]);
    }

    public function store(Request $request, $userId)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'from_bank' => 'nullable|string|max:255',
            'from_account_number' => 'nullable|string|max:255',
            'from_account_name' => 'nullable|string|max:255',
            'amount_paid' => 'required|numeric|min:1',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:1000',
            'auto_verify' => 'nullable|boolean',
        ]);

        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->findOrFail($userId);

        // Get payment information
        $payment = Payment::where('organization_id', $admin->organization_id)
            ->findOrFail($validated['payment_id']);

        // Prepare payment info metadata
        $paymentInfoMetadata = [
            'from_bank' => $validated['from_bank'] ?? '',
            'from_account_number' => $validated['from_account_number'] ?? '',
            'from_account_name' => $validated['from_account_name'] ?? '',
        ];

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
        $pembayaran->created_by_user_id = $admin->id;
        $pembayaran->created_by_user_name = $admin->name;
        
        // If admin chooses to auto-verify
        if ($request->has('auto_verify') && $request->auto_verify) {
            $pembayaran->status = 'verified';
            $pembayaran->verified_by_user_id = $admin->id;
            $pembayaran->verified_by_user_name = $admin->name;
            $pembayaran->verified_at = now();
        } else {
            $pembayaran->status = 'pending';
        }
        
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

            // Read file content and upload
            $fileStream = fopen($file->getRealPath(), 'r');
            $result = $gcs->put($fileName, $fileStream);

            if ($result) {
                $pembayaran->file_driver = 'gcs';
                $pembayaran->file_path = $fileName;
            }
        }

        $pembayaran->save();

        // Create activity log
        $logDescription = "Deposit baru sebesar Rp " . number_format($pembayaran->payment_amount, 0, ',', '.') . " untuk warga '{$user->name}' ({$user->blok}) telah ditambahkan";
        if ($pembayaran->status === 'verified') {
            $logDescription .= " dan langsung diverifikasi";

            // dispatch pembayaran otomatis deposit job for this user
            PembayaranOtomatisDepositJob::dispatch($user->id);
        }
        
        $pembayaran->logs()->create([
            'organization_id' => $admin->organization_id,
            'username' => $admin->name,
            'description' => $logDescription,
            'url_link' => '/admin/master/warga/' . $user->id . '/deposit/' . $pembayaran->id,
        ]);

        $message = $pembayaran->status === 'verified' 
            ? 'Deposit berhasil ditambahkan dan terverifikasi' 
            : 'Deposit berhasil ditambahkan dan menunggu verifikasi';

        return redirect()
            ->route('admin.warga.deposit', ['userID' => $userId])
            ->with('success', $message);
    }

    public function show($userId, $depositId)
    {
        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $deposit = Pembayaran::where('organization_id', $admin->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'user_deposit')
            ->findOrFail($depositId);

        return view('admin.warga.show_deposit', [
            'user' => $user,
            'deposit' => $deposit,
            'currentTagihan' => $user->getCurrentMonthTagihan(),
            'tunggakanTotal' => $user->getTunggakanTotal(),
        ]);
    }

    public function update(Request $request, $userId, $depositId)
    {
        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $deposit = Pembayaran::where('organization_id', $admin->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'user_deposit')
            ->findOrFail($depositId);

        // Prevent editing if already verified
        if ($deposit->status === 'verified') {
            return redirect()
                ->route('admin.warga.deposit.show', ['userID' => $userId, 'depositID' => $depositId])
                ->with('error', 'Deposit yang sudah diverifikasi tidak dapat diedit');
        }

        // Track changes for logging
        $changes = [];

        if ($deposit->payment_amount != $validated['amount_paid']) {
            $changes[] = "Jumlah deposit diubah dari Rp " . number_format($deposit->payment_amount, 0, ',', '.') . " menjadi Rp " . number_format($validated['amount_paid'], 0, ',', '.');
        }

        if ($deposit->notes != $validated['notes']) {
            $changes[] = "Catatan diperbarui";
        }

        // Update deposit
        $deposit->payment_amount = $validated['amount_paid'];
        $deposit->notes = $validated['notes'];
        $deposit->save();

        // Create log if there are changes
        if (!empty($changes)) {
            $description = "Data deposit #{$deposit->id} untuk warga '{$user->name}' ({$user->blok}) diupdate:\n" . implode("\n", $changes);
            $deposit->logs()->create([
                'organization_id' => $admin->organization_id,
                'username' => $admin->name,
                'description' => $description,
                'url_link' => '/admin/master/warga/' . $user->id . '/deposit/' . $deposit->id,
            ]);
        }

        return redirect()
            ->route('admin.warga.deposit.show', ['userID' => $userId, 'depositID' => $depositId])
            ->with('success', 'Data deposit berhasil diupdate');
    }

    public function approve($userId, $depositId)
    {
        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $deposit = Pembayaran::where('organization_id', $admin->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'user_deposit')
            ->findOrFail($depositId);

        // Check if deposit is already verified or rejected
        if ($deposit->status !== 'pending') {
            return redirect()
                ->route('admin.warga.deposit.show', ['userID' => $userId, 'depositID' => $depositId])
                ->with('error', 'Deposit ini sudah diproses sebelumnya dan tidak dapat diverifikasi lagi');
        }

        // Approve deposit
        $deposit->status = 'verified';
        $deposit->verified_by_user_id = $admin->id;
        $deposit->verified_by_user_name = $admin->name;
        $deposit->verified_at = now();
        $deposit->save();

        // Create activity log
        $deposit->logs()->create([
            'organization_id' => $admin->organization_id,
            'username' => $admin->name,
            'description' => "Deposit #{$deposit->id} sebesar Rp " . number_format($deposit->payment_amount, 0, ',', '.') . " untuk warga '{$user->name}' ({$user->blok}) telah diverifikasi",
            'url_link' => '/admin/master/warga/' . $user->id . '/deposit/' . $deposit->id,
        ]);

        // Dispatch pembayaran otomatis deposit job for this user
        PembayaranOtomatisDepositJob::dispatch($user->id);
        // Dispatch notification job
        NotifVerifiedPembayaranJob::dispatch($deposit->id);

        return redirect()
            ->route('admin.warga.deposit.show', ['userID' => $userId, 'depositID' => $depositId])
            ->with('success', 'Deposit berhasil diverifikasi');
    }

    public function reject(Request $request, $userId, $depositId)
    {
        $validated = $request->validate([
            'reject_reason' => 'nullable|string|max:1000',
        ]);

        $admin = auth()->user();
        
        $user = User::where('organization_id', $admin->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $deposit = Pembayaran::where('organization_id', $admin->organization_id)
            ->where('user_id', $user->id)
            ->where('payment_type', 'user_deposit')
            ->findOrFail($depositId);

        // Check if deposit is already verified or rejected
        if ($deposit->status !== 'pending') {
            return redirect()
                ->route('admin.warga.deposit.show', ['userID' => $userId, 'depositID' => $depositId])
                ->with('error', 'Deposit ini sudah diproses sebelumnya dan tidak dapat ditolak lagi');
        }

        // Reject deposit
        $deposit->status = 'rejected';
        $deposit->verified_by_user_id = $admin->id;
        $deposit->verified_by_user_name = $admin->name;
        $deposit->verified_at = now();
        
        // Add reject reason to notes if provided
        if (!empty($validated['reject_reason'])) {
            $rejectNote = "\n\n[Alasan Penolakan: " . $validated['reject_reason'] . "]";
            $deposit->notes = ($deposit->notes ?? '') . $rejectNote;
        }
        
        $deposit->save();

        // Create activity log
        $logDescription = "Deposit #{$deposit->id} sebesar Rp " . number_format($deposit->payment_amount, 0, ',', '.') . " untuk warga '{$user->name}' ({$user->blok}) telah ditolak";
        if (!empty($validated['reject_reason'])) {
            $logDescription .= "\nAlasan: " . $validated['reject_reason'];
        }
        
        $deposit->logs()->create([
            'organization_id' => $admin->organization_id,
            'username' => $admin->name,
            'description' => $logDescription,
            'url_link' => '/admin/master/warga/' . $user->id . '/deposit/' . $deposit->id,
        ]);

        // Dispatch notification job
        NotifRejectedPembayaranJob::dispatch($deposit->id, $validated['reject_reason'] ?? null);

        return redirect()
            ->route('admin.warga.deposit.show', ['userID' => $userId, 'depositID' => $depositId])
            ->with('success', 'Deposit berhasil ditolak');
    }
}
