@extends('layouts.main')

@section('content')
    <h1 class="mt-4">{{ $user->name }} ({{ $user->blok }})</h1>
    @include('admin.master._header_warga_detail', ['active' => 'detail', 'userId' => $user->id])
    
    <!-- Tagihan Summary Cards -->
    <div class="row mb-4 mt-3">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card {{ $currentTagihan > 0 ? 'border-primary' : 'border-success' }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">
                                <i class="fa fa-calendar"></i> Tagihan Bulan Ini
                            </h6>
                            <p class="mb-1">
                                <small class="text-muted">{{ \Carbon\Carbon::now()->locale('id')->isoFormat('MMMM YYYY') }}</small>
                            </p>
                            <h4 class="mb-0 {{ $currentTagihan > 0 ? 'text-danger' : 'text-success' }}">
                                Rp {{ number_format($currentTagihan, 0, ',', '.') }}
                            </h4>
                            @if($currentTagihan <= 0)
                                <small class="text-success"><i class="fa fa-check-circle"></i> Sudah lunas</small>
                            @endif
                        </div>
                        <div>
                            <i class="fa fa-file-text fa-3x {{ $currentTagihan > 0 ? 'text-primary' : 'text-success' }} opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card {{ $tunggakanTotal > 0 ? 'border-warning' : 'border-success' }} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">
                                <i class="fa fa-exclamation-triangle"></i> Tunggakan
                            </h6>
                            <p class="mb-1">
                                <small class="text-muted">Bulan sebelumnya yang belum lunas</small>
                            </p>
                            <h4 class="mb-0 {{ $tunggakanTotal > 0 ? 'text-danger' : 'text-success' }}">
                                Rp {{ number_format($tunggakanTotal, 0, ',', '.') }}
                            </h4>
                            @if($tunggakanTotal <= 0)
                                <small class="text-success"><i class="fa fa-check-circle"></i> Tidak ada tunggakan</small>
                            @endif
                        </div>
                        <div>
                            <i class="fa fa-warning fa-3x {{ $tunggakanTotal > 0 ? 'text-warning' : 'text-success' }} opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-user"></i></span>
            <span class="app-card-title">Detail Warga</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Username:</strong>
                        </div>
                        <div class="col-md-8">
                            {{ $user->username }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Blok:</strong>
                        </div>
                        <div class="col-md-8">
                            <span class="badge badge-primary">{{ $user->blok }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Nama:</strong>
                        </div>
                        <div class="col-md-8">
                            {{ $user->name }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Nomor Telepon:</strong>
                        </div>
                        <div class="col-md-8">
                            {{ $user->phone_number ?: '-' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Email:</strong>
                        </div>
                        <div class="col-md-8">
                            {{ $user->email }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Jabatan:</strong>
                        </div>
                        <div class="col-md-8">
                            {{ $user->jabatan ?: '-' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Catatan:</strong>
                        </div>
                        <div class="col-md-8">
                            @if($user->notes)
                                <div class="alert alert-info" style="white-space: pre-wrap;">{{ $user->notes }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>WhatsApp Terdaftar:</strong>
                        </div>
                        <div class="col-md-8">
                            @if($user->whatsapp_id)
                                <span class="badge badge-success">Ya</span>
                            @else
                                <span class="badge badge-secondary">Tidak</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Role:</strong>
                        </div>
                        <div class="col-md-8">
                            @if($user->hasRole('admin'))
                                <span class="badge badge-success">Admin</span>
                            @else
                                <span class="badge badge-secondary">User</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Default Password:</strong>
                        </div>
                        <div class="col-md-8">
                            @if($user->default_password)
                                <span class="badge badge-warning">{{ $user->default_password }}</span>
                            @else
                                <span class="text-muted">Sudah dirubah</span>
                            @endif
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex flex-wrap" style="gap: .5rem;">
                        <a href="{{ url('admin/master/warga/edit/' . $user->id) }}" class="btn btn-primary">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#resetWhatsappModal">
                            <i class="fa fa-refresh"></i> Reset WhatsApp
                        </button>
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#sendWhatsappModal" {{ $user->whatsapp_id ? '' : 'disabled' }}>
                            <i class="fa fa-whatsapp"></i> Kirim Pesan WhatsApp
                        </button>
                        <a href="{{ url('admin/master/warga') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    <!-- Reset WhatsApp Modal -->
    <div class="modal fade" id="resetWhatsappModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Reset WhatsApp</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin mereset WhatsApp ID dan password default untuk <strong>{{ $user->name }}</strong>?</p>
                    <ul class="mb-0">
                        <li>WhatsApp ID akan dihapus (direset ke kosong)</li>
                        <li>Password default akan diganti dengan angka acak baru</li>
                        <li>Warga perlu mendaftarkan ulang WhatsApp dan login dengan password baru</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <form method="POST" action="{{ route('admin.warga.reset-whatsapp', $user->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-refresh"></i> Ya, Reset Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Send WhatsApp Modal -->
    <div class="modal fade" id="sendWhatsappModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.warga.send-whatsapp', $user->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Kirim Pesan WhatsApp</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Kirim pesan WhatsApp ke <strong>{{ $user->name }}</strong></p>
                        <div class="form-group">
                            <label for="message">Isi Pesan</label>
                            <textarea name="message" id="message" class="form-control @error('message') is-invalid @enderror" rows="5" required>{{ old('message') }}</textarea>
                            @error('message')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-whatsapp"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($user->logs && $user->logs->count() > 0)
    <div class="card">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-info"><i class="fa fa-history"></i></span>
            <span class="app-card-title">Riwayat Log (10 Terakhir)</span>
        </div>
        <div class="card-body">
            <!-- Mobile: stacked cards -->
            <div class="d-md-none border rounded">
                <div class="list-group list-group-flush">
                    @foreach($user->logs->sortByDesc('id')->take(10) as $log)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong class="small">{{ $log->username }}</strong>
                            <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <div class="small" style="white-space: pre-line;">{{ $log->description }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Admin</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->logs->sortByDesc('id')->take(10) as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $log->username }}</td>
                            <td style="white-space: pre-line;">{{ $log->description }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@endsection