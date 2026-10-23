
@php
    $orgName = (Auth::check() && Auth::user()->organization) ? Auth::user()->organization->name : config('app.name', 'GMR RT4');
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#343a40">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <meta name="apple-mobile-web-app-title" content="GMRT-04">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">

        <title>{{ $orgName }}</title>
        <link href="{{ asset('dist/css/styles.css') }}" rel="stylesheet" />
        <link href="{{ mix('css/main.css') }}" rel="stylesheet" />
        <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/js/all.min.js" crossorigin="anonymous"></script> -->
        <link rel="stylesheet" href="{{ asset('vendor/font-awesome/css/font-awesome.min.css') }}">

        <!-- Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js')
                        .then(function(registration) {
                            console.log('ServiceWorker registration successful');
                        })
                        .catch(function(err) {
                            console.log('ServiceWorker registration failed: ', err);
                        });
                });
            }
        </script>
    </head>
    <body class="sb-nav-fixed has-bottom-nav">
        <nav class="sb-topnav navbar navbar-expand navbar-light bg-white app-topnav-light">
            <button class="btn btn-link btn-sm d-lg-none" id="sidebarToggle" href="#"><i class="fa fa-bars"></i></button>
            <a class="navbar-brand" href="{{ url('/') }}"> {{ $orgName }}</a>
            <!-- Navbar Search-->
            <form class="d-none d-md-inline-block form-inline ml-auto mr-0 mr-md-3 my-2 my-md-0">
            </form>
            <!-- Navbar-->
            <ul class="navbar-nav ml-auto ml-md-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="userDropdown" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-user fa-fw"></i></a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="{{ url('user/password') }}">Ubah Profile Anda</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();">Logout</a>
                    </div>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-brand">
                        <span class="sb-sidenav-brand-icon"><i class="fa fa-home"></i></span>
                        <div>
                            <div class="sb-sidenav-brand-name">{{ $orgName }}</div>
                        </div>
                    </div>
                    <div class="sb-sidenav-menu">
                        @include('layouts.component.menu')
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
        <nav class="mobile-bottom-nav d-lg-none">
            <a href="{{ url('/') }}" class="bottom-nav-item {{ request()->is('/') ? 'active' : '' }}">
                <i class="fa fa-home"></i><span>Beranda</span>
            </a>
            <a href="{{ url('/payment-history') }}" class="bottom-nav-item {{ request()->is('payment-history') ? 'active' : '' }}">
                <i class="fa fa-history"></i><span>Riwayat</span>
            </a>
            <a href="{{ url('/financial-report') }}" class="bottom-nav-item {{ request()->is('financial-report') ? 'active' : '' }}">
                <i class="fa fa-line-chart"></i><span>Laporan</span>
            </a>
            <a href="{{ url('/upcoming-events') }}" class="bottom-nav-item {{ request()->is('upcoming-events') ? 'active' : '' }}">
                <i class="fa fa-calendar"></i><span>Kegiatan</span>
            </a>
            <a href="{{ url('/letters') }}" class="bottom-nav-item {{ request()->is('letters') ? 'active' : '' }}">
                <i class="fa fa-envelope"></i><span>Surat</span>
            </a>
        </nav>
        <script src="{{ asset('js/jquery-3.4.1.min.js') }}" ></script>
        <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('dist/js/scripts.js') }}"></script>
        <script>
            // On mobile, the open drawer overlays the top navbar (including the
            // hamburger that opened it), so give the dimmed backdrop a tap-to-close.
            // Scoped to mobile width only: on desktop, toggling this same class
            // instead collapses the docked sidebar, and content clicks there
            // must not re-expand it.
            $('#layoutSidenav_content').on('click', function(e) {
                if (window.innerWidth < 992 && $('body').hasClass('sb-sidenav-toggled') && e.target === this) {
                    $('body').removeClass('sb-sidenav-toggled');
                }
            });
        </script>
        @yield('scripts')
    </body>
</html>
