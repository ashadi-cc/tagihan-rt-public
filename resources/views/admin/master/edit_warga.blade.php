@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Daftar Warga</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/master/warga') }}">Daftar Warga</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Warga</li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Edit Warga</h5>
                    <form action="{{ url('admin/master/warga/edit/' . $user->id) }}" method="POST">
                        @csrf
                        {{ method_field('PUT') }}
                        <div class="form-group">
                            <label for="">Blok</label>
                            <input type="text" class="form-control" value="{{ $user->blok }}" readonly disabled>
                        </div>
                        <div class="form-group">
                            <label for="">Nama</label>
                            <input type="text" class="form-control" name="nama" placeholder="Nama warga" value="{{ old('nama') ?: $user->name }}">
                            @error('nama')
                                <div class="text-danger">
                                    {{ $errors->first('nama') }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="">Nomor Telepon</label>
                            <input type="text" class="form-control" name="phone_number" placeholder="Nomor telepon warga" value="{{ old('phone_number') ?: $user->phone_number }}">
                            @error('phone_number')
                                <div class="text-danger">
                                    {{ $errors->first('phone_number') }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Email warga" value="{{ old('email') ?: $user->email }}" autocomplete="off">
                            @error('email')
                                <div class="text-danger">
                                    {{ $errors->first('email') }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="">Password</label>
                            <input type="text" class="form-control" name="password" autocomplete="off">
                            <small>Kosongi jika tidak ingin merubah password lama</small>
                            @error('password')
                                <div class="text-danger">
                                    {{ $errors->first('password') }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="">Jabatan</label>
                            <input type="text" class="form-control" name="jabatan" placeholder="Jabatan warga" value="{{ old('jabatan') ?: $user->jabatan }}">
                            @error('jabatan')
                                <div class="text-danger">
                                    {{ $errors->first('jabatan') }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="">Catatan</label>
                            <textarea class="form-control" name="notes" rows="4" placeholder="Catatan tambahan tentang warga">{{ old('notes') ?: $user->notes }}</textarea>
                            @error('notes')
                                <div class="text-danger">
                                    {{ $errors->first('notes') }}
                                </div>
                            @enderror
                        </div>
                        @if (auth()->user()->id != $user->id)
                        <div class="form-check">
                            <label class="form-check-label">
                                <input type="checkbox" class="form-check-input" value="1" name="is_admin" @if($user->hasRole('admin')) checked @endif>Admin?
                            </label>
                        </div>
                        @endif
                        <hr>
                        <div class="form-group d-flex flex-wrap" style="gap: .5rem;">
                            <input type="submit" class="btn btn-success" value="Simpan">
                            <a href="{{ url('admin/master/warga') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

