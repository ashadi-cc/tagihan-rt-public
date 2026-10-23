<?php 

namespace App\Imports; 

use App\Models\BillingUser;
use App\Models\Billing; 
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Log;

class BillingUserImport implements ToCollection, ImportDataInterface
{
    use ImportData;

    private $year; 
    private $month; 
    private $billingId; 
    private $users;
    private $billings;
    private $billing;
    private $currentUser;

    private function setMaster()
    {
        $this->users = User::warga()->get(); 
        $this->billing = Billing::findOrFail($this->billingId);

        $this->billings = BillingUser::where([
            'billing_id' => $this->billingId,
            'month' => $this->month, 
            'year' => $this->year,
        ])->get(); 

        $this->currentUser = auth()->user();
    }

    public function __construct($month, $year, $billingId)
    {
        $this->month = $month; 
        $this->year = $year; 
        $this->billingId = $billingId; 
        
        $this->setMaster();
    }

    public function validate($row)
    {
       return $this->checkHeader([
           'blok',
           'nominal',
           'status (b/l/t)',
       ], $row);
    }

    public function processData(Collection $rows)
    {
        foreach ($rows as $key => $row) 
        {
            try {
                $saved = $this->saveBilling($key, $row);
                if ($saved) {
                    $this->successImport[] = [
                        'index' => ($key + 1), 
                        'data' => $row
                    ]; 
                }
                
            } catch(\Exception $e) {
                Log::error($e->getMessage()); 
                
                $this->failImport[] = [
                    'index' => ($key + 1), 
                    'data' => $row
                ]; 
            }
        }
    }

    protected function saveBilling($key, $row)
    {
        if ($key == 0) 
        {
            return false;
        }

        $blokInput      = strtoupper($row[0]); 
        $amountInput    = is_numeric($row[1]) ? $row[1]: '0'; 
        $statusInput    = $this->mapStatus($row[2]);

        if (trim($blokInput) == "") {
            return false;
        }

        //check user blok first 
        $foundUser = $this->users->first(function($value, $key) use ($blokInput){
            return $value->blok == $blokInput;
        });

        if (!$foundUser) {
            throw new \Exception('user not found '. $blokInput);
        }

        $billingUser = $this->billings->first(function($value, $key) use ($foundUser) {
            return $value->user_id == $foundUser->id; 
        });

        if (!$billingUser) {
            $billingUser = new BillingUser;
        }

        $billingUser->user_id = $foundUser->id; 
        $billingUser->billing_id = $this->billingId; 
        $billingUser->user_blok = $foundUser->blok; 
        $billingUser->billing_name = $this->billing->name; 
        $billingUser->month = $this->month; 
        $billingUser->year = $this->year; 
        $billingUser->amount = $amountInput; 
        $billingUser->status = $statusInput;
        $billingUser->blok_name = $foundUser->blok_name;
        $billingUser->blok_number = $foundUser->blok_number; 
        
        $billingUser->changed_by = 'user'; 
        $billingUser->changed_user_id = $this->currentUser->id; 

        /**Save billing */
        $billingUser->save();

        return true;
    }

    private function mapStatus($status)
    {
        $status = trim(strtoupper($status));
        if ($status == "B") return "B"; 
        if ($status == "L") return "L";
        if ($status == "T") return "T";

        return ""; 
    }

}