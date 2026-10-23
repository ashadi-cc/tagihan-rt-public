<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\GoogleCloudStorage;

class TenantController extends Controller 
{
    public function index(Request $request)
    {
        $organization = auth()->user()->organization;
        
        $query = Tenant::where('organization_id', $organization->id)
            ->orderBy('unit_number')
            ->orderBy('name');
        
        // Search functionality
        if (trim($request->get('q')) != "") {
            $search = '%'. trim($request->get('q')) . '%'; 
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('unit_number', 'like', $search)
                  ->orWhere('contact_info', 'like', $search);
            });
        }

        $tenants = $query->paginate(50);

        return view('admin.tenant.index', compact('tenants'));
    }

    public function create()
    {
        return view('admin.tenant.create');
    }

    public function store(Request $request)
    {
        $organization = auth()->user()->organization;

        $request->validate([
            'name' => 'required|string|max:255',
            'contact_info' => 'nullable|string|max:255',
            'address' => 'required|string',
            'unit_number' => 'required|string|max:255',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
        ]);

        $tenant = new Tenant();
        $tenant->organization_id = $organization->id;
        $tenant->name = $request->name;
        $tenant->contact_info = $request->contact_info;
        $tenant->address = $request->address;
        $tenant->unit_number = $request->unit_number;
        $tenant->description = $request->description;

        // Handle file upload
        if ($request->hasFile('photo')) {
            $gcs = app(GoogleCloudStorage::class);
            $file = $request->file('photo');
            
            // Generate filename from block + name
            $baseName = ($request->unit_number ? $request->unit_number . '-' : '') . $request->name;
            $slug = \Illuminate\Support\Str::slug($baseName);
            $slug = substr($slug, 0, 30); // Max 30 chars
            $extension = $file->getClientOriginalExtension();
            $fileName = 'tenants/' . $slug . '.' . $extension;
            
            // Read file content and upload
            $fileStream = fopen($file->getRealPath(), 'r');
            $result = $gcs->put($fileName, $fileStream);
            
            if ($result) {
                $tenant->file_driver = 'gcs';
                $tenant->file_path = $fileName;
            }
        }

        $tenant->save();

        // Create log
        $tenant->logs()->create([
            'organization_id' => auth()->user()->organization_id,
            'username' => auth()->user()->name,
            'description' => "Warga pengontrak baru '{$tenant->name}' telah ditambahkan" . ($tenant->unit_number ? " di blok {$tenant->unit_number}" : ""),
            'url_link' => '/admin/tenant/' . $tenant->id,
        ]);

        return redirect('/admin/tenant')->with('success', 'Data warga pengontrak berhasil ditambahkan');
    }

    public function show($id)
    {
        $organization = auth()->user()->organization;
        $tenant = Tenant::where('organization_id', $organization->id)->findOrFail($id);
        
        return view('admin.tenant.show', compact('tenant'));
    }

    public function edit($id)
    {
        $organization = auth()->user()->organization;
        $tenant = Tenant::where('organization_id', $organization->id)->findOrFail($id);
        
        return view('admin.tenant.edit', compact('tenant'));
    }

    public function update(Request $request, $id)
    {
        $organization = auth()->user()->organization;
        $tenant = Tenant::where('organization_id', $organization->id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'contact_info' => 'nullable|string|max:255',
            'address' => 'required|string',
            'unit_number' => 'required|string|max:255',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
        ]);

        // Track changes for logging
        $changes = [];

        if ($tenant->name != $request->name) {
            $changes[] = "Nama diubah dari '{$tenant->name}' menjadi '{$request->name}'";
        }

        if ($tenant->contact_info != $request->contact_info) {
            $oldContact = $tenant->contact_info ?: '(kosong)';
            $newContact = $request->contact_info ?: '(kosong)';
            $changes[] = "No HP diubah dari '{$oldContact}' menjadi '{$newContact}'";
        }

        if ($tenant->unit_number != $request->unit_number) {
            $oldUnit = $tenant->unit_number ?: '(kosong)';
            $newUnit = $request->unit_number ?: '(kosong)';
            $changes[] = "Blok rumah diubah dari '{$oldUnit}' menjadi '{$newUnit}'";
        }

        if ($tenant->address != $request->address) {
            $changes[] = "Alamat asal diperbarui";
        }

        if ($tenant->description != $request->description) {
            $changes[] = "Keterangan diperbarui";
        }

        // Handle file upload
        if ($request->hasFile('photo')) {
            $gcs = app(GoogleCloudStorage::class);
            
            // Delete old file if exists
            // if ($tenant->file_path && $tenant->file_driver === 'gcs') {
            //     $gcs->delete($tenant->file_path);
            // }
            
            $file = $request->file('photo');
            
            // Generate filename from block + name
            $baseName = ($request->unit_number ? $request->unit_number . '-' : '') . $request->name;
            $slug = \Illuminate\Support\Str::slug($baseName);
            $slug = substr($slug, 0, 30); // Max 30 chars
            $extension = $file->getClientOriginalExtension();
            $fileName = 'tenants/' . $slug . '.' . $extension;
            
            // Read file content and upload
            $fileStream = fopen($file->getRealPath(), 'r');
            $result = $gcs->put($fileName, $fileStream);
            
            if ($result) {
                $tenant->file_driver = 'gcs';
                $tenant->file_path = $fileName;
                $changes[] = "Foto identitas diperbarui";
            }
        }

        $tenant->name = $request->name;
        $tenant->contact_info = $request->contact_info;
        $tenant->address = $request->address;
        $tenant->unit_number = $request->unit_number;
        $tenant->description = $request->description;
        $tenant->save();

        // Create log if there are changes
        if (!empty($changes)) {
            $description = "Data warga pengontrak '{$tenant->name}' diupdate:\n" . implode("\n", $changes);
            $tenant->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => $description,
                'url_link' => '/admin/tenant/' . $tenant->id,
            ]);
        }

        return redirect('/admin/tenant')->with('success', 'Data warga pengontrak berhasil diperbarui');
    }

    public function destroy($id)
    {
        $organization = auth()->user()->organization;
        
        try {
            $tenant = Tenant::where('organization_id', $organization->id)->findOrFail($id);
            $name = $tenant->name;
            
            // Create log before deletion
            auth()->user()->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => "Warga pengontrak '{$name}' telah dihapus",
                'url_link' => '/admin/tenant/',
            ]);

            $tenant->delete();

            return response()->json(['success' => true]); 
        } catch(\Exception $e) {
            return response()->json(['success' => false]); 
        }
    }
}
