    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
        <li class="breadcrumb-item active">Iuran</li>
    </ol>
    
    <div class="alert alert-info mb-3">
        <i class="fa fa-info-circle"></i> 
        <strong>Informasi Daftar Iuran:</strong> 
        Kelola jenis-jenis iuran warga RT seperti iuran kebersihan, keamanan, atau iuran bulanan lainnya. 
        Setiap iuran yang dibuat akan menjadi template yang dapat digunakan saat membuat tagihan untuk warga.
        <ul class="mb-0 mt-2">
            <li><strong>Daftar Iuran:</strong> Tampilan semua template iuran yang telah dibuat beserta nominal dan status aktif/nonaktif</li>
            <li><strong>Tambah Iuran Warga:</strong> Form untuk membuat template iuran baru dengan detail nama, nominal, dan keterangan</li>
            <li><strong>Buat Tagihan Manual:</strong> Form untuk membuat tagihan dengan memilih template iuran manual, menentukan warga, menyesuaikan nominal (dapat diedit dari nilai default), dan memilih waktu generate tagihan (sekarang atau tanggal tertentu)</li>
        </ul>
    </div>
    
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/master/tagihan') ? 'active' : '' }}" href="{{ url('/admin/master/tagihan') }}">Daftar Iuran</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/master/tagihan/create') ? 'active' : '' }}" href="{{ url('/admin/master/tagihan/create') }}">Tambah Iuran Warga</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/master/tagihan/manual/create') ? 'active' : '' }}" href="{{ url('/admin/master/tagihan/manual/create') }}">Buat Tagihan Manual</a>
        </li>
    </ul>