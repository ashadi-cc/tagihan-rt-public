<?php 

namespace App\Utils\Upload; 

interface UploadInterface
{
    public function preProcessUpload($file);
    public function getType();
}