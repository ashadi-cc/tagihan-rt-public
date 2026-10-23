<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Services\GoogleCloudStorage;

class InventoryController extends Controller 
{
    public function index(Request $request)
    {
        $organization = auth()->user()->organization;
        
        $query = Inventory::where('organization_id', $organization->id)
            ->orderBy('item_name');
        
        // Search functionality
        if (trim($request->get('q')) != "") {
            $search = '%'. trim($request->get('q')) . '%'; 
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', $search)
                  ->orWhere('stored_location', 'like', $search);
            });
        }

        $inventories = $query->paginate(50);

        return view('admin.inventory.index', compact('inventories'));
    }

    public function create()
    {
        return view('admin.inventory.create');
    }

    public function store(Request $request)
    {
        $organization = auth()->user()->organization;

        $request->validate([
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'condition' => 'nullable|string|max:255',
            'stored_location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB each
        ]);

        $inventory = new Inventory();
        $inventory->organization_id = $organization->id;
        $inventory->item_name = $request->item_name;
        $inventory->quantity = $request->quantity;
        $inventory->condition = $request->condition;
        $inventory->stored_location = $request->stored_location;
        $inventory->description = $request->description;

        // Handle photo uploads (max 3)
        $uploadedPhotos = [];
        if ($request->hasFile('photos')) {
            $gcs = app(GoogleCloudStorage::class);
            $photos = array_slice($request->file('photos'), 0, 3); // Limit to 3 photos

            $slug = \Illuminate\Support\Str::slug($request->item_name);
            $slug = substr($slug, 0, 30); // Max 30 chars

            foreach ($photos as $index => $file) {
                $extension = $file->getClientOriginalExtension();
                $fileName = 'inventories/' . $slug . '-' . ($index + 1) . '-' . time() . '.' . $extension;

                // Read file contents and upload
                $fileStream = fopen($file->getRealPath(), 'r');
                $result = $gcs->put($fileName, $fileStream);

                if ($result) {
                    $uploadedPhotos[] = [
                        'driver' => 'gcs',
                        'path' => $fileName,
                        'original_name' => $file->getClientOriginalName(),
                    ];
                }
            }
        }
        $inventory->photos = !empty($uploadedPhotos) ? $uploadedPhotos : null;

        $inventory->save();

        // Create log
        $inventory->logs()->create([
            'organization_id' => auth()->user()->organization_id,
            'username' => auth()->user()->name,
            'description' => "Barang baru '{$inventory->item_name}' telah ditambahkan dengan jumlah {$inventory->quantity}",
            'url_link' => '/admin/inventory/' . $inventory->id,
        ]);

        return redirect('/admin/inventory')->with('success', 'Data inventaris berhasil ditambahkan');
    }

    public function show($id)
    {
        $organization = auth()->user()->organization;
        $inventory = Inventory::where('organization_id', $organization->id)->findOrFail($id);
        
        return view('admin.inventory.show', compact('inventory'));
    }

    public function edit($id)
    {
        $organization = auth()->user()->organization;
        $inventory = Inventory::where('organization_id', $organization->id)->findOrFail($id);
        
        return view('admin.inventory.edit', compact('inventory'));
    }

    public function update(Request $request, $id)
    {
        $organization = auth()->user()->organization;
        $inventory = Inventory::where('organization_id', $organization->id)->findOrFail($id);

        $request->validate([
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'condition' => 'nullable|string|max:255',
            'stored_location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB each
            'delete_photos.*' => 'nullable|integer',
        ]);

        // Track changes for logging
        $changes = [];

        if ($inventory->item_name != $request->item_name) {
            $changes[] = "Nama barang diubah dari '{$inventory->item_name}' menjadi '{$request->item_name}'";
        }

        if ($inventory->quantity != $request->quantity) {
            $changes[] = "Jumlah diubah dari {$inventory->quantity} menjadi {$request->quantity}";
        }

        if ($inventory->condition != $request->condition) {
            $oldCondition = $inventory->condition ?: '(kosong)';
            $newCondition = $request->condition ?: '(kosong)';
            $changes[] = "Kondisi diubah dari '{$oldCondition}' menjadi '{$newCondition}'";
        }

        if ($inventory->stored_location != $request->stored_location) {
            $oldLocation = $inventory->stored_location ?: '(kosong)';
            $newLocation = $request->stored_location ?: '(kosong)';
            $changes[] = "Lokasi penyimpanan diubah dari '{$oldLocation}' menjadi '{$newLocation}'";
        }

        if ($inventory->description != $request->description) {
            $changes[] = "Deskripsi diperbarui";
        }

        // Handle existing photos
        $currentPhotos = $inventory->photos ?: [];

        // Delete selected photos
        if ($request->has('delete_photos')) {
            foreach ($request->delete_photos as $deleteIndex) {
                if (isset($currentPhotos[$deleteIndex])) {
                    unset($currentPhotos[$deleteIndex]);
                    $changes[] = "Foto barang dihapus";
                }
            }
            $currentPhotos = array_values($currentPhotos); // Re-index array
        }

        // Handle new photo uploads (max 3 total)
        if ($request->hasFile('photos')) {
            $gcs = app(GoogleCloudStorage::class);
            $remainingSlots = 3 - count($currentPhotos);
            $photos = array_slice($request->file('photos'), 0, $remainingSlots);

            $slug = \Illuminate\Support\Str::slug($request->item_name);
            $slug = substr($slug, 0, 30); // Max 30 chars

            foreach ($photos as $index => $file) {
                $extension = $file->getClientOriginalExtension();
                $fileName = 'inventories/' . $slug . '-' . (count($currentPhotos) + $index + 1) . '-' . time() . '.' . $extension;

                // Read file contents and upload
                $fileStream = fopen($file->getRealPath(), 'r');
                $result = $gcs->put($fileName, $fileStream);

                if ($result) {
                    $currentPhotos[] = [
                        'driver' => 'gcs',
                        'path' => $fileName,
                        'original_name' => $file->getClientOriginalName(),
                    ];
                    $changes[] = "Foto barang ditambahkan";
                }
            }
        }
        $inventory->photos = !empty($currentPhotos) ? $currentPhotos : null;

        $inventory->item_name = $request->item_name;
        $inventory->quantity = $request->quantity;
        $inventory->condition = $request->condition;
        $inventory->stored_location = $request->stored_location;
        $inventory->description = $request->description;
        $inventory->save();

        // Create log if there are changes
        if (!empty($changes)) {
            $description = "Data inventaris '{$inventory->item_name}' diupdate:\n" . implode("\n", $changes);
            $inventory->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => $description,
                'url_link' => '/admin/inventory/' . $inventory->id,
            ]);
        }

        return redirect('/admin/inventory')->with('success', 'Data inventaris berhasil diperbarui');
    }

    public function destroy($id)
    {
        $organization = auth()->user()->organization;
        
        try {
            $inventory = Inventory::where('organization_id', $organization->id)->findOrFail($id);
            $itemName = $inventory->item_name;
            
            // Create log before deletion
            auth()->user()->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => "Inventaris '{$itemName}' telah dihapus",
                'url_link' => '/admin/inventory/',
            ]);

            $inventory->delete();

            return response()->json(['success' => true]); 
        } catch(\Exception $e) {
            return response()->json(['success' => false]); 
        }
    }
}
