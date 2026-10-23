<?php 

namespace App\Utils\Upload; 

use App\Models\User;
use App\Imports\BillingUserImport;
use Excel;
use Log; 

class UploadBillingUser implements UploadInterface
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
            $billingImport = new BillingUserImport(
                request('bulan'),
                request('tahun'),
                request('jenis')
            ); 
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
        return 'billing_user';
    }

}