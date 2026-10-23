<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Letter;
use App\Services\GoogleCloudStorage;

class LetterController extends Controller 
{
    public function index(Request $request)
    {
        $organization = auth()->user()->organization;
        
        $query = Letter::where('organization_id', $organization->id)
            ->orderBy('tanggal_surat', 'desc');
        
        // Search functionality
        if (trim($request->get('q')) != "") {
            $search = '%'. trim($request->get('q')) . '%'; 
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('nomor_surat', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $letters = $query->paginate(50);

        return view('admin.letter.index', compact('letters'));
    }

    public function create()
    {
        return view('admin.letter.create');
    }

    public function store(Request $request)
    {
        $organization = auth()->user()->organization;

        $request->validate([
            'title' => 'required|string|max:255',
            'nomor_surat' => 'nullable|string|max:255',
            'tanggal_surat' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'show_on_public' => 'nullable|boolean',
            'files.*' => 'nullable|file|mimes:pdf|max:3072', // Max 3MB each
            'external_links.*' => 'nullable|url',
        ]);

        $letter = new Letter();
        $letter->organization_id = $organization->id;
        $letter->title = $request->title;
        $letter->nomor_surat = $request->nomor_surat;
        $letter->tanggal_surat = $request->tanggal_surat;
        $letter->description = $request->description;
        $letter->status = $request->status;
        $letter->show_on_public = $request->has('show_on_public') ? true : false;

        // Handle file uploads (max 3)
        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $gcs = app(GoogleCloudStorage::class);
            $files = array_slice($request->file('files'), 0, 3); // Limit to 3 files
            
            foreach ($files as $index => $file) {
                // Generate filename
                $slug = \Illuminate\Support\Str::slug($request->title);
                $slug = substr($slug, 0, 30); // Max 30 chars
                $extension = $file->getClientOriginalExtension();
                $fileName = 'letters/' . $slug . '-' . ($index + 1) . '-' . time() . '.' . $extension;
                
                // Read file contents and upload
                $fileStream = fopen($file->getRealPath(), 'r');
                $result = $gcs->put($fileName, $fileStream);
                
                if ($result) {
                    $uploadedFiles[] = [
                        'driver' => 'gcs',
                        'path' => $fileName,
                        'original_name' => $file->getClientOriginalName(),
                    ];
                }
            }
        }
        $letter->files = !empty($uploadedFiles) ? $uploadedFiles : null;

        // Handle external links (max 3)
        $externalLinks = [];
        if ($request->has('external_links')) {
            $links = array_filter($request->external_links); // Remove empty values
            $links = array_slice($links, 0, 3); // Limit to 3 links
            foreach ($links as $link) {
                if (filter_var($link, FILTER_VALIDATE_URL)) {
                    $externalLinks[] = $link;
                }
            }
        }
        $letter->external_links = !empty($externalLinks) ? $externalLinks : null;

        $letter->save();

        // Create log
        $logDescription = "Surat '{$letter->title}' telah ditambahkan dengan nomor surat {$letter->nomor_surat} pada tanggal " . $letter->tanggal_surat->format('d/m/Y');
        $logDescription .= " (Status: {$letter->status})";
        
        $letter->logs()->create([
            'organization_id' => auth()->user()->organization_id,
            'username' => auth()->user()->name,
            'description' => $logDescription,
            'url_link' => '/admin/surat-menyurat/' . $letter->id,
        ]);

        return redirect('/admin/surat-menyurat')->with('success', 'Data surat berhasil ditambahkan');
    }

    public function show($id)
    {
        $organization = auth()->user()->organization;
        $letter = Letter::where('organization_id', $organization->id)->findOrFail($id);
        
        return view('admin.letter.show', compact('letter'));
    }

    public function edit($id)
    {
        $organization = auth()->user()->organization;
        $letter = Letter::where('organization_id', $organization->id)->findOrFail($id);
        
        return view('admin.letter.edit', compact('letter'));
    }

    public function update(Request $request, $id)
    {
        $organization = auth()->user()->organization;
        $letter = Letter::where('organization_id', $organization->id)->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'nomor_surat' => 'nullable|string|max:255',
            'tanggal_surat' => 'required|date',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'show_on_public' => 'nullable|boolean',
            'files.*' => 'nullable|file|mimes:pdf|max:3072', // Max 3MB each
            'external_links.*' => 'nullable|url',
            'delete_files.*' => 'nullable|integer',
        ]);

        // Track changes for logging
        $changes = [];

        if ($letter->title != $request->title) {
            $changes[] = "Judul surat diubah dari '{$letter->title}' menjadi '{$request->title}'";
        }

        if ($letter->nomor_surat != $request->nomor_surat) {
            $oldNomor = $letter->nomor_surat ?: '(kosong)';
            $newNomor = $request->nomor_surat ?: '(kosong)';
            $changes[] = "Nomor surat diubah dari '{$oldNomor}' menjadi '{$newNomor}'";
        }

        if ($letter->tanggal_surat->format('Y-m-d') != $request->tanggal_surat) {
            $changes[] = "Tanggal surat diubah dari " . $letter->tanggal_surat->format('d/m/Y') . " menjadi " . date('d/m/Y', strtotime($request->tanggal_surat));
        }

        if ($letter->description != $request->description) {
            $changes[] = "Deskripsi diperbarui";
        }

        if ($letter->status != $request->status) {
            $changes[] = "Status diubah dari '{$letter->status}' menjadi '{$request->status}'";
        }

        $showOnPublic = $request->has('show_on_public') ? true : false;
        if ($letter->show_on_public != $showOnPublic) {
            $changes[] = $showOnPublic ? "Diatur untuk ditampilkan di halaman publik" : "Disembunyikan dari halaman publik";
        }

        // Handle existing files
        $currentFiles = $letter->files ?: [];
        
        // Delete selected files
        if ($request->has('delete_files')) {
            foreach ($request->delete_files as $deleteIndex) {
                if (isset($currentFiles[$deleteIndex])) {
                    unset($currentFiles[$deleteIndex]);
                    $changes[] = "File dihapus: " . ($currentFiles[$deleteIndex]['original_name'] ?? 'file');
                }
            }
            $currentFiles = array_values($currentFiles); // Re-index array
        }

        // Handle new file uploads (max 3 total)
        if ($request->hasFile('files')) {
            $gcs = app(GoogleCloudStorage::class);
            $remainingSlots = 3 - count($currentFiles);
            $files = array_slice($request->file('files'), 0, $remainingSlots);
            
            foreach ($files as $index => $file) {
                // Generate filename
                $slug = \Illuminate\Support\Str::slug($request->title);
                $slug = substr($slug, 0, 30); // Max 30 chars
                $extension = $file->getClientOriginalExtension();
                $fileName = 'letters/' . $slug . '-' . (count($currentFiles) + $index + 1) . '-' . time() . '.' . $extension;
                
                // Read file contents and upload
                $fileStream = fopen($file->getRealPath(), 'r');
                $result = $gcs->put($fileName, $fileStream);
                
                if ($result) {
                    $currentFiles[] = [
                        'driver' => 'gcs',
                        'path' => $fileName,
                        'original_name' => $file->getClientOriginalName(),
                    ];
                    $changes[] = "File PDF ditambahkan: " . $file->getClientOriginalName();
                }
            }
        }
        $letter->files = !empty($currentFiles) ? $currentFiles : null;

        // Handle external links (max 3)
        $externalLinks = [];
        if ($request->has('external_links')) {
            $links = array_filter($request->external_links); // Remove empty values
            $links = array_slice($links, 0, 3); // Limit to 3 links
            foreach ($links as $link) {
                if (filter_var($link, FILTER_VALIDATE_URL)) {
                    $externalLinks[] = $link;
                }
            }
        }
        
        if (json_encode($letter->external_links) != json_encode($externalLinks)) {
            $changes[] = "Link eksternal diperbarui";
        }
        $letter->external_links = !empty($externalLinks) ? $externalLinks : null;

        $letter->title = $request->title;
        $letter->nomor_surat = $request->nomor_surat;
        $letter->tanggal_surat = $request->tanggal_surat;
        $letter->description = $request->description;
        $letter->status = $request->status;
        $letter->show_on_public = $showOnPublic;
        $letter->save();

        // Create log if there are changes
        if (!empty($changes)) {
            $description = "Data surat '{$letter->title}' diupdate:\n" . implode("\n", $changes);
            $letter->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => $description,
                'url_link' => '/admin/surat-menyurat/' . $letter->id,
            ]);
        }

        return redirect('/admin/surat-menyurat')->with('success', 'Data surat berhasil diperbarui');
    }

    public function destroy($id)
    {
        $organization = auth()->user()->organization;
        
        try {
            $letter = Letter::where('organization_id', $organization->id)->findOrFail($id);
            $title = $letter->title;
            
            // Create log before deletion
            auth()->user()->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => "Surat '{$title}' telah dihapus",
                'url_link' => '/admin/surat-menyurat/',
            ]);

            $letter->delete();

            return response()->json(['success' => true]); 
        } catch(\Exception $e) {
            return response()->json(['success' => false]); 
        }
    }

    public function showFile($id, $fileIndex)
    {
        $organization = auth()->user()->organization;
        
        // Find letter in user's organization
        $letter = Letter::where('organization_id', $organization->id)->findOrFail($id);
        
        // Check if letter has files
        if (!$letter->files || !isset($letter->files[$fileIndex])) {
            return response('File not found', 404);
        }

        $fileData = $letter->files[$fileIndex];

        try {
            if ($fileData['driver'] === 'gcs') {
                // Serve from Google Cloud Storage
                $gcs = app(GoogleCloudStorage::class);
                return $gcs->response($fileData['path']);
            } elseif ($fileData['driver'] === 'local') {
                // Serve from local storage
                if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($fileData['path'])) {
                    return response('File not found', 404);
                }
                
                $file = \Illuminate\Support\Facades\Storage::disk('local')->get($fileData['path']);
                $mimeType = \Illuminate\Support\Facades\Storage::disk('local')->mimeType($fileData['path']);
                
                return response($file, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', 'inline; filename="' . basename($fileData['path']) . '"');
            }
            
            return response('Invalid driver', 400);
            
        } catch (\Exception $e) {
            \Log::error('Letter File Serve Error: ' . $e->getMessage());
            return response('Error serving file', 500);
        }
    }
}
