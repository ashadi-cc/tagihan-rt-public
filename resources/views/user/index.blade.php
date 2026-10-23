@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title"><i class="fa fa-user"></i> Profile Anda</h4>
        <p class="app-page-header-sub">Kelola informasi akun dan keamanan Anda</p>
    </div>

    <!-- Account Info Card (read-only) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-user"></i></span>
            <span class="app-card-title">Informasi Akun</span>
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-5 text-muted">Username:</div>
                <div class="col-7"><strong>{{ $user->username }}</strong></div>
            </div>
            <div class="row mb-2">
                <div class="col-5 text-muted">Blok Rumah:</div>
                <div class="col-7"><strong>{{ $user->blok }}</strong></div>
            </div>
            <div class="row mb-2">
                <div class="col-5 text-muted">Nama:</div>
                <div class="col-7"><strong>{{ $user->name }}</strong></div>
            </div>
            <div class="row">
                <div class="col-5 text-muted">Nomor Telepon:</div>
                <div class="col-7"><strong>{{ $user->phone_number }}</strong></div>
            </div>
        </div>
    </div>

    <!-- Security Form Card -->
    <div class="card border-0 shadow-sm" id="app">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-warning"><i class="fa fa-lock"></i></span>
            <span class="app-card-title">Keamanan Akun</span>
        </div>
        <div class="card-body p-3">
            <form action="{{ url('user/password') }}" method="POST">
                @csrf
                {{ method_field('PUT') }}

                <!-- Email -->
                <div class="form-group mb-3">
                    <label for="" class="text-muted small mb-1">Email</label>
                    <input type="email" class="form-control" name="email" placeholder="Email anda" value="{{ old('email') ?: $user->email }}">
                    @error('email')
                        <div class="alert alert-danger py-2 px-3 mt-2 mb-0 small">
                            <i class="fa fa-exclamation-circle"></i> {{ $errors->first('email') }}
                        </div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group mb-3">
                    <label for="" class="text-muted small mb-1">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Masukkan password baru">
                    <small class="form-text text-muted">Kosongi jika tidak ingin merubah password lama</small>
                    @error('password')
                        <div class="alert alert-danger py-2 px-3 mt-2 mb-0 small">
                            <i class="fa fa-exclamation-circle"></i> {{ $errors->first('password') }}
                        </div>
                    @enderror
                </div>

                <!-- Divider -->
                <hr class="my-3">

                <!-- Action Buttons -->
                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-success btn-block mb-2">
                        <i class="fa fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-block mt-0">
                        <i class="fa fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Mobile-First Optimizations */
@media (max-width: 576px) {
    .container-fluid {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    .card {
        border-radius: 12px;
    }
    
    h4 {
        font-size: 1.25rem;
    }
    
    .small {
        font-size: 0.8rem;
    }
}

/* Card hover effect */
.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

/* Better spacing for mobile */
.card-body hr {
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

/* Form input styling */
.form-control:disabled {
    background-color: #f8f9fa;
    border-color: #e9ecef;
}

/* Button styling for mobile */
@media (min-width: 768px) {
    .btn-block {
        display: inline-block;
        width: auto;
        min-width: 150px;
    }
    
    .btn-block.mb-2 {
        margin-bottom: 0 !important;
        margin-right: 0.5rem;
    }
}
</style>
@endsection