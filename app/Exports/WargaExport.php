<?php 


namespace App\Exports; 

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WargaExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        $organizationId = auth()->user()->organization_id;
        return  User::where('organization_id', $organizationId)->warga()->orderBy('blok_name')->orderBy('blok_number')->get();
    }

    public function map($user): array
    {
        return [
            $user->blok, 
            $user->name,
            $user->phone_number,
            $user->email,
            $user->notes,
        ];
    }

    public function headings(): array
    {
        return [
            'Blok',
            'Nama',
            'Phone_Number',
            'Email',
            'Notes',
        ];
    }

}