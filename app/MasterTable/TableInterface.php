<?php 

namespace App\MasterTable; 

use Illuminate\Http\Request;

interface TableInterface
{
    public function setHeaderCaption() :array; 
    public function setHeaderData() :array;
    public function getData(Request $request); 
    public function delete($recordId);
    public function edit(Request $request, $idRecord);
}