<?php 

namespace App\Imports; 

use App\Models\User;
use Hash;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Log;

class UserImport implements ToCollection, ImportDataInterface
{
    use ImportData;

    private $Users;
    
    public function validate($row)
    {
        return $this->checkHeader([
            'blok',
            'nama',
            'phone_number',
            'email',
            'notes',
        ], $row);
    }

    public function processData(Collection $rows)
    {
        $this->users = User::where('organization_id', auth()->user()->organization_id)->warga()->get();

        foreach ($rows as $key => $row) 
        {
            try {
                $saved = $this->saveUser($key, $row);
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

    protected function saveUser($key, $row)
    {
        if ($key == 0) 
        {
            return false;
        }

        $usernameInput = strtolower($row[0]); 
        $usernameInput = str_replace('-', '', $usernameInput);
        if (trim($usernameInput) == "")
        {
            return false;
        }

        $blokInput = strtoupper($row[0]); 
        $namaInput = $row[1]; 
        $phoneNumberInput = $row[2];
        $emailInput = $row[3]; 
        $emailInput = filter_var($emailInput, FILTER_VALIDATE_EMAIL) ? $emailInput : ($usernameInput . config('default.hostmail')); 
        $notes = $row[4];

        $user = $this->users->first(function($value, $key) use ($usernameInput) {
            return $value->username == $usernameInput && $value->organization_id == auth()->user()->organization_id;
        });

        $organizationId = auth()->user()->organization_id;

        $newData = false;
        if (!$user) {
            $user = new User();
            $user->username = $usernameInput; 
            $user->organization_id = $organizationId;
            $user->default_password = $this->generateRandomString();
            $user->password = Hash::make($user->default_password);
            $newData = true;
        }
        
        $user->blok = $blokInput; 
        $user->name = $namaInput;
        if (!empty($phoneNumberInput)) {
            $user->phone_number = $phoneNumberInput;
        }

        if (!empty($emailInput)) {
            $user->email = $emailInput; 
        }

        if (!empty($notes)) {
            $user->notes = $notes;
        }

        $user->save();

        if ($newData) {
            $user->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => 'User dibuat melalui import data',
                'url_link' => '/admin/master/warga/view/' . $user->id,
            ]);
        } else {
            $user->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => 'User diupdate melalui import data',
                'url_link' => '/admin/master/warga/view/' . $user->id,
            ]);
        }

        return true;
    }

    public function generateRandomString($length = 6) 
    {
        return substr(str_shuffle(str_repeat($x='0123456789', ceil($length/strlen($x)) )), 1, $length);
    }
}