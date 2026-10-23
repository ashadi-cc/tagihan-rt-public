    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ url('/admin/master/warga') }}">Warga</a></li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
    
    <div class="alert alert-info mb-3">
        <i class="fa fa-info-circle"></i> 
        <strong>Informasi Detail Warga:</strong> 
        <ul class="mb-0 mt-2">
            <li><strong>Detail:</strong> Informasi lengkap data warga seperti nama, alamat, dan kontak</li>
            <li><strong>Tagihan:</strong> Daftar semua tagihan warga (lunas dan belum lunas)</li>
            <li><strong>Pembayaran:</strong> Riwayat pembayaran yang telah dilakukan (terverifikasi dan belum terverifikasi)</li>
            <li><strong>Deposit:</strong> Saldo deposit warga yang dapat digunakan untuk membayar tagihan</li>
            <li><strong>Buat Tunggakan Iuran:</strong> Form untuk mencatat tunggakan iuran bulanan yang belum terbayar di periode kepengurusan sebelumnya</li>
        </ul>
    </div>
    
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link {{ $active == 'detail' ? 'active' : '' }}" href="{{ url('/admin/master/warga/view/' . $userId) }}">Detail</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $active == 'tagihan' ? 'active' : '' }}" href="{{ url('/admin/master/warga/' . $userId . '/tagihan') }}">Tagihan</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $active == 'pembayaran' ? 'active' : '' }}" href="{{ url('/admin/master/warga/' . $userId . '/pembayaran') }}">Pembayaran</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $active == 'deposit' ? 'active' : '' }}" href="{{ url('/admin/master/warga/' . $userId . '/deposit') }}">Deposit</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $active == 'tunggakan' ? 'active' : '' }}" href="{{ url('/admin/master/warga/' . $userId . '/tunggakan') }}">Buat Tunggakan Iuran</a>
        </li>
    </ul>
