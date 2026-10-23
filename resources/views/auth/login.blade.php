<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('site.name', 'GMR_RT04') }}</title>
        <link href="{{ asset('dist/css/styles.css') }}" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('vendor/font-awesome/css/font-awesome.min.css') }}">
        <style>
            body {
                background: #f3f4f8;
                min-height: 100vh;
                display: flex;
                align-items: center;
            }

            .login-container {
                width: 100%;
                padding: 15px;
            }

            .login-card {
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(20, 20, 43, 0.08);
                max-width: 400px;
                margin: 0 auto;
                background: white;
            }

            .login-body {
                padding: 2.5rem 2rem 2rem;
            }

            .login-logo {
                width: 56px;
                height: 56px;
                border-radius: 14px;
                background: linear-gradient(135deg, #4f6df5 0%, #4338ca 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1rem;
                font-size: 1.5rem;
                color: white;
                box-shadow: 0 8px 16px rgba(79, 109, 245, 0.35);
            }

            .login-title {
                text-align: center;
                margin: 0;
                font-weight: 700;
                font-size: 1.4rem;
                color: #1c1e2b;
            }

            .login-subtitle {
                text-align: center;
                margin: 0.25rem 0 1.75rem;
                font-size: 0.9rem;
                color: #8a8d9c;
            }

            .form-group {
                margin-bottom: 1.25rem;
            }

            .form-group-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 0.4rem;
            }

            .form-group label {
                font-weight: 600;
                color: #1c1e2b;
                font-size: 0.9rem;
                margin: 0;
            }

            .form-control {
                border-radius: 10px;
                border: 1.5px solid #e3e4ec;
                padding: 0.7rem 1rem;
                font-size: 1rem;
            }

            .form-control:focus {
                border-color: #4f6df5;
                box-shadow: 0 0 0 0.2rem rgba(79, 109, 245, 0.12);
            }

            .form-control.is-invalid {
                border-color: #dc3545;
            }

            .btn-login {
                background: linear-gradient(135deg, #4f6df5 0%, #4338ca 100%);
                border: none;
                border-radius: 10px;
                padding: 0.8rem 2rem;
                font-weight: 600;
                color: white;
                width: 100%;
                font-size: 1rem;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .btn-login:hover {
                transform: translateY(-1px);
                box-shadow: 0 8px 18px rgba(79, 109, 245, 0.35);
                color: white;
            }

            .btn-login:active {
                transform: translateY(0);
            }

            .back-home {
                display: block;
                text-align: center;
                margin-top: 1.5rem;
                color: #8a8d9c;
                font-size: 0.9rem;
                text-decoration: none;
            }

            .back-home:hover {
                color: #4f6df5;
                text-decoration: none;
            }

            .custom-checkbox {
                padding-left: 1.5rem;
            }

            .custom-control-label {
                color: #666;
                font-size: 0.9rem;
                cursor: pointer;
            }

            .alert {
                border-radius: 8px;
                margin-top: 0.5rem;
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
            }

            /* Mobile Optimizations */
            @media (max-width: 576px) {
                .login-container {
                    padding: 24px;
                }

                .login-card {
                    margin-top: 20px;
                    margin-bottom: 20px;
                }

                .login-body {
                    padding: 2rem 1.5rem 1.5rem;
                }

                .form-control {
                    font-size: 16px; /* Prevents zoom on iOS */
                }

                .btn-login {
                    padding: 0.875rem 2rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-card">
                <div class="login-body">
                    <div class="login-logo"><i class="fa fa-home"></i></div>
                    <h3 class="login-title">{{ config('site.name', 'GMR-RT04') }}</h3>
                    <p class="login-subtitle">Sistem Informasi Tagihan Iuran</p>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="form-group">
                            <div class="form-group-header">
                                <label for="username">Blok Rumah</label>
                            </div>
                            <input class="form-control @error('username') is-invalid @enderror"
                                   name="username"
                                   value="{{ old('username') }}"
                                   required
                                   autocomplete="username"
                                   autofocus
                                   id="username"
                                   placeholder="Contoh: k1 (huruf kecil)" />
                            @error('username')
                                <div class="alert alert-danger" role="alert">
                                    <i class="fa fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="form-group-header">
                                <label for="inputPassword">Password</label>
                            </div>
                            <input class="form-control @error('password') is-invalid @enderror"
                                   name="password"
                                   required
                                   autocomplete="current-password"
                                   id="inputPassword"
                                   type="password"
                                   placeholder="Masukkan password" />
                            @error('password')
                                <div class="alert alert-danger" role="alert">
                                    <i class="fa fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input"
                                       id="rememberPasswordCheck"
                                       name="remember"
                                       type="checkbox" />
                                <label class="custom-control-label" for="rememberPasswordCheck">
                                    Ingat saya
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-login">
                                Masuk
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src="{{ asset('js/jquery-3.4.1.min.js') }}" ></script>
        <script>
            $('#username').change(function() {
                if ($(this).val()) {
                    var val = $(this).val().toLowerCase();
                    $(this).val(val);
                }
            })
        </script>
    </body>
</html>
