<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\Log;
use App\Models\RtTransaction;

class DashboardController extends Controller
{
    public function index()
    {
        $organization = auth()->user()->organization;
        
        // Get pending pembayaran
        $pendingPembayaran = Pembayaran::where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();
        
        // Get recent activity logs
        $recentLogs = Log::where('organization_id', $organization->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Count statistics
        $totalPendingPayments = Pembayaran::where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->count();
        
        $totalVerifiedPayments = Pembayaran::where('organization_id', $organization->id)
            ->where('status', 'verified')
            ->where('payment_type', '!=', 'deposit_usage')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalWarga = auth()->user()->organization->users()->warga()->count();
        
        // Calculate total verified deposit (global)
        $totalDeposit = Pembayaran::where('organization_id', $organization->id)
            ->where('payment_type', 'user_deposit')
            ->where('status', 'verified')
            ->sum('payment_amount');

        // Calculate total used deposit from pembayaran_otomatis_deposit (global)
        $totalUsedDeposit = Pembayaran::where('organization_id', $organization->id)
            ->where('payment_type', 'deposit_usage')
            ->where('status', 'verified')
            ->sum('payment_amount');
        
        // Calculate available deposit
        $availableDeposit = $totalDeposit - $totalUsedDeposit;
        
        // RT Transaction Summary
        $rtSummary = RtTransaction::where('organization_id', $organization->id)
            ->selectRaw('SUM(credit) as total_income, SUM(debit) as total_expense')
            ->first();
        
        $totalIncome = $rtSummary->total_income ?? 0;
        $totalExpense = $rtSummary->total_expense ?? 0;
        $sisaKas = $totalIncome - $totalExpense;
        
        // Top 10 users (warga) with the biggest outstanding tagihan
        $topWargaTagihan = \App\Models\Tagihan::select('user_id', 'user_name', 'user_blok_number')
            ->selectRaw('SUM(billing_amount - payment_amount) as total_tagihan')
            ->where('organization_id', $organization->id)
            ->where(function($query) {
                $query->where(function($q) {
                    // only include rutin_perbulan billing type where billing_generated_date m-Y is less than current month-year
                    $q->where('billing_type', 'rutin_perbulan')
                        ->whereRaw("DATE_FORMAT(billing_generated_date, '%m-%Y') < ?", [now()->format('m-Y')]);
                })->orWhere('billing_type', 'tunggakan_warga'); // or include tunggakan_warga type
            })
            ->groupBy('user_id', 'user_name', 'user_blok_number')
            ->orderByDesc('total_tagihan')
            ->having('total_tagihan', '>', 0)
            ->limit(10)
            ->get();

        return view('admin.dashboard.index', [
            'pendingPembayaran' => $pendingPembayaran,
            'recentLogs' => $recentLogs,
            'totalPendingPayments' => $totalPendingPayments,
            'totalVerifiedPayments' => $totalVerifiedPayments,
            'totalWarga' => $totalWarga,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'sisaKas' => $sisaKas,
            'topWargaTagihan' => $topWargaTagihan,
            'totalDeposit' => $totalDeposit,
            'totalUsedDeposit' => $totalUsedDeposit,
            'availableDeposit' => $availableDeposit,
        ]);
    }
}
