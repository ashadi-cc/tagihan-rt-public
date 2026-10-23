<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;

class IncomePerIuranController extends Controller 
{
    /**
     * Display income per iuran report
     */
    public function index(Request $request)
    {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        // Build the query for grouping
        $query = Tagihan::where('organization_id', auth()->user()->organization_id)
            ->where('payment_amount', '>', 0)
            ->whereIn('billing_type', ['rutin_perbulan', 'manual'])
            ->whereYear('billing_generated_date', $year)
            ->whereMonth('billing_generated_date', $month)
            ->select(
                'billing_id',
                'billing_name',
                DB::raw('DATE_FORMAT(billing_generated_date, "%Y-%m") as period'),
                DB::raw('SUM(payment_amount) as total_payment'),
                DB::raw('COUNT(DISTINCT user_id) as jumlah_pembayar')
            )
            ->groupBy('billing_id', 'billing_name', 'period')
            ->orderBy('billing_name', 'asc');

        $incomePerIuran = $query->get();

        // Calculate grand total
        $totalPemasukan = Tagihan::where('organization_id', auth()->user()->organization_id)
            ->where('payment_amount', '>', 0)
            ->whereIn('billing_type', ['rutin_perbulan', 'manual'])
            ->whereYear('billing_generated_date', $year)
            ->whereMonth('billing_generated_date', $month)
            ->sum('payment_amount');

        // Format period for display
        $periodDisplay = date('F Y', mktime(0, 0, 0, $month, 1, $year));

        return view('admin.report.income_per_iuran', compact('incomePerIuran', 'totalPemasukan', 'month', 'year', 'periodDisplay'));
    }

    /**
     * Get user details for a specific billing and period
     */
    public function getUserDetails(Request $request)
    {
        $billingId = $request->get('billing_id');
        $month = $request->get('month');
        $year = $request->get('year');
        
        $organizationId = auth()->user()->organization_id;

        // Get all users who have this billing (from tagihan table) for the selected period
        $userDetails = Tagihan::where('organization_id', $organizationId)
            ->where('billing_id', $billingId)
            ->whereYear('billing_generated_date', $year)
            ->whereMonth('billing_generated_date', $month)
            ->select(
                'user_id',
                'user_name',
                'user_blok_number',
                'billing_amount',
                'payment_amount as paid_amount',
                DB::raw("SUBSTRING_INDEX(user_blok_number, '-', 1) as blok_name_sort"),
                DB::raw("CAST(SUBSTRING_INDEX(user_blok_number, '-', -1) AS UNSIGNED) as blok_number_sort")
            )
            ->orderBy('payment_amount', 'desc')
            ->orderBy('blok_name_sort', 'asc')
            ->orderBy('blok_number_sort', 'asc')
            ->orderBy('user_name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $userDetails
        ]);
    }
}
