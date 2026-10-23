<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Services\GoogleCloudStorage;

class EventController extends Controller 
{
    public function index(Request $request)
    {
        $organization = auth()->user()->organization;
        
        $query = Event::where('organization_id', $organization->id)
            ->orderBy('event_date', 'desc');
        
        // Search functionality
        if (trim($request->get('q')) != "") {
            $search = '%'. trim($request->get('q')) . '%'; 
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('location', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $events = $query->paginate(50);

        return view('admin.event.index', compact('events'));
    }

    public function create()
    {
        return view('admin.event.create');
    }

    public function store(Request $request)
    {
        $organization = auth()->user()->organization;

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'event_time' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'persistent_event' => 'nullable|boolean',
            'files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif|max:3072', // Max 3MB each
        ]);

        $event = new Event();
        $event->organization_id = $organization->id;
        $event->title = $request->title;
        $event->description = $request->description;
        $event->event_date = $request->event_date;
        $event->event_time = $request->event_time;
        $event->location = $request->location;
        $event->persistent_event = $request->has('persistent_event') ? true : false;

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
                $fileName = 'events/' . $slug . '-' . ($index + 1) . '-' . time() . '.' . $extension;

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
        $event->files = !empty($uploadedFiles) ? $uploadedFiles : null;

        $event->save();

        // Create log
        $logDescription = "Kegiatan baru '{$event->title}' telah ditambahkan pada tanggal " . $event->event_date->format('d/m/Y');
        if ($event->persistent_event) {
            $logDescription .= " (ditampilkan sebagai kegiatan tetap)";
        }
        
        $event->logs()->create([
            'organization_id' => auth()->user()->organization_id,
            'username' => auth()->user()->name,
            'description' => $logDescription,
            'url_link' => '/admin/event/' . $event->id,
        ]);

        return redirect('/admin/event')->with('success', 'Data kegiatan berhasil ditambahkan');
    }

    public function show($id)
    {
        $organization = auth()->user()->organization;
        $event = Event::where('organization_id', $organization->id)->findOrFail($id);
        
        return view('admin.event.show', compact('event'));
    }

    public function edit($id)
    {
        $organization = auth()->user()->organization;
        $event = Event::where('organization_id', $organization->id)->findOrFail($id);
        
        return view('admin.event.edit', compact('event'));
    }

    public function update(Request $request, $id)
    {
        $organization = auth()->user()->organization;
        $event = Event::where('organization_id', $organization->id)->findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'event_time' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'persistent_event' => 'nullable|boolean',
            'files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif|max:3072', // Max 3MB each
            'delete_files.*' => 'nullable|integer',
        ]);

        // Track changes for logging
        $changes = [];

        if ($event->title != $request->title) {
            $changes[] = "Judul kegiatan diubah dari '{$event->title}' menjadi '{$request->title}'";
        }

        if ($event->event_date->format('Y-m-d') != $request->event_date) {
            $changes[] = "Tanggal diubah dari " . $event->event_date->format('d/m/Y') . " menjadi " . date('d/m/Y', strtotime($request->event_date));
        }

        if ($event->event_time != $request->event_time) {
            $oldTime = $event->event_time ?: '(kosong)';
            $newTime = $request->event_time ?: '(kosong)';
            $changes[] = "Waktu diubah dari '{$oldTime}' menjadi '{$newTime}'";
        }

        if ($event->location != $request->location) {
            $oldLocation = $event->location ?: '(kosong)';
            $newLocation = $request->location ?: '(kosong)';
            $changes[] = "Lokasi diubah dari '{$oldLocation}' menjadi '{$newLocation}'";
        }

        if ($event->description != $request->description) {
            $changes[] = "Deskripsi diperbarui";
        }

        $persistentEvent = $request->has('persistent_event') ? true : false;
        if ($event->persistent_event != $persistentEvent) {
            $changes[] = $persistentEvent ? "Diatur sebagai kegiatan tetap" : "Dihapus dari kegiatan tetap";
        }

        // Handle existing files
        $currentFiles = $event->files ?: [];

        // Delete selected files
        if ($request->has('delete_files')) {
            foreach ($request->delete_files as $deleteIndex) {
                if (isset($currentFiles[$deleteIndex])) {
                    $changes[] = "File dihapus: " . ($currentFiles[$deleteIndex]['original_name'] ?? 'file');
                    unset($currentFiles[$deleteIndex]);
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
                $fileName = 'events/' . $slug . '-' . (count($currentFiles) + $index + 1) . '-' . time() . '.' . $extension;

                // Read file contents and upload
                $fileStream = fopen($file->getRealPath(), 'r');
                $result = $gcs->put($fileName, $fileStream);

                if ($result) {
                    $currentFiles[] = [
                        'driver' => 'gcs',
                        'path' => $fileName,
                        'original_name' => $file->getClientOriginalName(),
                    ];
                    $changes[] = "File ditambahkan: " . $file->getClientOriginalName();
                }
            }
        }
        $event->files = !empty($currentFiles) ? $currentFiles : null;

        $event->title = $request->title;
        $event->description = $request->description;
        $event->event_date = $request->event_date;
        $event->event_time = $request->event_time;
        $event->location = $request->location;
        $event->persistent_event = $persistentEvent;
        $event->save();

        // Create log if there are changes
        if (!empty($changes)) {
            $description = "Data kegiatan '{$event->title}' diupdate:\n" . implode("\n", $changes);
            $event->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => $description,
                'url_link' => '/admin/event/' . $event->id,
            ]);
        }

        return redirect('/admin/event')->with('success', 'Data kegiatan berhasil diperbarui');
    }

    public function destroy($id)
    {
        $organization = auth()->user()->organization;
        
        try {
            $event = Event::where('organization_id', $organization->id)->findOrFail($id);
            $title = $event->title;
            
            // Create log before deletion
            auth()->user()->logs()->create([
                'organization_id' => auth()->user()->organization_id,
                'username' => auth()->user()->name,
                'description' => "Kegiatan '{$title}' telah dihapus",
                'url_link' => '/admin/event/',
            ]);

            $event->delete();

            return response()->json(['success' => true]);
        } catch(\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    public function showFile($id, $fileIndex)
    {
        $organization = auth()->user()->organization;

        $event = Event::where('organization_id', $organization->id)->findOrFail($id);

        if (!$event->files || !isset($event->files[$fileIndex])) {
            return response('File not found', 404);
        }

        $fileData = $event->files[$fileIndex];

        try {
            if ($fileData['driver'] === 'gcs') {
                $gcs = app(GoogleCloudStorage::class);
                return $gcs->response($fileData['path']);
            } elseif ($fileData['driver'] === 'local') {
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
            \Log::error('Event File Serve Error: ' . $e->getMessage());
            return response('Error serving file', 500);
        }
    }
}
