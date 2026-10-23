<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tagihan;
use App\Models\Billing;
use App\Exports\TagihanIuranPerWargaExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TagihanIuranPerWargaController extends Controller 
{
    /**
     * Display tagihan iuran per warga report
     */
    public function index(Request $request)
    {
        $query = Tagihan::where('tagihans.organization_id', auth()->user()->organization_id);

        // Apply filters
        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('billing_generated_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('billing_generated_date', '<=', $request->end_date);
        }

        // User name or blok filter
        if ($request->filled('user_search')) {
            $search = '%' . $request->user_search . '%';
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'like', $search)
                  ->orWhere('user_blok_number', 'like', $search);
            });
        }

        // Billing name filter
        if ($request->filled('billing_id')) {
            $query->where('billing_id', $request->billing_id);
        }

        // Status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'lunas':
                    // Fully paid: billing_amount - payment_amount <= 0
                    $query->whereRaw('billing_amount - payment_amount <= 0');
                    break;
                case 'kurang_bayar':
                    // Partially paid: payment_amount > 0 AND billing_amount - payment_amount > 0
                    $query->where('payment_amount', '>', 0)
                          ->whereRaw('billing_amount - payment_amount > 0');
                    break;
                case 'belum_bayar':
                    // Not paid: payment_amount = 0
                    $query->where('payment_amount', '=', 0);
                    break;
            }
        }

        // Calculate tunggakan (arrears) and add it to results
        $tagihans = $query->select(
                'tagihans.id',
                'billing_generated_date',
                'user_id',
                'user_name',
                'user_blok_number',
                'billing_name',
                'billing_amount',
                'payment_amount',
                DB::raw('(billing_amount - payment_amount) as tunggakan'),
                DB::raw("SUBSTRING_INDEX(user_blok_number, '-', 1) as blok_name_sort"),
                DB::raw("CAST(SUBSTRING_INDEX(user_blok_number, '-', -1) AS UNSIGNED) as blok_number_sort")
            )
            ->orderBy('billing_generated_date', 'asc')
            ->orderBy('blok_name_sort', 'asc')
            ->orderBy('blok_number_sort', 'asc')
            ->orderBy('user_id', 'asc')
            ->paginate(50);

        // Get all billing types for filter dropdown
        $billings = Billing::where('organization_id', auth()->user()->organization_id)
            ->where('disabled', false)
            ->orderBy('name', 'asc')
            ->get();

        // Calculate summary statistics
        $totalTagihan = 0;
        $totalPembayaran = 0;
        $totalTunggakan = 0;
        $countLunas = 0;
        $countCicilan = 0;
        $countBelumBayar = 0;

        // Always calculate summary (not just when filters applied)
        $summaryQuery = Tagihan::where('tagihans.organization_id', auth()->user()->organization_id);
            
        if ($request->filled('start_date')) {
            $summaryQuery->whereDate('billing_generated_date', '>=', $request->start_date);
        }
            
        if ($request->filled('end_date')) {
            $summaryQuery->whereDate('billing_generated_date', '<=', $request->end_date);
        }

        if ($request->filled('user_search')) {
            $search = '%' . $request->user_search . '%';
            $summaryQuery->where(function($q) use ($search) {
                $q->where('user_name', 'like', $search)
                  ->orWhere('user_blok_number', 'like', $search);
            });
        }

        if ($request->filled('billing_id')) {
            $summaryQuery->where('billing_id', $request->billing_id);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'lunas':
                    $summaryQuery->whereRaw('billing_amount - payment_amount <= 0');
                    break;
                case 'kurang_bayar':
                    $summaryQuery->where('payment_amount', '>', 0)
                                ->whereRaw('billing_amount - payment_amount > 0');
                    break;
                case 'belum_bayar':
                    $summaryQuery->where('payment_amount', '=', 0);
                    break;
            }
        }

        $summary = $summaryQuery->select(
            DB::raw('SUM(billing_amount) as total_tagihan'),
            DB::raw('SUM(payment_amount) as total_pembayaran'),
            DB::raw('SUM(billing_amount - payment_amount) as total_tunggakan')
        )->first();

        $totalTagihan = $summary->total_tagihan ?? 0;
        $totalPembayaran = $summary->total_pembayaran ?? 0;
        $totalTunggakan = $summary->total_tunggakan ?? 0;
            
        // Count status
        $countLunas = Tagihan::where('tagihans.organization_id', auth()->user()->organization_id)
            ->whereRaw('billing_amount - payment_amount <= 0');
        $countCicilan = Tagihan::where('tagihans.organization_id', auth()->user()->organization_id)
            ->where('payment_amount', '>', 0)
            ->whereRaw('billing_amount - payment_amount > 0');
        $countBelumBayar = Tagihan::where('tagihans.organization_id', auth()->user()->organization_id)
            ->where('payment_amount', '=', 0);
            
        // Apply same filters to count queries
        if ($request->filled('start_date')) {
            $countLunas->whereDate('billing_generated_date', '>=', $request->start_date);
            $countCicilan->whereDate('billing_generated_date', '>=', $request->start_date);
            $countBelumBayar->whereDate('billing_generated_date', '>=', $request->start_date);
        }
            
        if ($request->filled('end_date')) {
            $countLunas->whereDate('billing_generated_date', '<=', $request->end_date);
            $countCicilan->whereDate('billing_generated_date', '<=', $request->end_date);
            $countBelumBayar->whereDate('billing_generated_date', '<=', $request->end_date);
        }

        if ($request->filled('user_search')) {
            $search = '%' . $request->user_search . '%';
            $countLunas->where(function($q) use ($search) {
                $q->where('user_name', 'like', $search)->orWhere('user_blok_number', 'like', $search);
            });
            $countCicilan->where(function($q) use ($search) {
                $q->where('user_name', 'like', $search)->orWhere('user_blok_number', 'like', $search);
            });
            $countBelumBayar->where(function($q) use ($search) {
                $q->where('user_name', 'like', $search)->orWhere('user_blok_number', 'like', $search);
            });
        }

        if ($request->filled('billing_id')) {
            $countLunas->where('billing_id', $request->billing_id);
            $countCicilan->where('billing_id', $request->billing_id);
            $countBelumBayar->where('billing_id', $request->billing_id);
        }

            if ($request->filled('status')) {
                switch ($request->status) {
                case 'lunas':
                    $countLunas->whereRaw('billing_amount - payment_amount <= 0');
                    $countCicilan->whereRaw('1 = 0'); // No results
                    $countBelumBayar->whereRaw('1 = 0'); // No results
                    break;
                case 'kurang_bayar':
                    $countLunas->whereRaw('1 = 0'); // No results
                    $countCicilan->where('payment_amount', '>', 0)
                                ->whereRaw('billing_amount - payment_amount > 0');
                    $countBelumBayar->whereRaw('1 = 0'); // No results
                    break;
                case 'belum_bayar':
                    $countLunas->whereRaw('1 = 0'); // No results
                    $countCicilan->whereRaw('1 = 0'); // No results
                    $countBelumBayar->where('payment_amount', '=', 0);
                    break;
            }
        }
        
        $countLunas = $countLunas->count();
        $countCicilan = $countCicilan->count();
        $countBelumBayar = $countBelumBayar->count();

        return view('admin.report.tagihan_iuran_per_warga', compact(
            'tagihans', 
            'billings',
            'totalTagihan',
            'totalPembayaran',
            'totalTunggakan',
            'countLunas',
            'countCicilan',
            'countBelumBayar'
        ));
    }

    /**
     * Export to Excel
     */
    public function export(Request $request)
    {
        // Validate that date range is provided
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'start_date.required' => 'Tanggal mulai wajib diisi untuk export Excel',
            'end_date.required' => 'Tanggal akhir wajib diisi untuk export Excel',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai',
        ]);

        $export = new TagihanIuranPerWargaExport();
        $export->setFilters($request);
        
        return Excel::download($export, $export->getFileName());
    }
}
