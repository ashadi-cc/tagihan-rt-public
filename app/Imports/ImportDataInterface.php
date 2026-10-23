<?php 

namespace App\Imports; 

use Illuminate\Support\Collection;

interface ImportDataInterface
{
    public function validate($row); 
    public function processData(Collection $rows);
}