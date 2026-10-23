<?php 

namespace App\Utils\Upload; 

use App\Models\User;
use App\Imports\BillingImport;
use Excel;
use Log; 

class UploadBilling implements UploadInterface
{
    /**
     * use upload trait
     */
    use Upload; 

    /**
     * this should return true or false
     */
    public function preProcessUpload($file)
    {
        try {
            $billingImport = new BillingImport; 
            Excel::import($billingImport, $file);

            //get result of import status
            $this->result = $billingImport->getResult();

            return true; 
        } catch(\Exception $e) {
            Log::error($e->getMessage()); 
            
            return false; 
        }
    }

    public function getType()
    {
        return 'billing';
    }

}