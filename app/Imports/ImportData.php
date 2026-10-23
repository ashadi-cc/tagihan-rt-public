<?php 
namespace App\Imports; 

use Illuminate\Support\Collection;

trait ImportData
{
    private $successImport = array(); 

    private $failImport = array(); 

    public function getResult()
    {
        return [
            'success' => $this->successImport,
            'fail' => $this->failImport,
        ];
    }

    public function validate($row)
    {
        return true;
    }

    protected function checkHeader(Array $headers, $row)
    {
        $countHeader = count($headers);
        if (count($row) < $countHeader)
        {
            return false; 
        }
        
        foreach ($headers as $key => $header)
        {
            $headerCheck = trim(strtolower($row[$key])); 
            $header = trim(strtolower($header)); 

            if ($header != $headerCheck)
            {
                return false;
            }
        }

        return true;
    }

    public function collection(Collection $rows)
    {
        //dont do process when no rows
        if (count($rows) == 0 ) 
        {
            return;
        }

        $validate = $this->validate($rows[0]); 
        if (!$validate) {
            throw new \Exception("format file is not valid");
        }

        $this->processData($rows);
    }

    public function processData(Collection $rows)
    {

    }
}