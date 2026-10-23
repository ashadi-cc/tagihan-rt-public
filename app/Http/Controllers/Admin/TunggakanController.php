<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tagihan;
use App\Jobs\PembayaranOtomatisDepositJob;

class TunggakanController extends Controller 
{
    public function index($userId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $list = Tagihan::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('billing_type', 'tunggakan_warga')
            ->orderBy('billing_generated_date', 'desc')
            ->get();

        return view('admin.tunggakan.index', ['user' => $user, 'list' => $list]);
    }

    public function store(Request $request, $userId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $request->validate([
            'billing_name' => 'required|string|max:255',
            'billing_amount' => 'required|numeric|min:0',
            'billing_generated_date' => 'required|date|before_or_equal:today',
        ]);

        // Create the tunggakan tagihan
        Tagihan::create([
            'organization_id' => $user->organization_id,
            'billing_id' => null,
            'billing_name' => $request->billing_name,
            'billing_type' => 'tunggakan_warga',
            'billing_amount' => $request->billing_amount,
            'billing_priority' => 0,
            'billing_generated_date' => $request->billing_generated_date,
            'description' => $request->description,
            'payment_amount' => 0,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_blok_number' => $user->blok,
            'created_by_user_id' => auth()->user()->id,
            'created_by_user_name' => auth()->user()->name,
        ]);

        // Dispatch pembayaran otomatis deposit job for this user
        PembayaranOtomatisDepositJob::dispatch($user->id);

        // Create log for the user
        $user->logs()->create([
            'organization_id' => $user->organization_id,
            'username' => auth()->user()->name,
            'description' => "Tunggakan '{$request->billing_name}' sebesar Rp " . number_format($request->billing_amount, 0, ',', '.') . " telah dibuat pada tanggal " . date('d M Y', strtotime($request->billing_generated_date)),
            'url_link' => '/admin/master/tagihan',
        ]);

        return redirect()->back()->with('success', 'Tunggakan berhasil disimpan.');
    }

    public function destroy($userId, $tagihanId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->where('id', $userId)
            ->firstOrFail();

        $tagihan = Tagihan::where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->where('billing_type', 'tunggakan_warga')
            ->where('id', $tagihanId)
            ->firstOrFail();

        // Guard: cannot delete if payment_amount is not zero
        if ($tagihan->payment_amount > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus tunggakan yang sudah ada pembayaran.');
        }

        // Create log before deleting
        $user->logs()->create([
            'organization_id' => $user->organization_id,
            'username' => auth()->user()->name,
            'description' => "Tunggakan '{$tagihan->billing_name}' sebesar Rp " . number_format($tagihan->billing_amount, 0, ',', '.') . " telah dihapus",
            'url_link' => '/admin/master/tagihan',
        ]);

        $tagihan->delete();

        return redirect()->back()->with('success', 'Tunggakan berhasil dihapus.');
    }
}