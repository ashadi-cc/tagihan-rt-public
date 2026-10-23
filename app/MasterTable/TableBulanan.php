<?php 

namespace App\MasterTable; 

use App\Models\BillingUser; 
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Util;
use Log;


class TableBulanan implements TableInterface
{
    use Table, Util;

    private $masterBilling; 
    private $users; 
    private $headers; 

    private function generateHeader()
    {
        $headers = ['Blok'];
        $headerData = [[
            'header' => 'Blok',
            'value' => 'blok',
        ]];
        $users = []; 

        foreach($this->masterBilling as $item) {
            $billName = $item->billing_name; 
            $billId = 'b_' . $item->billing_id; 

            if (!in_array($billName, $headers)) {
                $headers[] = $billName; 
                $headerData[] = [
                    'header' => $billName,
                    'value' => $billId,
                ];
            }

            if (!in_array($item->user_blok, $users)) {
                $users[] = $item->user_blok;
            }
        } 

        $this->users = $users; 
        $this->headers = $headerData;
    }

    private function generatePivot()
    {
        $columns = $this->setHeaderData();

        $records = [];
        foreach($this->users as $user)
        {
            $row = [
                'blok' => $user
            ]; 

            foreach($columns as $column)
            {
                if ($column != 'blok') {
                    $billingId = str_replace('b_', '', $column); 
                    $found = $this->masterBilling->first(function($value, $key) use($user, $billingId) {
                        if ($value->user_blok == $user && $value->billing_id == $billingId) {
                            return true;
                        }

                        return false;
                    });

                    if ($found) {
                        $row[$column] = [
                            'id' => $found->id, 
                            'amount' => $found->amount,
                            'status' => $found->status,
                        ];
                    } else {
                        $row[$column] = false;
                    }
                }
            }

            $records[] = $row;
        }

        return $records; 
    }

    public function setHeaderCaption() :array 
    {
        return array_map(function($value) {
            return $value['header'];
        }, $this->headers);
    }

    public function setHeaderData() : array 
    {
        return array_map(function($value) {
            return $value['value'];
        }, $this->headers);
    }

    public function getData(Request $request)
    {
        $year = $request->year ?:'0';
        $month = $request->month ?:'0';
        $query = BillingUser::where([
            'year' => $year,
            'month' => $month,
        ])
        ->orderBy('blok_name')->orderBy('blok_number');

        if (trim($request->q)) {
            $query->where('user_blok', 'like', '%'. $request->q . '%');
        }

        $this->masterBilling = $query->get();

        $this->generateHeader();

        return $this->generatePivot();
    }

    public function delete($billingId)
    {

    }

    public function edit(Request $request, $idRecord)
    {
        try {
            $billing = BillingUser::findOrFail($idRecord); 

            if ($request->amount) {
                $billing->amount = $request->amount;
            }

            if ($request->status) {
                $billing->status = $request->status; 
            }

            $billing->changed_by = 'user'; 
            $billing->changed_user_id = auth()->user()->id;

            $billing->save(); 

            return true; 
        } catch(\Exception $e) {
            Log::error($e->getMessage());

            return false;
        }
    }

    public function summary(Request $request)
    {
        $year = $request->year ?:'0';
        $month = $request->month ?:'0';
        $query = $billings = BillingUser::where([
            'year' => $year,
            'month' => $month,
        ]); 

        if (trim($request->q)) {
            $query->where('user_blok', 'like', '%'. $request->q . '%');
        }

        $billings = $query->get();

        $idBillings = collect(explode(',', $request->summary))->filter(function($value, $key){
            return $value != 'blok';
        })->map(function($value) {
            return str_replace('b_', '', $value);
        }); 

        $data = []; 

        foreach ($idBillings as $bilId) {
            $data[] = [
                'billing_id' => $bilId,
                'lunas' => $this->sumByStatus($billings, $bilId, 'L'),
                'belum' => $this->sumByStatus($billings, $bilId, 'B'),
            ];
        }

        return $data;
    }

    public function getTagihanByUser(Request $request)
    {
        $user = User::where('blok', $request->blok)->first(); 

        if (!$user) {
            throw new \Exception('user not found ', $request->blok); 
        }

        $year   = intval(date('Y'));
        $month  = intval(date('n'));

        $billings = $user->billings()->orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        $userDisplay =  [
            'id' => $user->id, 
            'blok' => $user->blok, 
            'name' => $user->name, 
        ];

        $otherBill = [];
        $thisMonth = [];

        foreach($billings as $value) {
            $value->month_name = $this->getMonthName($value->month); 
            if (($value->month == $month) && ($value->year == $year)) {
                $thisMonth[] = $value;
            } else {
                $otherBill[] = $value;
            }
        }

        return [
            'user' => $userDisplay,
            'thisMonth' => $thisMonth,
            'otherMonth' => $otherBill,
            'currentMonth' => $this->getMonthName($month),
        ];
    }

    private function sumByStatus($billings, $bilId, $status)
    {
        return $billings->filter(function($value, $key) use($bilId, $status){
            return ($value->billing_id) == $bilId && ($value->status == $status);
        })->sum('amount');
    }

}