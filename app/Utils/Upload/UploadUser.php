<?php 

namespace App\Utils\Upload; 

use App\Models\User;
use App\Imports\UserImport;
use Excel;
use Log; 

class UploadUser implements UploadInterface
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
            $userImport = new UserImport; 
            Excel::import($userImport, $file);

            //get result of import status
            $this->result = $userImport->getResult();

            return true; 
        } catch(\Exception $e) {
            Log::error($e->getMessage()); 
            
            return false; 
        }
    }

    public function getType()
    {
        return 'user';
    }

}