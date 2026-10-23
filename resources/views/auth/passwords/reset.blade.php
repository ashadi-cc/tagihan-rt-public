@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">

            <div class="text-center mb-4 mt-2">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary mb-3"
                     style="width:56px;height:56px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="white" viewBox="0 0 16 16">
                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                    </svg>
                </div>
                <h5 class="font-weight-bold mb-1">Buat Password Baru</h5>
                <p class="text-muted small mb-0">Masukkan password baru untuk akun Anda</p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="email" value="{{ $email }}">

                        @if($userName || $blok)
                        <div class="bg-light rounded p-3 mb-4 d-flex align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary mr-3 flex-shrink-0"
                                 style="width:40px;height:40px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="white" viewBox="0 0 16 16">
                                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-weight-bold text-dark" style="font-size:.95rem;">{{ $userName }}</div>
                                <div class="text-muted small">Blok {{ $blok }}</div>
                            </div>
                        </div>
                        @endif

                        <div class="form-group mb-3">
                            <label for="password" class="font-weight-semibold small text-uppercase text-muted mb-1">
                                Password Baru
                            </label>
                            <input id="password"
                                   type="password"
                                   class="form-control form-control-lg @error('password') is-invalid @enderror"
                                   name="password"
                                   placeholder="Minimal 6 karakter"
                                   required
                                   autocomplete="new-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label for="password-confirm" class="font-weight-semibold small text-uppercase text-muted mb-1">
                                Konfirmasi Password
                            </label>
                            <input id="password-confirm"
                                   type="password"
                                   class="form-control form-control-lg"
                                   name="password_confirmation"
                                   placeholder="Ulangi password baru"
                                   required
                                   autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            Simpan Password
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
