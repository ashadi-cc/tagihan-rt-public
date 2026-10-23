<?php 

namespace App\Exports; 

use App\Models\Billing; 
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BillingTemplateExport implements FromCollection, WithMapping, WithHeadings
{
    private $billing; 

    public function collection()
    {
        return User::warga()->orderBy('blok_name')->orderBy('blok_number')->get();
    }

    
    public function map($user): array
    {
        return [
            $user->blok,
            $this->billing->amount,
            'B'
        ];
    }

    public function headings(): array
    {
        return [
            'BLOK',
            'NOMINAL',
            'STATUS (B/L/T)',
        ];
    }

    public function setBilling(Billing $billing)
    {
        $this->billing = $billing;
    }

    public function getTemplateFileName()
    {
        $name = strtolower($this->billing->name); 
        $name = str_replace(' ', '_', trim($name));

        $year  = strtolower(request('year')) ?: ''; 
        $month = strtolower(request('month')) ?: ''; 


        return 'template_iuran_'. $name . '_'. $month .'_'. $year. '.xlsx';
    }
}