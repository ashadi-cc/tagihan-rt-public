<?php 

namespace App\MasterTable; 

use App\Models\BillingUser; 
use Illuminate\Http\Request;
use Log;

class TableTagihan implements TableInterface
{
    use Table;

    public function setHeaderCaption() :array 
    {
        return [
        ];
    }

    public function setHeaderData() : array 
    {
        return [
        ];
    }

    public function getData(Request $request)
    {
        $query = BillingUser::where([
            'billing_id' => $request->billing_id,
            'month' => $request->month, 
            'year' => $request->year,
        ])
        ->orderBy('blok_name')->orderBy('blok_number');

        if (trim($request->get('q')) != "") {
            $query->where('user_blok', 'like', '%'. $request->q . '%');
        }

        return $query->get(); 
    }

    public function delete($billingId)
    {
        try {
            $billing = BillingUser::findOrFail($billingId); 
            $billing->delete(); 

            return true;
        } catch(\Exception $e) {
            Log::error($e->getMessage());

            return false;
        }
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

}