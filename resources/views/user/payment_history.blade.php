@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title"><i class="fa fa-history"></i> Riwayat Pembayaran</h4>
        <p class="app-page-header-sub">Daftar pembayaran yang telah Anda ajukan</p>
    </div>

    <!-- Payment History List -->
    @if($pembayarans->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Belum ada riwayat pembayaran</p>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($pembayarans as $pembayaran)
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <!-- Header with Status Badge -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 font-weight-bold">{{ $pembayaran->payment_name }}</h6>
                                <p class="text-muted small mb-0">
                                    <i class="fa fa-calendar"></i> {{ $pembayaran->created_at->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }}
                                </p>
                            </div>
                            <div class="ml-2">
                                @if($pembayaran->status === 'verified')
                                    <span class="badge badge-success px-2 py-1">
                                        <i class="fa fa-check-circle"></i> Terverifikasi
                                    </span>
                                @elseif($pembayaran->status === 'pending')
                                    <span class="badge badge-warning px-2 py-1">
                                        <i class="fa fa-clock-o"></i> Pending
                                    </span>
                                @elseif($pembayaran->status === 'rejected')
                                    <span class="badge badge-danger px-2 py-1">
                                        <i class="fa fa-times-circle"></i> Ditolak
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Divider -->
                        <hr class="my-2">

                        <!-- Payment Details -->
                        <div class="row">
                            <div class="col-6">
                                <p class="text-muted small mb-1">Jumlah Pembayaran</p>
                                <h5 class="mb-0 text-primary font-weight-bold">
                                    Rp {{ number_format($pembayaran->getLabelPaymentAmount(true), 0, ',', '.') }}
                                </h5>
                            </div>
                            <div class="col-6">
                                <p class="text-muted small mb-1">Metode Pembayaran</p>
                                <p class="mb-0 font-weight-semibold">
                                    {{ $pembayaran->payment_method === 'bank_account' ? 'Transfer Bank' : ($pembayaran->payment_method ?? '-') }}
                                </p>
                            </div>
                        </div>

                        <!-- Payment Type -->
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Jenis Pembayaran</p>
                            <p class="mb-0">
                                @if($pembayaran->payment_type === 'user_deposit')
                                    <i class="fa fa-money text-success"></i> Deposit
                                @else
                                    <i class="fa fa-file-text-o text-info"></i> Tagihan Iuran
                                @endif
                            </p>
                        </div>

                        <!-- Payment Date -->
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Tanggal Pembayaran</p>
                            <p class="mb-0">
                                <i class="fa fa-calendar"></i> {{ $pembayaran->payment_date ? $pembayaran->payment_date->locale('id')->isoFormat('D MMMM YYYY') : '-' }}
                            </p>
                        </div>

                        <!-- Tagihan Details (expandable) -->
                        @if($pembayaran->tagihans && $pembayaran->tagihans->count() > 0)
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Detail Tagihan</p>
                            <button class="btn btn-sm btn-outline-info btn-block text-left" 
                                    type="button" 
                                    data-toggle="collapse" 
                                    data-target="#tagihan-{{ $pembayaran->id }}" 
                                    aria-expanded="false">
                                <i class="fa fa-plus-circle toggle-icon"></i> 
                                Lihat {{ $pembayaran->tagihans->count() }} Tagihan
                            </button>
                            <div class="collapse mt-2" id="tagihan-{{ $pembayaran->id }}">
                                <div class="card card-body p-2 bg-light">
                                    @foreach($pembayaran->tagihans as $tagihan)
                                    <div class="d-flex justify-content-between align-items-center py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                                        <div class="flex-grow-1">
                                            <small class="font-weight-semibold">{{ $tagihan->billing_name }} ({{ $tagihan->period }})</small>
                                        </div>
                                        <div class="ml-2">
                                            <small class="text-success font-weight-bold">
                                                Rp {{ number_format($tagihan->pivot->amount, 0, ',', '.') }}
                                            </small>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Verified Info (only for verified payments) -->
                        @if($pembayaran->status === 'verified')
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Diverifikasi Oleh</p>
                            <p class="mb-0">
                                <i class="fa fa-check-circle text-success"></i> Admin
                            </p>
                            @if($pembayaran->verified_at)
                            <p class="text-muted small mb-0">
                                {{ $pembayaran->verified_at->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }}
                            </p>
                            @endif
                        </div>
                        @endif

                        <!-- Notes (if rejected) -->
                        @if($pembayaran->status === 'rejected' && $pembayaran->notes)
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Alasan Penolakan</p>
                            <div class="alert alert-danger py-2 px-3 mb-0 small">
                                <i class="fa fa-info-circle"></i> {{ $pembayaran->notes }}
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
                    Menampilkan {{ $pembayarans->firstItem() ?? 0 }} - {{ $pembayarans->lastItem() ?? 0 }} dari {{ $pembayarans->total() }} data
                </div>
                <div>
                    @if ($pembayarans->onFirstPage())
                        <span class="btn btn-sm btn-light disabled mr-2">« Sebelumnya</span>
                    @else
                        <a href="{{ $pembayarans->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Sebelumnya</a>
                    @endif

                    @if ($pembayarans->hasMorePages())
                        <a href="{{ $pembayarans->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Selanjutnya »</a>
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

/* Tagihan collapse button styling */
.btn-outline-info {
    transition: all 0.3s ease;
}

.btn-outline-info:hover {
    background-color: #17a2b8;
    color: white;
}

.toggle-icon {
    transition: transform 0.3s ease;
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle collapse toggle icon change
    const collapseElements = document.querySelectorAll('[data-toggle="collapse"]');
    collapseElements.forEach(function(element) {
        const targetId = element.getAttribute('data-target');
        const target = document.querySelector(targetId);
        
        if (target) {
            target.addEventListener('show.bs.collapse', function() {
                const icon = element.querySelector('.toggle-icon');
                if (icon) {
                    icon.classList.remove('fa-plus-circle');
                    icon.classList.add('fa-minus-circle');
                }
            });
            
            target.addEventListener('hide.bs.collapse', function() {
                const icon = element.querySelector('.toggle-icon');
                if (icon) {
                    icon.classList.remove('fa-minus-circle');
                    icon.classList.add('fa-plus-circle');
                }
            });
        }
    });
});
</script>

@endsection
