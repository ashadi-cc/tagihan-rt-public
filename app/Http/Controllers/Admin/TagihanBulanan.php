<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\MasterTable\TableBulanan;
use Excel;
use App\Exports\IuranPerMonthExport;


class TagihanBulanan extends Controller 
{
    use Util; 

    public function index()
    {
        $options = $this->getFilterYearandMonth(); 
        
        return view('admin.master.list_bulanan', ['options' => $options]);
    }

    public function getData(Request $request)
    {
        $table = TableBulanan::NewTable(); 
        $data = $table->getData($request);
        $headerColumn = $table->getHeaderCaption(); 
        $headerData = $table->getHeaderData();

        return [
            'data' => $data,
            'headers' => $headerColumn, 
            'columns' => $headerData,
        ];
    }

    public function edit(Request $request, $idRecord)
    {
        $table = TableBulanan::NewTable(); 
        $success = $table->edit($request, $idRecord); 

        return ['success' => true];
    }

    public function summary(Request $request)
    {
        $table = TableBulanan::NewTable(); 
        $data = $table->summary($request); 

        return $data;
    }

    public function byUser(Request $request)
    {
        $table = TableBulanan::NewTable(); 
        
        return $table->getTagihanByUser($request);
    }

    public function download(Request $request)
    {
        $tagihan = new IuranPerMonthExport;
        $tagihan->getData($request); 

        return Excel::download($tagihan, $tagihan->getTemplateFileName());
    }

}