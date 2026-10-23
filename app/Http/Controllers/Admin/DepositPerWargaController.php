<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DepositPerWargaController extends Controller 
{
    /**
     * Display deposit per warga report
     */
    public function index(Request $request)
    {
        $organizationId = auth()->user()->organization_id;

        // Get deposit summary per warga (grouped by user)
        $depositsPerWarga = Pembayaran::select([
                'user_id',
                'user_name',
                'user_blok',
                DB::raw('SUM(CASE WHEN payment_type = "user_deposit" AND status = "verified" THEN payment_amount ELSE 0 END) as total_deposit'),
                DB::raw('SUM(CASE WHEN payment_type = "deposit_usage" AND status = "verified" THEN payment_amount ELSE 0 END) as total_used_deposit'),
                DB::raw('SUM(CASE WHEN payment_type = "user_deposit" AND status = "verified" THEN payment_amount ELSE 0 END) - SUM(CASE WHEN payment_type = "deposit_usage" AND status = "verified" THEN payment_amount ELSE 0 END) as remaining_deposit'),
                DB::raw("SUBSTRING_INDEX(user_blok, '-', 1) as blok_name_sort"),
                DB::raw("CAST(SUBSTRING_INDEX(user_blok, '-', -1) AS UNSIGNED) as blok_number_sort")
            ])
            ->where('organization_id', $organizationId)
            ->whereIn('payment_type', ['user_deposit', 'deposit_usage'])
            ->where('status', 'verified')
            ->groupBy('user_id', 'user_name', 'user_blok')
            ->orderBy('blok_name_sort', 'asc')
            ->orderBy('blok_number_sort', 'asc')
            ->orderBy('user_id', 'asc')
            ->get();

        // Filter out users with zero deposits
        $depositsPerWarga = $depositsPerWarga->filter(function($deposit) {
            return $deposit->total_deposit > 0;
        });

        // Calculate global summary
        $totalDeposit = Pembayaran::where('organization_id', $organizationId)
            ->where('payment_type', 'user_deposit')
            ->where('status', 'verified')
            ->sum('payment_amount');

        $totalUsedDeposit = Pembayaran::where('organization_id', $organizationId)
            ->where('payment_type', 'deposit_usage')
            ->where('status', 'verified')
            ->sum('payment_amount');

        $availableDeposit = $totalDeposit - $totalUsedDeposit;

        // Count statistics
        $totalWargaWithDeposit = $depositsPerWarga->count();
        $totalWargaWithRemainingDeposit = $depositsPerWarga->filter(function($deposit) {
            return $deposit->remaining_deposit > 0;
        })->count();

        return view('admin.report.deposit_per_warga', compact(
            'depositsPerWarga',
            'totalDeposit',
            'totalUsedDeposit',
            'availableDeposit',
            'totalWargaWithDeposit',
            'totalWargaWithRemainingDeposit'
        ));
    }
}
