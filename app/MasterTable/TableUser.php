<?php 

namespace App\MasterTable; 

use App\Models\User; 
use App\Models\Tagihan;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Log;
use Hash;


class TableUser implements TableInterface
{
    use Table;

    public function setHeaderCaption() :array 
    {
        return [
            'Username',
            'Blok',
            'Nama',
            'Telepon',
            'Tagihan',
            'WA Register?',
            'Admin?',
        ];
    }

    public function setHeaderData() : array 
    {
        return [
            'id',
            'username',
            'blok',
            'name',
            'phone_number',
            'tunggakan',
            'wa_register',
            'is_admin',
        ];
    }

    public function create(Request $request)
    {
        $oganization = auth()->user()->organization;

        $validation = [
            'nama' => 'required',
            'blok' => 'required|regex:/^[A-Za-z]+-\d+$/',
            'password' => 'required|min:5',
        ];

        if ($request->email) {
            $validation['email'] = 'required|email|unique:users,email';
        }

        if ($request->phone_number) {
            $validation['phone_number'] = 'nullable|string|unique:users,phone_number';
        }

        $request->validate($validation);

        $parseUsername = strtolower($request->blok);
        $parseUsername = str_replace('-', '', $parseUsername);

        if (User::where('organization_id', $oganization->id)->where('username', $parseUsername)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'blok' => ['Blok sudah terdaftar, silakan gunakan blok lain.'],
            ]);
        }

        $user = new User();
        $user->organization_id = $oganization->id;
        $user->username = $parseUsername;
        $user->blok = strtoupper($request->blok);
        $user->name = $request->nama;
        $user->phone_number = $request->phone_number ? $request->phone_number : null;
        $user->email = $request->email ? $request->email : ($parseUsername . config('default.hostmail'));
        $user->password = Hash::make($request->password);
        $user->jabatan = $request->jabatan ? $request->jabatan : null;
        $user->notes = $request->notes ? $request->notes : null;
        
        if ($request->is_admin) {
            $user->assignRole('admin');
        }
        
        $user->save();

        // create log since new user created
        $user->logs()->create([
            'organization_id' => auth()->user()->organization_id,
            'username' => auth()->user()->name,
            'description' => "User baru dengan username '{$user->username}' telah dibuat",
            'url_link' => '/admin/master/warga/view/' . $user->id,
        ]);

    }

    public function edit(Request $request, $idRecord)
    {
        $oganization = auth()->user()->organization;
        $user = User::where('organization_id', $oganization->id)->findOrFail($idRecord);

        // Track changes for logging
        $changes = [];

        $validation = [
            'nama' => 'required',
        ]; 

        if ($request->jabatan != $user->jabatan) {
            $changes[] = "Jabatan diubah dari '{$user->jabatan}' menjadi '{$request->jabatan}'";
        }
        $user->jabatan = $request->jabatan;

        if ($request->email) {
            $validation['email'] = 'required|email|unique:users,email,'. $user->id;
            if ($user->email != $request->email) {
                $changes[] = "Email diubah dari '{$user->email}' menjadi '{$request->email}'";
            }
            $user->email = $request->email; 
        }

        if ($request->phone_number) {
            $validation['phone_number'] = 'nullable|string|unique:users,phone_number,'. $user->id;
            if ($user->phone_number != $request->phone_number) {
                $oldPhone = $user->phone_number ?: '(kosong)';
                $changes[] = "Nomor telepon diubah dari '{$oldPhone}' menjadi '{$request->phone_number}'";
            }
            $user->phone_number = $request->phone_number; 
        } else {
            if ($user->phone_number != null) {
                $changes[] = "Nomor telepon dihapus (sebelumnya: '{$user->phone_number}')";
            }
            $user->phone_number = null; 
        }

        if ($user->name != $request->nama) {
            $changes[] = "Nama diubah dari '{$user->name}' menjadi '{$request->nama}'";
            Tagihan::where('user_id', $user->id)->update(['user_name' => $request->nama]);
            Pembayaran::where('user_id', $user->id)->update(['user_name' => $request->nama]);
        }

        if ($user->notes != $request->notes) {
            $oldNotes = $user->notes ?: '(kosong)';
            $newNotes = $request->notes ?: '(kosong)';
            $changes[] = "Catatan diubah dari '{$oldNotes}' menjadi '{$newNotes}'";
        }
        $user->notes = $request->notes;

        if ($request->password) {
            $validation['password'] = 'required|min:5';
            $user->password = Hash::make($request->password);
            $user->default_password = ''; 
            $changes[] = "Password diubah";
        }

        $request->validate($validation); 
        $user->name = $request->nama; 

        $user->save();

        // Check admin role changes
        $wasAdmin = $user->hasRole('admin');
        if (auth()->user()->id != $user->id) {
            if ($request->is_admin) {
                $user->assignRole('admin');
                if (!$wasAdmin) {
                    $changes[] = "Role admin ditambahkan";
                }
            } else {
                $user->removeRole('admin');
                if ($wasAdmin) {
                    $changes[] = "Role admin dihapus";
                }
            }
        }

        // Create log if there are changes
        if (!empty($changes)) {
            $description = "Data user diupdate:\n" . implode("\n", $changes);
            $user->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => $description,
                'url_link' => '/admin/master/warga/view/' . $user->id,
            ]);
        }
    }

    public function delete($userId)
    {
        $currentUser = auth()->user(); 
        if ($currentUser->id == $userId) {
            return false;
        }

        $organization = $currentUser->organization;

        try {
            $user = User::where('organization_id', $organization->id)->findOrFail($userId);
            $oldUsername = $user->username;
            $user->blok = 'delete-' . $user->id . $user->blok; 
            $user->username = 'delete-' .  $user->id . $user->username; 
            $user->email = 'delete-' .  $user->id . $user->email; 
            $user->save();
            
            //finally delete it
            $user->delete();

            // create log since user deleted
            $currentUser->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => $currentUser->name,
                'description' => "User dengan username '{$oldUsername}' telah dihapus",
                'url_link' => '/admin/master/warga/',
            ]);

            return true; 
        } catch(\Exception $e) {
            Log::error($e->getMessage()); 
            return false;
        }
    }

    public function getData(Request $request)
    {
        $organization = auth()->user()->organization;
        $query = User::warga()
            ->where('organization_id', $organization->id)
            ->with('roles')
            ->selectRaw('users.*, COALESCE((SELECT SUM(billing_amount - payment_amount) FROM tagihans WHERE tagihans.user_id = users.id), 0) as tunggakan')
            ->orderBy('blok_name')
            ->orderBy('blok_number');
        
        if (trim($request->get('q')) != "") {
            $search = '%'. trim($request->get('q')) . '%'; 
            $query->where(function($q) use ($search) {
                $q->where('blok', 'like', $search)
                ->orWhere('username', 'like', $search)
                ->orWhere('name', 'like', $search)
                ;
            });
        }

        $data = $query->get()->filter(function($value){
            return $value->username != 'admin';
        })->all();

        return array_map(function($value)
        {
            $tunggakan = ($value['tunggakan'] > 0) ? '<span class="text-danger font-weight-bold">Rp. '. number_format($value['tunggakan'], 0, ',', '.') .'</span>' : '<span class="text-success font-weight-bold">-</span>';
            return [
                'id' => $value['id'],
                'username' => $value['username'],
                'blok' => $value['blok'], 
                'name' => $value['name'],
                'phone_number' => $value['phone_number'],
                'email' => $value['email'],
                'tunggakan' => $tunggakan,
                'wa_register' => $value['whatsapp_id'] ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-secondary">Tidak</span>',
                'is_admin' => $this->isAdmin($value['roles']),
            ];

        }, $data);
    }

    private function isAdmin($roles)
    {
        foreach($roles as $role)
        {
            if ($role['name'] == 'admin')
            {
                return '<span class="badge badge-success">Ya</span>';
            }
        }

        return '<span class="badge badge-secondary">Tidak</span>';
    }
}