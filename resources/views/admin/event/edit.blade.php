@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Kegiatan RT</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/event') }}">Daftar Kegiatan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Kegiatan</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>Edit Kegiatan</h5>
                    <form action="{{ url('/admin/event/' . $event->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="title">Judul Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" placeholder="Masukkan judul kegiatan" 
                                   value="{{ old('title', $event->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="event_date">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('event_date') is-invalid @enderror" 
                                   id="event_date" name="event_date" 
                                   value="{{ old('event_date', $event->event_date->format('Y-m-d')) }}" required>
                            @error('event_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="event_time">Waktu</label>
                            <input type="text" class="form-control @error('event_time') is-invalid @enderror" 
                                   id="event_time" name="event_time" 
                                   placeholder="Contoh: 08:00 - 12:00 WIB" 
                                   value="{{ old('event_time', $event->event_time) }}">
                            @error('event_time')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Lokasi</label>
                            <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                   id="location" name="location" 
                                   placeholder="Masukkan lokasi kegiatan" 
                                   value="{{ old('location', $event->location) }}">
                            @error('location')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="5" 
                                      placeholder="Masukkan deskripsi kegiatan">{{ old('description', $event->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="persistent_event" 
                                       name="persistent_event" value="1" 
                                       {{ old('persistent_event', $event->persistent_event) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="persistent_event">
                                    Tampilkan sebagai kegiatan tetap
                                    <small class="text-muted d-block">Kegiatan akan selalu ditampilkan di halaman pengguna</small>
                                </label>
                            </div>
                        </div>
                        
                        <hr>

                        <h6 class="mb-3">File Lampiran (PDF / Gambar)</h6>

                        @if($event->files && count($event->files) > 0)
                        <div class="mb-3">
                            <label>File yang sudah ada:</label>
                            @foreach($event->files as $index => $file)
                            @php
                                $ext = strtolower(pathinfo($file['original_name'] ?? $file['path'] ?? '', PATHINFO_EXTENSION));
                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                                $icon = $isImage ? 'fa-picture-o' : 'fa-file-pdf-o';
                                $label = $file['original_name'] ?? (($isImage ? 'Foto' : 'Berkas') . ' ' . ($index + 1));
                            @endphp
                            <div class="d-flex align-items-center mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input"
                                           id="delete_file_{{ $index }}" name="delete_files[]" value="{{ $index }}">
                                    <label class="custom-control-label" for="delete_file_{{ $index }}">
                                        Hapus file ini
                                    </label>
                                </div>
                                <span class="badge badge-success ml-2" title="{{ $file['original_name'] ?? '' }}">
                                    <i class="fa {{ $icon }}"></i> {{ \Illuminate\Support\Str::limit($label, 25) }}
                                </span>
                                <button type="button" class="btn btn-sm btn-info ml-2" data-toggle="modal" data-target="#pdfModal{{ $index }}">
                                    <i class="fa fa-eye"></i> Lihat
                                </button>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <div class="form-group">
                            <label for="files">Upload File Baru (Total maksimal 3 file)</label>
                            <div id="file-upload-container">
                                @php
                                    $currentFileCount = $event->files ? count($event->files) : 0;
                                    $remainingSlots = 3 - $currentFileCount;
                                @endphp
                                @for($i = 0; $i < $remainingSlots; $i++)
                                <div class="file-upload-item mb-2">
                                    <input type="file" class="form-control-file" name="files[]" accept=".pdf,.jpg,.jpeg,.png,.gif">
                                </div>
                                @endfor
                            </div>
                            <small class="form-text text-muted">
                                Format: PDF, JPG, JPEG, PNG, GIF. Maksimal 3MB per file. Saat ini: {{ $currentFileCount }} file. Slot tersisa: {{ $remainingSlots }}.
                            </small>
                            @error('files.*')
                                <div class="text-danger small">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <hr>
                        <div class="form-group d-flex flex-wrap" style="gap: .5rem;">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="{{ url('/admin/event') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($event->files && count($event->files) > 0)
    @foreach($event->files as $index => $file)
    <!-- File Modal -->
    <div class="modal fade" id="pdfModal{{ $index }}" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel{{ $index }}" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel{{ $index }}">{{ $file['original_name'] ?? 'File' }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="loading-spinner-{{ $index }}" class="text-center mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat file...</p>
                    </div>
                    <iframe
                        id="pdf-viewer-{{ $index }}"
                        src=""
                        data-src="{{ url('/admin/event/' . $event->id . '/file/' . $index) }}"
                        style="width: 100%; height: 70vh; border: none; display: none;">
                    </iframe>
                </div>
                <div class="modal-footer">
                    <a href="{{ url('/admin/event/' . $event->id . '/file/' . $index) }}" class="btn btn-primary" target="_blank">
                        <i class="fa fa-download"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @endif
@endsection

@section('scripts')
    @if($event->files && count($event->files) > 0)
    <script>
        $(document).ready(function() {
            @foreach($event->files as $index => $file)
            $('#pdfModal{{ $index }}').on('show.bs.modal', function (e) {
                $('#loading-spinner-{{ $index }}').show();
                $('#pdf-viewer-{{ $index }}').hide();

                var iframe = document.getElementById('pdf-viewer-{{ $index }}');
                iframe.src = iframe.getAttribute('data-src');

                $('#pdf-viewer-{{ $index }}').on('load', function() {
                    $('#loading-spinner-{{ $index }}').hide();
                    $('#pdf-viewer-{{ $index }}').show();
                });
            });
            @endforeach
        });
    </script>
    @endif
@endsection
