<?php 

namespace App\Exports; 

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;
use App\MasterTable\TableBulanan;

class IuranPerMonthExport implements FromCollection, WithMapping, WithHeadings
{

    private $table;
    private $data;

    public function  getData(Request $request)
    {
        $this->table = TableBulanan::NewTable(); 
        $this->data = $this->table->getData($request); 
    }

    public function collection()
    {
        return collect($this->data); 
    }

    public function map($row): array
    {
        $result = []; 

        foreach($row as $key => $r)
        {
            if ($key == 'blok') {
                $result[] = $r;
            } else {
                if ($r == false) {
                    $result[] = '0';
                    $result[] = 'N/A';
                } else {
                    $amount = $r['amount'];
                    $status = $r['status'];

                    switch ($r['status']) {
                        case 'L':
                            $amount = $amount; 
                            $status = 'Lunas';
                        break;
                        case 'B': 
                            $amount = $amount;
                            $status = 'Belum Lunas';
                        break;
                        case 'T':
                            $amount = '0'; 
                            $status = 'Tidak Wajib';
                        break;
                        default:
                            $amount = '0';
                    }

                    $result[] = $amount; 
                    $result[] = $status;
                }
            }
        }

        return $result; 
    }

    public function headings(): array
    {
        $rawHeader = $this->table->getHeaderCaption(); 
        $headers = explode(',', $rawHeader); 
        $resultHeader = [];

        foreach ($headers as $key => $header)
        {
            $resultHeader[] = $header; 
            if ($key > 0) {
                $resultHeader[] = 'Status';
            }
        }

        return $resultHeader;
    }

    public function getTemplateFileName()
    {
        $year  = strtolower(request('year')) ?: ''; 
        $month = strtolower(request('month')) ?: ''; 


        return 'tagihan_bulanan_'. $month .'_'. $year. '.xlsx';
    }
}