<div class="nav">
    <div class="sb-sidenav-menu-heading">Pribadi</div>
    <a class="nav-link" href="{{ url('/') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-address-book"></i></div>
        Tagihan Anda</a>
    <a class="nav-link" href="{{ url('/payment-history') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-history"></i></div>
        Riwayat Pembayaran</a>

    @php
        // Check if user has any deposit
        $hasDeposit = auth()->user()->pembayarans()
            ->where('payment_type', 'user_deposit')
            ->where('status', 'verified')
            ->exists();
    @endphp

    @if($hasDeposit)
    <a class="nav-link" href="{{ url('/user-deposit') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-history"></i></div>
        Riwayat Deposit</a>
    @endif

    <a class="nav-link" href="{{ url('/financial-report') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-line-chart"></i></div>
        Laporan Keuangan RT</a>

    <a class="nav-link" href="{{ url('/dawis-financial-report') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-line-chart"></i></div>
        Laporan Keuangan Dawis</a>

    <a class="nav-link" href="{{ url('/upcoming-events') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-calendar"></i></div>
        Kegiatan RT</a>

    <a class="nav-link" href="{{ url('/letters') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-envelope"></i></div>
        Arsip Surat</a>

    <a class="nav-link" href="{{ url('/inventaris') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-archive"></i></div>
        Inventaris RT</a>

    <a class="nav-link" href="{{ url('/tenant') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-home"></i></div>
        Pengontrak Rumah</a>

    @role('admin')
    <div class="sb-sidenav-menu-heading">Admin</div>
    <a class="nav-link" href="{{ url('/admin/dashboard') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-dashboard"></i></div>
        Dashboard</a
    ></a>
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts"
        ><div class="sb-nav-link-icon"><i class="fa fa-columns"></i></div>
        Master
        <div class="sb-sidenav-collapse-arrow"><i class="fa fa-angle-down"></i></div
    ></a>
    <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{{ url('/admin/master/warga') }}">Daftar Warga</a>
            <a class="nav-link" href="{{ url('/admin/master/tagihan') }}">Daftar Iuran</a>
            <a class="nav-link" href="{{ url('/admin/master/payment') }}">Channel Pembayaran</a>
        </nav>
    </div>
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages"
        ><div class="sb-nav-link-icon"><i class="fa fa-book"></i></div>
        Administrasi
        <div class="sb-sidenav-collapse-arrow"><i class="fa fa-angle-down"></i></div
    ></a>
    <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a href="{{ url('/admin/payment') }}" class="nav-link">Pembayaran Iuran</a>
            <a class="nav-link" href="{{ url('/admin/rt-transaction') }}">Pemasukan dan Pengeluaran</a>
            <a href="{{ url('/admin/inventory') }}" class="nav-link">Inventaris</a>
             <a href="{{ url('/admin/tenant') }}" class="nav-link">Daftar Warga Pengontrak</a>
            <a href="{{ url('/admin/event') }}" class="nav-link">Kegiatan RT</a>
            <a href="{{ url('/admin/surat-menyurat') }}" class="nav-link">Surat Menyurat</a>
        </nav>
    </div>
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReports" aria-expanded="false" aria-controls="collapseReports"
        ><div class="sb-nav-link-icon"><i class="fa fa-file-text"></i></div>
        Laporan
        <div class="sb-sidenav-collapse-arrow"><i class="fa fa-angle-down"></i></div
    ></a>
    <div class="collapse" id="collapseReports" aria-labelledby="headingThree" data-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{{ url('/admin/income-per-iuran') }}">Pemasukan Per Iuran</a>
            <a class="nav-link" href="{{ url('/admin/iuran-per-warga') }}">Tagihan Iuran Per Warga</a>
            <a class="nav-link" href="{{ url('/admin/deposit-per-warga') }}">Deposit Per Warga</a>
        </nav>
    </div>
    <a class="nav-link" href="{{ url('/admin/dawis-transaction') }}"
        ><div class="sb-nav-link-icon"><i class="fa fa-line-chart"></i></div>
        Laporan Keuangan Dawis</a>
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSystem" aria-expanded="false" aria-controls="collapseSystem"
        ><div class="sb-nav-link-icon"><i class="fa fa-cog"></i></div>
        System
        <div class="sb-sidenav-collapse-arrow"><i class="fa fa-angle-down"></i></div
    ></a>
    <div class="collapse" id="collapseSystem" aria-labelledby="headingFour" data-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{{ url('/admin/activity-log') }}">Activity Logs</a>
            <a class="nav-link" href="{{ url('/admin/organization') }}">Organization</a>
            @role('superadmin')
            <a class="nav-link" href="{{ url('/admin/bot-service') }}">Bot Service</a>
            @endrole
        </nav>
    </div>
    @endrole
</div>