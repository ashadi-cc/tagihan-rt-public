<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tagihan;
use Carbon\Carbon;
use App\Models\Pembayaran;

class WargaTagihanController extends Controller 
{
    use Util;

    public function index($userId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $currentMonth = Carbon::now()->format('Y-m');

        // Current month tagihan: rutin_perbulan only for this month
        $currentTagihan = Tagihan::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('billing_type', 'rutin_perbulan')
            ->whereYear('billing_generated_date', Carbon::now()->year)
            ->whereMonth('billing_generated_date', Carbon::now()->month)
            ->orderBy('billing_priority', 'asc')
            ->orderBy('billing_name', 'asc')
            ->get();

        // Manual billings: shown as-is, not filtered by month
        $manualBillings = Tagihan::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('billing_type', 'manual')
            ->orderBy('billing_generated_date', 'asc')
            ->get();

        // Tunggakan: rutin_perbulan before current month, and tunggakan_warga (manual excluded)
        $tunggakanQuery = Tagihan::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where(function($query) use ($currentMonth) {
                $query->where(function($q) use ($currentMonth) {
                        $q->whereRaw("DATE_FORMAT(billing_generated_date, '%Y-%m') < ?", [$currentMonth])
                          ->where('billing_type', 'rutin_perbulan');
                    })
                    ->orWhere('billing_type', 'tunggakan_warga');
            })
            ->whereRaw('billing_amount > payment_amount');

        // Calculate totals for all tunggakan
        $tunggakanTotal = [
            'billing_amount' => $tunggakanQuery->sum('billing_amount'),
            'payment_amount' => $tunggakanQuery->sum('payment_amount'),
            'sisa' => $tunggakanQuery->sum('billing_amount') - $tunggakanQuery->sum('payment_amount'),
        ];

        $tunggakan = $tunggakanQuery->orderBy('billing_generated_date', 'desc')
            ->orderBy('billing_priority', 'asc')
            ->paginate(10);

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

        $leftoverDeposit = $totalDeposit - $totalUsedDeposit;

        return view('admin.warga.tagihan', [
            'user' => $user,
            'currentTagihan' => $currentTagihan,
            'manualBillings' => $manualBillings,
            'tunggakan' => $tunggakan,
            'tunggakanTotal' => $tunggakanTotal,
            'leftoverDeposit' => $leftoverDeposit,
        ]);
    }

    public function bayarTagihanFromDeposit($userId, Request $request)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $request->validate([
            'tagihan_id' => 'required|exists:tagihans,id',
        ]);

        $tagihanId = $request->input('tagihan_id');

        $this->pembayaranOtomatisFromUserDeposit($user, [$tagihanId]);

        return redirect()->back()->with('success', 'Pembayaran otomatis dari deposit berhasil diproses.');
    }

    public function update($userId, $tagihanId, Request $request)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $tagihan = Tagihan::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('id', $tagihanId)
            ->firstOrFail();

        $request->validate([
            'billing_amount' => 'required|numeric|min:0',
            'edit_note' => 'required|string|max:500',
        ]);

        $oldAmount = $tagihan->billing_amount;
        $newAmount = $request->input('billing_amount');
        
        // Update tagihan
        $tagihan->billing_amount = $newAmount;
        $tagihan->description = $tagihan->description . ' ' . $request->input('edit_note');
        $tagihan->save();

        // Create activity log
        $description = "Tagihan '{$tagihan->billing_name}' untuk warga '{$user->name}' ({$user->blok}) diubah:\n";
        $description .= "Jumlah tagihan: Rp " . number_format($oldAmount, 0, ',', '.') . " → Rp " . number_format($newAmount, 0, ',', '.');
        $description .= "\nAlasan: " . $request->input('edit_note');
        
        $tagihan->logs()->create([
            'organization_id' => $user->organization_id,
            'username' => auth()->user()->name,
            'description' => $description,
            'url_link' => '/admin/master/warga/' . $user->id . '/tagihan',
        ]);

        return redirect()->back()->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function destroy($userId, $tagihanId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $tagihan = Tagihan::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('id', $tagihanId)
            ->firstOrFail();

        // Guard: cannot delete if payment_amount is greater than zero
        if ($tagihan->payment_amount > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus tagihan yang sudah ada pembayaran.');
        }

        // Create log before deleting
        $user->logs()->create([
            'organization_id' => $user->organization_id,
            'username' => auth()->user()->name,
            'description' => "Tagihan '{$tagihan->billing_name}' sebesar Rp " . number_format($tagihan->billing_amount, 0, ',', '.') . " telah dihapus",
            'url_link' => '/admin/master/tagihan',
        ]);

        $tagihan->delete();

        return redirect()->back()->with('success', 'Tagihan berhasil dihapus.');
    }
}
