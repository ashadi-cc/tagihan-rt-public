@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title"><i class="fa fa-home"></i> Pengontrak Rumah</h4>
        <p class="app-page-header-sub">Daftar warga pengontrak di lingkungan RT</p>
    </div>

    <!-- Tenants List -->
    @if($tenants->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-home fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Belum ada data warga pengontrak</p>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($tenants as $tenant)
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm tenant-card">
                    <div class="card-body p-3">
                        <!-- Header with Unit Badge -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 font-weight-bold">{{ $tenant->name }}</h6>
                            </div>
                            <div class="ml-2">
                                <div class="unit-badge">
                                    <div class="unit-badge-label">Blok Rumah</div>
                                    <div class="unit-badge-number">{{ $tenant->unit_number }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-muted small mb-2 mb-sm-0">
                    Menampilkan {{ $tenants->firstItem() ?? 0 }} - {{ $tenants->lastItem() ?? 0 }} dari {{ $tenants->total() }} data
                </div>
                <div>
                    @if ($tenants->onFirstPage())
                        <span class="btn btn-sm btn-light disabled mr-2">« Sebelumnya</span>
                    @else
                        <a href="{{ $tenants->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Sebelumnya</a>
                    @endif

                    @if ($tenants->hasMorePages())
                        <a href="{{ $tenants->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Selanjutnya »</a>
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
.tenant-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.tenant-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* Unit Badge Styling */
.unit-badge {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    border-radius: 8px;
    padding: 8px 12px;
    text-align: center;
    min-width: 60px;
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
}

.unit-badge-label {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.unit-badge-number {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    margin-top: 2px;
}

/* Tenant Description */
.tenant-description {
    color: #495057;
    font-size: 0.9rem;
    line-height: 1.6;
    max-height: 150px;
    overflow-y: auto;
    padding: 8px;
    background-color: #f8f9fa;
    border-radius: 6px;
}

/* Better spacing for mobile */
.card-body hr {
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

/* Improve readability on mobile */
.font-weight-semibold {
    font-weight: 600;
}

/* Smooth shadows */
.shadow-sm {
    box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.075) !important;
}

/* Responsive adjustments */
@media (min-width: 768px) {
    .unit-badge {
        min-width: 70px;
        padding: 10px 15px;
    }
    
    .unit-badge-label {
        font-size: 0.75rem;
    }
    
    .unit-badge-number {
        font-size: 1.75rem;
    }
}

/* Improve text legibility */
strong {
    font-weight: 600;
}

/* Custom scrollbar for description */
.tenant-description::-webkit-scrollbar {
    width: 6px;
}

.tenant-description::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.tenant-description::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.tenant-description::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
@endsection
