@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Arsip Surat</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/surat-menyurat') }}">Daftar Surat</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Surat</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-10">
                    <h5>Edit Surat</h5>
                    <form action="{{ url('/admin/surat-menyurat/' . $letter->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="title">Judul Surat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" placeholder="Masukkan judul surat" 
                                   value="{{ old('title', $letter->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nomor_surat">Nomor Surat</label>
                                    <input type="text" class="form-control @error('nomor_surat') is-invalid @enderror" 
                                           id="nomor_surat" name="nomor_surat" 
                                           placeholder="Contoh: 001/RT-01/II/2026" 
                                           value="{{ old('nomor_surat', $letter->nomor_surat) }}">
                                    @error('nomor_surat')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_surat">Tanggal Surat <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('tanggal_surat') is-invalid @enderror" 
                                           id="tanggal_surat" name="tanggal_surat" 
                                           value="{{ old('tanggal_surat', $letter->tanggal_surat->format('Y-m-d')) }}" required>
                                    @error('tanggal_surat')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="5" 
                                      placeholder="Masukkan deskripsi atau perihal surat">{{ old('description', $letter->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status <span class="text-danger">*</span></label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            id="status" name="status" required>
                                        <option value="draft" {{ old('status', $letter->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="published" {{ old('status', $letter->status) == 'published' ? 'selected' : '' }}>Published</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Hanya surat dengan status "Published" yang bisa dilihat oleh warga
                                    </small>
                                    @error('status')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="show_on_public" 
                                               name="show_on_public" value="1" {{ old('show_on_public', $letter->show_on_public) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="show_on_public">
                                            Tampilkan di halaman publik
                                            <small class="text-muted d-block">Surat akan terlihat di menu "Arsip Surat" warga</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">File Lampiran (PDF)</h6>
                        
                        @if($letter->files && count($letter->files) > 0)
                        <div class="mb-3">
                            <label>File yang sudah ada:</label>
                            @foreach($letter->files as $index => $file)
                            <div class="d-flex align-items-center mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="delete_file_{{ $index }}" name="delete_files[]" value="{{ $index }}">
                                    <label class="custom-control-label" for="delete_file_{{ $index }}">
                                        Hapus file ini
                                    </label>
                                </div>
                                <span class="badge badge-success ml-2">
                                    <i class="fa fa-file-pdf-o"></i> {{ $file['original_name'] ?? 'File ' . ($index + 1) }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <div class="form-group">
                            <label for="files">Upload File PDF Baru (Total maksimal 3 file)</label>
                            <div id="file-upload-container">
                                @php
                                    $currentFileCount = $letter->files ? count($letter->files) : 0;
                                    $remainingSlots = 3 - $currentFileCount;
                                @endphp
                                @for($i = 0; $i < $remainingSlots; $i++)
                                <div class="file-upload-item mb-2">
                                    <input type="file" class="form-control-file" name="files[]" accept=".pdf">
                                </div>
                                @endfor
                            </div>
                            <small class="form-text text-muted">
                                Format: PDF. Maksimal 3MB per file. Saat ini: {{ $currentFileCount }} file. Slot tersisa: {{ $remainingSlots }}.
                            </small>
                            @error('files.*')
                                <div class="text-danger small">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <hr>

                        <h6 class="mb-3">Tautan Eksternal</h6>
                        <div class="form-group">
                            <label>Link ke Google Drive, Dropbox, dll (Maksimal 3 link)</label>
                            <div id="link-container">
                                @php
                                    $currentLinks = $letter->external_links ?: [];
                                @endphp
                                @for($i = 0; $i < 3; $i++)
                                <div class="link-item mb-2">
                                    <input type="url" class="form-control" name="external_links[]" 
                                           placeholder="https://drive.google.com/..." 
                                           value="{{ old('external_links.' . $i, $currentLinks[$i] ?? '') }}">
                                </div>
                                @endfor
                            </div>
                            <small class="form-text text-muted">
                                Masukkan URL lengkap (contoh: https://drive.google.com/file/...)
                            </small>
                            @error('external_links.*')
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
                            <a href="{{ url('/admin/surat-menyurat') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
