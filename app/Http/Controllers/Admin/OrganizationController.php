<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function show()
    {
        $organization = auth()->user()->organization;

        return view('admin.organization.show', compact('organization'));
    }

    public function edit()
    {
        $organization = auth()->user()->organization;

        return view('admin.organization.edit', compact('organization'));
    }

    public function update(Request $request)
    {
        $organization = auth()->user()->organization;
        $isSuperadmin = auth()->user()->hasRole('superadmin');

        $rules = [
            'name' => 'required|string|max:255|unique:organizations,name,' . $organization->id,
            'description' => 'nullable|string|max:255',
        ];

        if ($isSuperadmin) {
            $rules['whatsapp_admin_group_id'] = 'nullable|string|max:255';
            $rules['whatsapp_member_group_id'] = 'nullable|string|max:255';
            $rules['whatsapp_notif_startup_number'] = 'nullable|string|max:255';
            $rules['whatsapp_enable_registration'] = 'nullable|boolean';
        }

        $request->validate($rules);

        $changes = [];

        if ($organization->name != $request->name) {
            $changes[] = "Nama organisasi diubah dari '{$organization->name}' menjadi '{$request->name}'";
        }

        if ($organization->description != $request->description) {
            $changes[] = "Deskripsi organisasi diperbarui";
        }

        $organization->name = $request->name;
        $organization->description = $request->description;

        if ($isSuperadmin) {
            if ($organization->whatsapp_admin_group_id != $request->whatsapp_admin_group_id
                || $organization->whatsapp_member_group_id != $request->whatsapp_member_group_id
                || $organization->whatsapp_notif_startup_number != $request->whatsapp_notif_startup_number
                || $organization->whatsapp_enable_registration != $request->boolean('whatsapp_enable_registration')) {
                $changes[] = "Konfigurasi WhatsApp organisasi diperbarui";
            }

            $organization->whatsapp_admin_group_id = $request->whatsapp_admin_group_id;
            $organization->whatsapp_member_group_id = $request->whatsapp_member_group_id;
            $organization->whatsapp_notif_startup_number = $request->whatsapp_notif_startup_number;
            $organization->whatsapp_enable_registration = $request->boolean('whatsapp_enable_registration');
        }

        $organization->save();

        if (!empty($changes)) {
            auth()->user()->logs()->create([
                'organization_id' => $organization->id,
                'username' => auth()->user()->name,
                'description' => implode("\n", $changes),
                'url_link' => '/admin/organization',
            ]);
        }

        return redirect('/admin/organization')->with('success', 'Data organisasi berhasil diperbarui');
    }
}
