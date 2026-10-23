@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Kegiatan RT</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/event') }}">Daftar Kegiatan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah Kegiatan</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>Tambah Kegiatan Baru</h5>
                    <form action="{{ url('/admin/event/create') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="title">Judul Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" placeholder="Masukkan judul kegiatan" 
                                   value="{{ old('title') }}" required>
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
                                   value="{{ old('event_date') }}" required>
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
                                   value="{{ old('event_time') }}">
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
                                   value="{{ old('location') }}">
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
                                      placeholder="Masukkan deskripsi kegiatan">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="persistent_event" 
                                       name="persistent_event" value="1" {{ old('persistent_event') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="persistent_event">
                                    Tampilkan sebagai kegiatan tetap
                                    <small class="text-muted d-block">Kegiatan akan selalu ditampilkan di halaman pengguna</small>
                                </label>
                            </div>
                        </div>
                        
                        <hr>

                        <h6 class="mb-3">File Lampiran (PDF / Gambar)</h6>
                        <div class="form-group">
                            <label for="files">Upload File (Maksimal 3 file)</label>
                            <div id="file-upload-container">
                                <div class="file-upload-item mb-2">
                                    <input type="file" class="form-control-file" name="files[]" accept=".pdf,.jpg,.jpeg,.png,.gif">
                                </div>
                                <div class="file-upload-item mb-2">
                                    <input type="file" class="form-control-file" name="files[]" accept=".pdf,.jpg,.jpeg,.png,.gif">
                                </div>
                                <div class="file-upload-item mb-2">
                                    <input type="file" class="form-control-file" name="files[]" accept=".pdf,.jpg,.jpeg,.png,.gif">
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Format: PDF, JPG, JPEG, PNG, GIF. Maksimal 3MB per file. Maksimal 3 file.
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
                                <i class="fa fa-save"></i> Simpan
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
@endsection
