<?php 

namespace App\Utils\Upload; 

use App\Models\Upload as UploadModel;
use App\Models\User;

trait Upload 
{

    protected $user; 

    protected $uploadType = '';

    protected $result;

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getResult()
    {
        return $this->result;
    }


    public function process($file)
    {
        $success = $this->preProcessUpload($file); 

        if (!$success) {
            return false;
        }

        $this->afterUpload($file);

        return true;
    }

    //implemented this on each type upload
    public function preProcessUpload($file)
    {
        return true;
    }

    protected function afterUpload($file)
    {
        $this->createLog($file); 
    }

    public function getType()
    {
        return $this->uploadType; 
    }

    public function createLog($file)
    {
        $name = $file->getClientOriginalName(); 
        $this->user->uploads()->create([
            'file_name' => $name,
            'file_type' => $this->getType(),
        ]);
    }

    public static function NewUpload(User $user)
    {
        $upload = New self();
        $upload->setUser($user);

        return $upload;
    }

}