<?php 

namespace App\Exports;

use App\Models\Tagihan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class TagihanIuranPerWargaExport implements FromCollection, WithMapping, WithHeadings
{
    protected $request;

    public function setFilters($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Tagihan::where('tagihans.organization_id', auth()->user()->organization_id);

        // Apply filters
        if ($this->request->filled('start_date')) {
            $query->whereDate('billing_generated_date', '>=', $this->request->start_date);
        }
        
        if ($this->request->filled('end_date')) {
            $query->whereDate('billing_generated_date', '<=', $this->request->end_date);
        }

        if ($this->request->filled('user_search')) {
            $search = '%' . $this->request->user_search . '%';
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'like', $search)
                  ->orWhere('user_blok_number', 'like', $search);
            });
        }

        if ($this->request->filled('billing_id')) {
            $query->where('billing_id', $this->request->billing_id);
        }

        // Status filter
        if ($this->request->filled('status')) {
            switch ($this->request->status) {
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

        // Get all records (no pagination for export)
        return $query->select(
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
            ->get();
    }

    public function map($tagihan): array
    {
        // Determine status based on payment
        $isPaid = $tagihan->tunggakan <= 0;
        if ($isPaid) {
            $status = 'Lunas';
        } elseif ($tagihan->payment_amount > 0) {
            $status = 'Kurang Bayar';
        } else {
            $status = 'Belum Bayar';
        }

        return [
            $tagihan->billing_generated_date->format('Y-m-d'),
            $tagihan->user_name,
            $tagihan->user_blok_number,
            $tagihan->billing_name,
            intval($tagihan->billing_amount),
            intval($tagihan->payment_amount),
            intval($tagihan->tunggakan),
            $status,
        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal Tagihan',
            'Nama Warga',
            'Blok',
            'Jenis Iuran',
            'Jumlah Tagihan',
            'Jumlah Pembayaran',
            'Tunggakan',
            'Status',
        ];
    }

    public function getFileName()
    {
        $date = date('Y-m-d');
        $filename = 'tagihan_iuran_per_warga_' . $date;
        
        if ($this->request->filled('start_date') || $this->request->filled('end_date')) {
            $start = $this->request->start_date ?? 'all';
            $end = $this->request->end_date ?? 'all';
            $filename .= '_' . $start . '_to_' . $end;
        }
        
        return $filename . '.xlsx';
    }
}
