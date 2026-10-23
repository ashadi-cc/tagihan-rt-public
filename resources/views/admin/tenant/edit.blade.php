@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Warga Pengontrak</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/tenant') }}">Daftar Warga Pengontrak</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Warga Pengontrak</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>Edit Data Warga Pengontrak</h5>
                    <form action="{{ url('/admin/tenant/' . $tenant->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="name">Nama Warga Pengontrak <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" placeholder="Masukkan nama warga pengontrak" 
                                   value="{{ old('name', $tenant->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="unit_number">Blok Rumah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('unit_number') is-invalid @enderror" 
                                   id="unit_number" name="unit_number" 
                                   placeholder="Contoh: A-1" 
                                   value="{{ old('unit_number', $tenant->unit_number) }}" required>
                            @error('unit_number')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_info">No HP Pengontrak</label>
                            <input type="text" class="form-control @error('contact_info') is-invalid @enderror" 
                                   id="contact_info" name="contact_info" 
                                   placeholder="Masukkan nomor HP" 
                                   value="{{ old('contact_info', $tenant->contact_info) }}">
                            @error('contact_info')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Alamat Asal Pengontrak <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" name="address" rows="3" 
                                      placeholder="Masukkan alamat asal" required>{{ old('address', $tenant->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Keterangan</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Masukkan keterangan tambahan">{{ old('description', $tenant->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="photo">Foto Identitas</label>
                            @if($tenant->file_path)
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fa fa-check-circle text-success"></i> Foto sudah diupload
                                    </small>
                                </div>
                            @endif
                            <input type="file" class="form-control-file @error('photo') is-invalid @enderror" 
                                   id="photo" name="photo" accept="image/*">
                            <small class="form-text text-muted">
                                <i class="fa fa-info-circle"></i> Format yang didukung: JPG, PNG, GIF. Maksimal 5MB. Kosongkan jika tidak ingin mengganti foto.
                            </small>
                            @error('photo')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <hr>
                        <div class="form-group d-flex flex-wrap" style="gap: .5rem;">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="{{ url('/admin/tenant') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
