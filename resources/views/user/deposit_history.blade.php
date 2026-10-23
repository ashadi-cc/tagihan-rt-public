@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title"><i class="fa fa-history"></i> Riwayat Deposit</h4>
        <p class="app-page-header-sub">Informasi deposit dan penggunaan deposit Anda</p>
    </div>

    <!-- Deposit Summary Cards -->
    <div class="row mb-4">
        <!-- Total Deposit -->
        <div class="col-md-4 mb-3">
            <div class="app-summary-card app-summary-card-primary">
                <span class="icon-chip icon-chip-lg icon-chip-primary"><i class="fa fa-money"></i></span>
                <div>
                    <div class="app-summary-label">Total Deposit</div>
                    <div class="app-summary-value">Rp {{ number_format($totalUserDeposit, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <!-- Total Usage -->
        <div class="col-md-4 mb-3">
            <div class="app-summary-card app-summary-card-warning">
                <span class="icon-chip icon-chip-lg icon-chip-warning"><i class="fa fa-shopping-cart"></i></span>
                <div>
                    <div class="app-summary-label">Total Penggunaan</div>
                    <div class="app-summary-value">Rp {{ number_format($totalDepositUsage, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <!-- Remaining Deposit -->
        <div class="col-md-4 mb-3">
            <div class="app-summary-card app-summary-card-success">
                <span class="icon-chip icon-chip-lg icon-chip-success"><i class="fa fa-check-circle"></i></span>
                <div>
                    <div class="app-summary-label">Sisa Deposit</div>
                    <div class="app-summary-value">Rp {{ number_format($remainingDeposit, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deposit Usage History -->
    <div class="mb-3">
        <h5 class="mb-3"><i class="fa fa-list"></i> Riwayat Penggunaan Deposit</h5>
    </div>

    @if($depositUsages->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Belum ada riwayat penggunaan deposit</p>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($depositUsages as $usage)
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <!-- Header with Status Badge -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 font-weight-bold">{{ $usage->payment_name }}</h6>
                                <p class="text-muted small mb-0">
                                    <i class="fa fa-calendar"></i> {{ $usage->created_at->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }}
                                </p>
                            </div>
                            <div class="ml-2">
                                @if($usage->status === 'verified')
                                    <span class="badge badge-success px-2 py-1">
                                        <i class="fa fa-check-circle"></i> Terverifikasi
                                    </span>
                                @elseif($usage->status === 'pending')
                                    <span class="badge badge-warning px-2 py-1">
                                        <i class="fa fa-clock-o"></i> Pending
                                    </span>
                                @elseif($usage->status === 'rejected')
                                    <span class="badge badge-danger px-2 py-1">
                                        <i class="fa fa-times-circle"></i> Ditolak
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Divider -->
                        <hr class="my-2">

                        <!-- Usage Details -->
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted small mb-1">Jumlah Digunakan</p>
                                <h5 class="mb-0 text-danger font-weight-bold">
                                    - Rp {{ number_format($usage->payment_amount, 0, ',', '.') }}
                                </h5>
                            </div>
                            <div class="col-6">
                                <p class="text-muted small mb-1">Tipe Transaksi</p>
                                <p class="mb-0">
                                    <i class="fa fa-credit-card"></i> Otomatis dari Deposit
                                </p>
                            </div>
                        </div>

                        <!-- Payment Date -->
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Tanggal Penggunaan</p>
                            <p class="mb-0">
                                <i class="fa fa-calendar"></i> {{ $usage->payment_date ? $usage->payment_date->locale('id')->isoFormat('D MMMM YYYY') : '-' }}
                            </p>
                        </div>

                        <!-- Period (if available) -->
                        @if($usage->formatted_period)
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Periode Pembayaran</p>
                            <p class="mb-0">
                                <i class="fa fa-calendar-o"></i> {{ $usage->formatted_period }}
                            </p>
                        </div>
                        @endif

                        <!-- Verified Info (only for verified payments) -->
                        @if($usage->status === 'verified')
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Diverifikasi Oleh</p>
                            <p class="mb-0">
                                <i class="fa fa-check-circle text-success"></i> Sistem Otomatis
                            </p>
                            @if($usage->verified_at)
                            <p class="text-muted small mb-0">
                                {{ $usage->verified_at->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }}
                            </p>
                            @endif
                        </div>
                        @endif

                        <!-- Additional Notes (if any) -->
                        @if($usage->notes)
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Catatan</p>
                            <div class="alert alert-info py-2 px-3 mb-0 small">
                                <i class="fa fa-info-circle"></i> {{ $usage->notes }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-muted small mb-2 mb-sm-0">
                    Menampilkan {{ $depositUsages->firstItem() ?? 0 }} - {{ $depositUsages->lastItem() ?? 0 }} dari {{ $depositUsages->total() }} data
                </div>
                <div>
                    @if ($depositUsages->onFirstPage())
                        <span class="btn btn-sm btn-light disabled mr-2">« Sebelumnya</span>
                    @else
                        <a href="{{ $depositUsages->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Sebelumnya</a>
                    @endif

                    @if ($depositUsages->hasMorePages())
                        <a href="{{ $depositUsages->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Selanjutnya »</a>
                    @else
                        <span class="btn btn-sm btn-light disabled">Selanjutnya »</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
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
    
    .badge {
        font-size: 0.7rem;
        white-space: nowrap;
    }
    
    h4 {
        font-size: 1.25rem;
    }
    
    h5 {
        font-size: 1.1rem;
    }
    
    h6 {
        font-size: 0.95rem;
    }
    
    .small {
        font-size: 0.8rem;
    }

    .fa-2x {
        font-size: 1.5rem !important;
    }
}

/* Card hover effect */
.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* Status badge styling */
.badge {
    font-weight: 600;
    letter-spacing: 0.3px;
}

/* Improve readability on mobile */
.font-weight-semibold {
    font-weight: 600;
}

/* Better spacing for mobile */
.card-body hr {
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

/* Opacity helper */
.opacity-75 {
    opacity: 0.75;
}

/* Summary card icons */
.bg-primary .fa,
.bg-warning .fa,
.bg-success .fa {
    opacity: 0.8;
}

/* Pagination styling for mobile */
.pagination {
    flex-wrap: wrap;
}

.page-link {
    min-width: 40px;
    text-align: center;
}

@media (max-width: 576px) {
    .pagination {
        font-size: 0.875rem;
    }
    
    .page-link {
        padding: 0.375rem 0.75rem;
    }
}
</style>
@endsection
