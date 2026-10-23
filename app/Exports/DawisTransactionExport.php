<?php

namespace App\Exports;
use App\Models\DawisTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DawisTransactionExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        $organization = auth()->user()->organization;

        $query = DawisTransaction::where('organization_id', $organization->id)
            ->orderBy('payment_date', 'desc');

        // Search functionality
        if (trim(request()->get('q')) != "") {
            $search = '%'. trim(request()->get('q')) . '%';
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('created_by', 'like', $search);
            });
        }

        // Filter by type (income/expense)
        if (request()->get('type') == 'income') {
            $query->where('credit', '>', 0)->where('debit', 0);
        } elseif (request()->get('type') == 'expense') {
            $query->where('debit', '>', 0)->where('credit', 0);
        }

        // Filter by date range
        if (request()->get('start_date')) {
            $query->whereDate('payment_date', '>=', request()->get('start_date'));
        }
        if (request()->get('end_date')) {
            $query->whereDate('payment_date', '<=', request()->get('end_date'));
        }

        return $query->paginate(10000);
    }

    public function map($transaction): array
    {
        return [
            $transaction->payment_date->format('Y-m-d'),
            $transaction->debit > 0 ? 'Pengeluaran' : 'Pemasukan',
            $transaction->name,
            $transaction->description,
            $transaction->debit > 0 ? intval($transaction->debit) : intval($transaction->credit),
            $transaction->created_by,
        ];
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Jenis',
            'Nama',
            'Keterangan',
            'Jumlah',
            'Dibuat Oleh',
        ];
    }
}
