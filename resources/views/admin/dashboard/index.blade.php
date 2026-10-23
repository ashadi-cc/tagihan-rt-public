@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Dashboard</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <!-- Pending Payments -->
        <div class="col-6 col-lg-3 mb-3 mb-lg-0">
            <div class="app-summary-card app-summary-card-warning app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-clock-o"></i></span>
                <div>
                    <div class="app-summary-label">Konfirmasi Pending</div>
                    <div class="app-summary-value">{{ $totalPendingPayments }}</div>
                </div>
            </div>
        </div>

        <!-- Verified This Month -->
        <div class="col-6 col-lg-3 mb-3 mb-lg-0">
            <div class="app-summary-card app-summary-card-success app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-check-circle"></i></span>
                <div>
                    <div class="app-summary-label">Terverifikasi Bulan Ini</div>
                    <div class="app-summary-value">{{ $totalVerifiedPayments }}</div>
                </div>
            </div>
        </div>

        <!-- Sisa Kas RT -->
        <div class="col-6 col-lg-3">
            <a href="{{ url('/admin/rt-transaction') }}" class="app-summary-card app-summary-card-primary app-summary-card-compact text-decoration-none">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-check-circle"></i></span>
                <div>
                    <div class="app-summary-label">Sisa Kas RT</div>
                    <div class="app-summary-value">Rp {{ number_format($sisaKas, 0, ',', '.') }}</div>
                </div>
            </a>
        </div>

        <!-- Total Warga -->
        <div class="col-6 col-lg-3">
            <div class="app-summary-card app-summary-card-info app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-users"></i></span>
                <div>
                    <div class="app-summary-label">Total Warga</div>
                    <div class="app-summary-value">{{ $totalWarga }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deposit Summary (reference info, compact tiles; desktop only, hidden on mobile) -->
    <div class="row mb-4 d-none d-md-flex">
        <div class="col-12">
            <div class="card border-0 shadow bg-light">
                <div class="d-flex align-items-center px-3 pt-4 pb-2" style="gap: .65rem;">
                    <span class="icon-chip icon-chip-primary"><i class="fa fa-money"></i></span>
                    <span class="app-card-title">Ringkasan Deposit Warga</span>
                </div>
                <div class="card-body pb-4">
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="app-summary-card app-summary-card-primary">
                                <span class="icon-chip icon-chip-lg icon-chip-primary"><i class="fa fa-arrow-down"></i></span>
                                <div>
                                    <div class="app-summary-label">Total Deposit Masuk</div>
                                    <div class="app-summary-value">Rp {{ number_format($totalDeposit, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="app-summary-card app-summary-card-warning">
                                <span class="icon-chip icon-chip-lg icon-chip-warning"><i class="fa fa-shopping-cart"></i></span>
                                <div>
                                    <div class="app-summary-label">Total Deposit Terpakai</div>
                                    <div class="app-summary-value">Rp {{ number_format($totalUsedDeposit, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="app-summary-card app-summary-card-success">
                                <span class="icon-chip icon-chip-lg icon-chip-success"><i class="fa fa-check-circle"></i></span>
                                <div>
                                    <div class="app-summary-label">Sisa Deposit Tersedia</div>
                                    <div class="app-summary-value">Rp {{ number_format($availableDeposit, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RT Finance Summary (reference info, compact tiles; desktop only, hidden on mobile) -->
    <div class="row mb-4 d-none d-md-flex">
        <div class="col-12">
            <div class="card border-0 shadow bg-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap px-3 pt-4 pb-2" style="gap: .5rem;">
                    <div class="d-flex align-items-center" style="gap: .65rem;">
                        <span class="icon-chip icon-chip-primary"><i class="fa fa-line-chart"></i></span>
                        <span class="app-card-title">Ringkasan Keuangan RT</span>
                    </div>
                    <a href="{{ url('/admin/rt-transaction') }}" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fa fa-arrow-right"></i> Lihat Transaksi
                    </a>
                </div>
                <div class="card-body pb-4">
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="app-summary-card app-summary-card-success">
                                <span class="icon-chip icon-chip-lg icon-chip-success"><i class="fa fa-arrow-down"></i></span>
                                <div>
                                    <div class="app-summary-label">Total Pemasukan</div>
                                    <div class="app-summary-value">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="app-summary-card app-summary-card-danger">
                                <span class="icon-chip icon-chip-lg icon-chip-danger"><i class="fa fa-arrow-up"></i></span>
                                <div>
                                    <div class="app-summary-label">Total Pengeluaran</div>
                                    <div class="app-summary-value">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="app-summary-card app-summary-card-primary">
                                <span class="icon-chip icon-chip-lg icon-chip-primary"><i class="fa fa-check-circle"></i></span>
                                <div>
                                    <div class="app-summary-label">Sisa Kas</div>
                                    <div class="app-summary-value">Rp {{ number_format($sisaKas, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Pending Pembayaran -->
        <div class="col-lg-7 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="app-card-header">
                    <span class="icon-chip icon-chip-warning"><i class="fa fa-clock-o"></i></span>
                    <span class="app-card-title">Konfirmasi Pembayaran Pending</span>
                    @if($totalPendingPayments > 10)
                        <a href="{{ url('/admin/payment?status=pending') }}" class="btn btn-sm btn-outline-primary">
                            Lihat Semua
                        </a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if($pendingPembayaran->isEmpty())
                        <div class="p-4 text-center text-muted">
                            <i class="fa fa-check-circle fa-3x mb-3 text-success"></i>
                            <p class="mb-0">Tidak ada konfirmasi pembayaran yang pending</p>
                        </div>
                    @else
                        <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
                        <div class="d-md-none list-group list-group-flush">
                            @foreach($pendingPembayaran as $pembayaran)
                            @php
                                $badgeClass = $pembayaran->payment_type == 'current_billing' ? 'primary'
                                    : ($pembayaran->payment_type == 'previous_billing' ? 'warning'
                                    : ($pembayaran->payment_type == 'user_deposit' ? 'info' : 'secondary'));
                                $showUrl = $pembayaran->payment_type == 'user_deposit'
                                    ? route('admin.warga.deposit.show', ['userID' => $pembayaran->user_id, 'depositID' => $pembayaran->id])
                                    : route('admin.warga.pembayaran.show', ['userID' => $pembayaran->user_id, 'pembayaranID' => $pembayaran->id]);
                            @endphp
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>{{ $pembayaran->user_name }}</strong>
                                        <div class="text-muted small">{{ $pembayaran->user_blok }}</div>
                                    </div>
                                    <span class="badge badge-{{ $badgeClass }}">{{ $pembayaran->formatted_payment_type }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>{!! $pembayaran->getLabelPaymentAmount() !!}</div>
                                    <small class="text-muted">{{ $pembayaran->created_at->diffForHumans() }}</small>
                                </div>
                                <a href="{{ $showUrl }}" class="btn btn-sm btn-outline-primary btn-block">
                                    <i class="fa fa-eye"></i> Lihat
                                </a>
                            </div>
                            @endforeach
                        </div>

                        <!-- Tablet/desktop: table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Warga</th>
                                        <th>Tipe</th>
                                        <th>Jumlah</th>
                                        <th>Waktu</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingPembayaran as $pembayaran)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $pembayaran->user_name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $pembayaran->user_blok }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($pembayaran->payment_type == 'current_billing')
                                                <span class="badge badge-primary">{{ $pembayaran->formatted_payment_type }}</span>
                                            @elseif($pembayaran->payment_type == 'previous_billing')
                                                <span class="badge badge-warning">{{ $pembayaran->formatted_payment_type }}</span>
                                            @elseif($pembayaran->payment_type == 'user_deposit')
                                                <span class="badge badge-info">{{ $pembayaran->formatted_payment_type }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $pembayaran->formatted_payment_type }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {!! $pembayaran->getLabelPaymentAmount() !!}
                                        </td>
                                        <td>
                                            <small>{{ $pembayaran->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ $pembayaran->payment_type == 'user_deposit' ? route('admin.warga.deposit.show', ['userID' => $pembayaran->user_id, 'depositID' => $pembayaran->id]) : route('admin.warga.pembayaran.show', ['userID' => $pembayaran->user_id, 'pembayaranID' => $pembayaran->id]) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-eye"></i> Lihat
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Top Warga Tagihan & Recent Activity Logs -->
        <div class="col-lg-5 mb-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="app-card-header">
                            <span class="icon-chip icon-chip-danger"><i class="fa fa-list-ol"></i></span>
                            <span class="app-card-title">Top 10 Warga Tunggakan Terbanyak</span>
                        </div>
                        <div class="card-body p-0">
                            @if($topWargaTagihan->isEmpty())
                                <div class="p-4 text-center text-muted">
                                    <i class="fa fa-user-times fa-3x mb-3"></i>
                                    <p class="mb-0">Tidak ada warga dengan tunggakan</p>
                                </div>
                            @else
                                <!-- Mobile: stacked cards -->
                                <div class="d-md-none list-group list-group-flush">
                                    @foreach($topWargaTagihan as $warga)
                                    <a href="{{ url('admin/master/warga/view/' . $warga->user_id) }}" class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div>{{ $warga->user_name }}</div>
                                                <small class="text-muted">{{ $warga->user_blok_number }}</small>
                                            </div>
                                            <div class="text-danger font-weight-bold">Rp {{ number_format($warga->total_tagihan, 0, ',', '.') }}</div>
                                        </div>
                                    </a>
                                    @endforeach
                                </div>

                                <!-- Tablet/desktop: table -->
                                <div class="table-responsive d-none d-md-block">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Nama</th>
                                                <th>Blok</th>
                                                <th class="text-right">Total Tunggakan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topWargaTagihan as $warga)
                                            <tr>
                                                <td>
                                                    <a href="{{ url('admin/master/warga/view/' . $warga->user_id) }}">
                                                        {{ $warga->user_name }}
                                                    </a>
                                                </td>
                                                <td>{{ $warga->user_blok_number }}</td>
                                                <td class="text-right text-danger font-weight-bold">Rp {{ number_format($warga->total_tagihan, 0, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="app-card-header">
                            <span class="icon-chip icon-chip-info"><i class="fa fa-history"></i></span>
                            <span class="app-card-title">Aktivitas Terakhir</span>
                            <a href="{{ url('/admin/activity-log') }}" class="btn btn-sm btn-outline-primary">
                                Lihat Semua
                            </a>
                        </div>
                        <div class="card-body p-0">
                            @if($recentLogs->isEmpty())
                                <div class="p-4 text-center text-muted">
                                    <i class="fa fa-history fa-3x mb-3"></i>
                                    <p class="mb-0">Belum ada aktivitas</p>
                                </div>
                            @else
                                <div class="list-group list-group-flush">
                                    @foreach($recentLogs as $log)
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-start">
                                            <div class="mr-3">
                                                <i class="fa fa-circle text-primary" style="font-size: 0.5rem;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="mb-1 small">{{ $log->description }}</p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fa fa-user"></i> {{ $log->username }}
                                                    </small>
                                                    <small class="text-muted">
                                                        {{ $log->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<style>
    .dashboard-icon-circle {
        background-color: rgba(255, 255, 255, 0.25);
    }

    .card {
        transition: transform 0.2s;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    .table thead th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Card header title + action button: stack on narrow screens */
    @media (max-width: 575.98px) {
        .card-header .d-flex.justify-content-between {
            flex-wrap: wrap;
            gap: .5rem;
        }
    }
</style>
@endsection
