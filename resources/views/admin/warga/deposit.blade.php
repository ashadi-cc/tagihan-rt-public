@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Deposit {{ $user->name }} ({{ $user->blok }})</h1>
    @include('admin.master._header_warga_detail', ['active' => 'deposit', 'userId' => $user->id])

    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-money"></i></span>
            <span class="app-card-title">Daftar Deposit</span>
            <a href="{{ route('admin.warga.deposit.create', ['userID' => $user->id]) }}" class="btn btn-sm btn-primary">
                <i class="fa fa-plus"></i> Tambah Deposit
            </a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-12 col-md-6 mb-3 mb-md-0">
                    <div class="app-summary-card app-summary-card-info app-summary-card-compact">
                        <span class="icon-chip icon-chip-lg"><i class="fa fa-arrow-down"></i></span>
                        <div>
                            <div class="app-summary-label">Total Deposit Terverifikasi</div>
                            <div class="app-summary-value">Rp {{ number_format($totalDeposit, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="app-summary-card app-summary-card-success app-summary-card-compact">
                        <span class="icon-chip icon-chip-lg"><i class="fa fa-check-circle"></i></span>
                        <div>
                            <div class="app-summary-label">Sisa Deposit</div>
                            <div class="app-summary-value">Rp {{ number_format($totalDeposit - $totalUsedDeposit, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if($deposits->isEmpty())
                <div class="alert alert-warning">
                    <i class="fa fa-info-circle"></i> Belum ada riwayat deposit untuk warga ini.
                </div>
            @else
                <!-- Mobile: stacked cards -->
                <div class="d-md-none list-group list-group-flush mb-3">
                    @foreach($deposits as $index => $deposit)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong>{{ $deposit->payment_name }}</strong>
                            @if($deposit->status == 'verified')
                                <span class="badge badge-success">Terverifikasi</span>
                            @elseif($deposit->status == 'pending')
                                <span class="badge badge-warning">Menunggu Verifikasi</span>
                            @elseif($deposit->status == 'rejected')
                                <span class="badge badge-danger">Ditolak</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($deposit->status) }}</span>
                            @endif
                        </div>
                        <div class="text-muted small mb-2">{{ $deposit->payment_date->format('d M Y') }}</div>
                        <div class="mb-2"><strong>Rp {{ number_format($deposit->payment_amount, 0, ',', '.') }}</strong></div>
                        @if($deposit->notes)
                            <div class="text-muted small mb-2">{{ Str::limit($deposit->notes, 80) }}</div>
                        @endif
                        <div class="text-muted small mb-2">
                            Dibuat oleh {{ $deposit->created_by_user_name ?? '-' }} &middot; {{ $deposit->created_at->format('d M Y H:i') }}
                        </div>
                        <a href="{{ route('admin.warga.deposit.show', ['userID' => $user->id, 'depositID' => $deposit->id]) }}" class="btn btn-info btn-sm">
                            <i class="fa fa-eye"></i> Lihat
                        </a>
                    </div>
                    @endforeach
                </div>

                <!-- Tablet/desktop: table -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="12%">Tanggal</th>
                                <th width="21%">Metode Pembayaran</th>
                                <th width="12%">Jumlah</th>
                                <th width="12%">Status</th>
                                <th width="12%">Dibuat Oleh</th>
                                <th width="15%">Catatan</th>
                                <th width="6%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deposits as $index => $deposit)
                                <tr>
                                    <td>{{ $deposits->firstItem() + $index }}</td>
                                    <td>{{ $deposit->payment_date->format('d M Y') }}</td>
                                    <td>
                                        <strong>{{ $deposit->payment_name }}</strong>
                                        @if($deposit->payment_info_metadata)
                                            @php
                                                $info = is_string($deposit->payment_info_metadata)
                                                    ? json_decode($deposit->payment_info_metadata, true)
                                                    : $deposit->payment_info_metadata;
                                            @endphp
                                            @if(!empty($info['from_bank']))
                                                <br><small class="text-muted">
                                                    {{ $info['from_bank'] }}
                                                    @if(!empty($info['from_account_number']))
                                                        - {{ $info['from_account_number'] }}
                                                    @endif
                                                </small>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <strong>Rp {{ number_format($deposit->payment_amount, 0, ',', '.') }}</strong>
                                    </td>
                                    <td class="text-center">
                                        @if($deposit->status == 'verified')
                                            <span class="badge badge-success">Terverifikasi</span>
                                            @if($deposit->verified_at)
                                                <br><small class="text-muted">{{ $deposit->verified_at->format('d M Y H:i') }}</small>
                                            @endif
                                        @elseif($deposit->status == 'pending')
                                            <span class="badge badge-warning">Menunggu Verifikasi</span>
                                        @elseif($deposit->status == 'rejected')
                                            <span class="badge badge-danger">Ditolak</span>
                                        @else
                                            <span class="badge badge-secondary">{{ ucfirst($deposit->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $deposit->created_by_user_name ?? '-' }}</small>
                                        <br><small class="text-muted">{{ $deposit->created_at->format('d M Y H:i') }}</small>
                                    </td>
                                    <td>
                                        @if($deposit->notes)
                                            <small>{{ Str::limit($deposit->notes, 50) }}</small>
                                        @else
                                            <small class="text-muted">-</small>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('admin.warga.deposit.show', ['userID' => $user->id, 'depositID' => $deposit->id]) }}"
                                           class="btn btn-info btn-sm"
                                           title="Lihat Detail">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="text-muted small mb-2 mb-sm-0">
                            Menampilkan {{ $deposits->firstItem() }} - {{ $deposits->lastItem() }} dari {{ $deposits->total() }} data
                        </div>
                        <div>
                            {{ $deposits->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
