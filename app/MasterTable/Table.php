<?php 

namespace App\MasterTable; 

trait Table 
{
    public static function NewTable()
    {
        $table = new self(); 

        return $table;
    }

    public function getHeaderCaption() :string 
    {
        return implode(',', $this->setHeaderCaption());
    }

    public function getHeaderData() :string 
    {
        return implode(',', $this->setHeaderData());
    }

}