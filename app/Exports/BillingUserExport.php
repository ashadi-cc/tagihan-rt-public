<?php 

namespace App\Exports; 

use App\Models\Billing; 
use App\Models\BillingUser;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BillingUserExport implements FromCollection, WithMapping, WithHeadings
{
    private $billing; 

    public function setBilling($billingId)
    {
        $this->billing = Billing::findOrFail($billingId);
    }

    public function collection()
    {
        return BillingUser::where([
                'billing_id' => $this->billing->id, 
                'month' => request('month'), 
                'year' => request('year'),
            ])->orderBy('blok_name')->orderBy('blok_number')->get();
    }

    public function headings(): array
    {
        return [
            'BLOK Rumah',
            'NOMINAL',
            'STATUS',
        ];
    }

    public function map($billing): array
    {
        return [
            $billing->user_blok,
            $billing->amount,
            $this->mapStatus($billing->status),
        ];
    }

    private function mapStatus($status) 
    {
        if ($status == 'B') return 'Belum Lunas';
        if ($status == 'L') return 'Lunas';
        if ($status == 'T') return 'Tidak Wajib';

        return 'N/A';
    }


    public function getTemplateFileName()
    {
        $name = strtolower($this->billing->name); 
        $name = str_replace(' ', '_', trim($name));

        $year  = strtolower(request('year')) ?: ''; 
        $month = strtolower(request('month')) ?: ''; 

        return 'report_iuran_'. $name . '_'. $month .'_'. $year. '.xlsx';
    }
}