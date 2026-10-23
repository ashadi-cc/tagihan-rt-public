<?php 

namespace App\MasterTable; 

use App\Models\Billing;
use Illuminate\Http\Request;
use App\Jobs\BuatTagihanRutinJob;
use Log;


class TableBilling implements TableInterface
{
    use Table;

    public function setHeaderCaption() :array 
    {
        return [
            'Nama',
            'Nominal',
            'Prioritas',
            'Jenis Tagihan',
            'Active'
        ];
    }

    public function setHeaderData() : array 
    {
        return [
            'id',
            'name',
            'amount',
            'priority',
            'billing_type',
            'disabled'
        ];
    }

    public function create(Request $request)
    {
        $organization = auth()->user()->organization;

        $request->validate([
            'nama' => 'required|max:100',
            'nominal' => 'required|numeric|min:0',
            'billing_type' => 'required|in:rutin_perbulan,manual',
            'description' => 'nullable|max:500',
            'priority' => 'required|numeric|min:1',
        ]);

        $billing = new Billing();
        $billing->organization_id = $organization->id;
        $billing->name = $request->nama;
        $billing->description = $request->description;
        $billing->type = 'user'; // Set as user type by default
        $billing->billing_type = $request->billing_type;
        $billing->disabled = $request->has('disabled') ? false : true; // checkbox active means not disabled
        $billing->amount = $request->nominal;
        $billing->priority = $request->priority;
        
        $billing->save();

        $userIds = $request->input('user_ids', []);
        $billing->users()->attach($userIds);

        // Create log for new billing
        $billing->logs()->create([
            'organization_id' => auth()->user()->organization_id,
            'username' => auth()->user()->name,
            'description' => "Iuran baru dengan nama '{$billing->name}' telah dibuat (Tipe: " . ($billing->billing_type == 'rutin_perbulan' ? 'Rutin Per Bulan' : 'Manual') . ")",
            'url_link' => '/admin/master/tagihan',
        ]);

        return $billing;
    }

    public function getData(Request $request)
    {
        $query = Billing::where('type', 'user')->where('organization_id', auth()->user()->organization->id)->orderBy('name', 'asc');
        
        if (trim($request->get('q')) != "") {
            $search = '%'. trim($request->get('q')) . '%'; 
            $query->where('name', 'like', $search);
        }

        $data = $query->orderBy('id')->get()->toArray();

        return array_map(function($value)
        {
            return [
                'id' => $value['id'],
                'name' => $value['name'], 
                'amount' => number_format($value['amount'], 0, ',', '.'),
                'priority' => $value['priority'],
                'billing_type' => $value['billing_type'] == 'rutin_perbulan' ? 'Rutin Per Bulan' : 'Manual',
                'disabled' => $value['disabled'] ? '<span class="badge badge-danger">Tidak</span>' : '<span class="badge badge-success">Ya</span>',
            ];

        }, $data);
    }

    public function delete($billingId)
    {
        
        try {
            $billing = Billing::findOrFail($billingId); 

            $billing->delete(); 
            return true;
        } catch(\Exception $e) {
            Log::error($e->getMessage());

            return false;
        }
    }

    public function edit(Request $request, $idRecord)
    {
        $request->validate([
            'nama' => 'required|max:100', 
            'nominal' => 'required|numeric|min:0',
            'billing_type' => 'required|in:rutin_perbulan,manual',
            'description' => 'nullable|max:500',
            'priority' => 'required|numeric|min:1',
        ]); 

        $billing = Billing::findOrFail($idRecord);
        
        // Track changes for logging
        $changes = [];

        if ($billing->name != $request->nama) {
            $changes[] = "Nama diubah dari '{$billing->name}' menjadi '{$request->nama}'";
            $billing->name = $request->nama;
        }

        if ($billing->description != $request->description) {
            $oldDesc = $billing->description ?: '(kosong)';
            $newDesc = $request->description ?: '(kosong)';
            $changes[] = "Deskripsi diubah dari '{$oldDesc}' menjadi '{$newDesc}'";
            $billing->description = $request->description;
        }

        if ($billing->amount != $request->nominal) {
            $changes[] = "Nominal diubah dari '" . number_format($billing->amount, 2) . "' menjadi '" . number_format($request->nominal, 2) . "'";
            $billing->amount = $request->nominal;
        }

        if ($billing->billing_type != $request->billing_type) {
            $oldType = $billing->billing_type == 'rutin_perbulan' ? 'Rutin Per Bulan' : 'Manual';
            $newType = $request->billing_type == 'rutin_perbulan' ? 'Rutin Per Bulan' : 'Manual';
            $changes[] = "Tipe tagihan diubah dari '{$oldType}' menjadi '{$newType}'";
            $billing->billing_type = $request->billing_type;
        }

        if ($billing->priority != $request->priority) {
            $changes[] = "Prioritas diubah dari '{$billing->priority}' menjadi '{$request->priority}'";
            $billing->priority = $request->priority;
        }

        $newDisabled = $request->has('disabled') ? false : true;
        if ($billing->disabled != $newDisabled) {
            $changes[] = $newDisabled ? "Status diubah menjadi Tidak Aktif" : "Status diubah menjadi Aktif";
            $billing->disabled = $newDisabled;
        }

        $billing->save(); 

        // Update associated users
        $userIds = $request->input('user_ids', []);
        $oldUserIds = $billing->users()->pluck('user_id')->toArray();
        $billing->users()->sync($userIds);

        if ($billing->billing_type == 'rutin_perbulan') {
            $addedUsers = array_diff($userIds, $oldUserIds);
            $removedUsers = array_diff($oldUserIds, $userIds);

            if (!empty($addedUsers) || !empty($removedUsers)) {
                BuatTagihanRutinJob::dispatch(now()->format('Y-m-d'), $billing->id, $removedUsers);
            }
        }

        // Create log if there are changes
        if (!empty($changes)) {
            $description = "Iuran diupdate:\n" . implode("\n", $changes);
            $billing->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => $description,
                'url_link' => '/admin/master/tagihan',
            ]);
        }
    }

}