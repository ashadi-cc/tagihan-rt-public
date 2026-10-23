@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title"><i class="fa fa-line-chart"></i> Laporan Keuangan Dawis</h4>
        <p class="app-page-header-sub">Kondisi keuangan Dawis secara keseluruhan</p>
    </div>

    <!-- Financial Summary Cards -->
    <div class="row mb-4">
        <!-- Total Income -->
        <div class="col-md-4 mb-3">
            <div class="app-summary-card app-summary-card-success">
                <span class="icon-chip icon-chip-lg icon-chip-success"><i class="fa fa-arrow-down"></i></span>
                <div>
                    <div class="app-summary-label">Total Pemasukan</div>
                    <div class="app-summary-value">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <!-- Total Expense -->
        <div class="col-md-4 mb-3">
            <div class="app-summary-card app-summary-card-danger">
                <span class="icon-chip icon-chip-lg icon-chip-danger"><i class="fa fa-arrow-up"></i></span>
                <div>
                    <div class="app-summary-label">Total Pengeluaran</div>
                    <div class="app-summary-value">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <!-- Balance -->
        <div class="col-md-4 mb-3">
            <div class="app-summary-card {{ $balance >= 0 ? 'app-summary-card-primary' : 'app-summary-card-warning' }}">
                <span class="icon-chip icon-chip-lg {{ $balance >= 0 ? 'icon-chip-primary' : 'icon-chip-warning' }}"><i class="fa fa-balance-scale"></i></span>
                <div>
                    <div class="app-summary-label">Saldo Kas Dawis</div>
                    <div class="app-summary-value">Rp {{ number_format($balance, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions History -->
    <div class="mb-3">
        <h5 class="mb-3"><i class="fa fa-list"></i> Riwayat Transaksi</h5>
    </div>

    @if($transactions->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Belum ada transaksi keuangan</p>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($transactions as $transaction)
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <!-- Header with Type Badge -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 font-weight-bold">{{ $transaction->name }}</h6>
                                <p class="text-muted small mb-0">
                                    <i class="fa fa-calendar"></i> {{ $transaction->payment_date ? \Carbon\Carbon::parse($transaction->payment_date)->locale('id')->isoFormat('D MMMM YYYY') : '-' }}
                                </p>
                            </div>
                            <div class="ml-2">
                                @if($transaction->credit > 0)
                                    <span class="badge badge-success px-2 py-1">
                                        <i class="fa fa-arrow-down"></i> Pemasukan
                                    </span>
                                @else
                                    <span class="badge badge-danger px-2 py-1">
                                        <i class="fa fa-arrow-up"></i> Pengeluaran
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Divider -->
                        <hr class="my-2">

                        <!-- Transaction Details -->
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted small mb-1">Jumlah</p>
                                @if($transaction->credit > 0)
                                    <h5 class="mb-0 text-success font-weight-bold">
                                        + Rp {{ number_format($transaction->credit, 0, ',', '.') }}
                                    </h5>
                                @else
                                    <h5 class="mb-0 text-danger font-weight-bold">
                                        - Rp {{ number_format($transaction->debit, 0, ',', '.') }}
                                    </h5>
                                @endif
                            </div>
                        </div>

                        <!-- Description -->
                        @if($transaction->description)
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Keterangan</p>
                            <div class="alert alert-info py-2 px-3 mb-0 small">
                                <i class="fa fa-info-circle"></i> {{ $transaction->description }}
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
                    Menampilkan {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} dari {{ $transactions->total() }} data
                </div>
                <div>
                    @if ($transactions->onFirstPage())
                        <span class="btn btn-sm btn-light disabled mr-2">« Sebelumnya</span>
                    @else
                        <a href="{{ $transactions->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Sebelumnya</a>
                    @endif

                    @if ($transactions->hasMorePages())
                        <a href="{{ $transactions->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Selanjutnya »</a>
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
.bg-success .fa,
.bg-danger .fa {
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
