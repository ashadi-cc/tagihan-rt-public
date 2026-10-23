@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Inventaris</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/inventory') }}">Daftar Inventaris</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Inventaris</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>Edit Data Inventaris</h5>
                    <form action="{{ url('/admin/inventory/' . $inventory->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="item_name">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('item_name') is-invalid @enderror" 
                                   id="item_name" name="item_name" placeholder="Masukkan nama barang" 
                                   value="{{ old('item_name', $inventory->item_name) }}" required>
                            @error('item_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('quantity') is-invalid @enderror" 
                                   id="quantity" name="quantity" placeholder="Masukkan jumlah barang" 
                                   value="{{ old('quantity', $inventory->quantity) }}" min="0" required>
                            @error('quantity')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="condition">Kondisi</label>
                            <select class="form-control @error('condition') is-invalid @enderror" 
                                    id="condition" name="condition">
                                <option value="">-- Pilih Kondisi --</option>
                                <option value="Baik" {{ old('condition', $inventory->condition) == 'Baik' ? 'selected' : '' }}>Baik</option>
                                <option value="Cukup" {{ old('condition', $inventory->condition) == 'Cukup' ? 'selected' : '' }}>Cukup</option>
                                <option value="Rusak" {{ old('condition', $inventory->condition) == 'Rusak' ? 'selected' : '' }}>Rusak</option>
                            </select>
                            @error('condition')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="stored_location">Lokasi Penyimpanan</label>
                            <input type="text" class="form-control @error('stored_location') is-invalid @enderror" 
                                   id="stored_location" name="stored_location" 
                                   placeholder="Masukkan lokasi penyimpanan" 
                                   value="{{ old('stored_location', $inventory->stored_location) }}">
                            @error('stored_location')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4" 
                                      placeholder="Masukkan deskripsi barang">{{ old('description', $inventory->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label>Foto Barang</label>

                            @if($inventory->photos && count($inventory->photos) > 0)
                            <div class="mb-3">
                                <label>Foto yang sudah ada:</label>
                                @foreach($inventory->photos as $index => $photo)
                                <div class="d-flex align-items-center mb-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input"
                                               id="delete_photo_{{ $index }}" name="delete_photos[]" value="{{ $index }}">
                                        <label class="custom-control-label" for="delete_photo_{{ $index }}">
                                            Hapus foto ini
                                        </label>
                                    </div>
                                    <span class="badge badge-success ml-2">
                                        <i class="fa fa-image"></i> {{ $photo['original_name'] ?? 'Foto ' . ($index + 1) }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            <div id="photo-upload-container">
                                @php
                                    $currentPhotoCount = $inventory->photos ? count($inventory->photos) : 0;
                                    $remainingPhotoSlots = 3 - $currentPhotoCount;
                                @endphp
                                @for($i = 0; $i < $remainingPhotoSlots; $i++)
                                <div class="photo-upload-item mb-2">
                                    <input type="file" class="form-control-file" name="photos[]" accept="image/*">
                                </div>
                                @endfor
                            </div>
                            <small class="form-text text-muted">
                                Format: JPEG, PNG, JPG, GIF. Maksimal 5MB per foto. Saat ini: {{ $currentPhotoCount }} foto. Slot tersisa: {{ $remainingPhotoSlots }}.
                            </small>
                            @error('photos.*')
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
                            <a href="{{ url('/admin/inventory') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
