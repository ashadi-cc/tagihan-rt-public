@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title"><i class="fa fa-archive"></i> Inventaris RT</h4>
        <p class="app-page-header-sub">Daftar barang inventaris milik RT</p>
    </div>

    <!-- Inventory List -->
    @if($inventories->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-archive fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Belum ada data inventaris</p>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($inventories as $inventory)
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm inventory-card">
                    <div class="card-body p-3">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 font-weight-bold">{{ $inventory->item_name }}</h6>
                                <p class="text-muted small mb-0">
                                    <i class="fa fa-cubes"></i> Jumlah: {{ $inventory->quantity }}
                                </p>
                            </div>
                            @if($inventory->condition)
                            <div class="ml-2">
                                @if($inventory->condition == 'Baik')
                                    <span class="badge badge-success">{{ 'kondisi ' . $inventory->condition }}</span>
                                @elseif($inventory->condition == 'Cukup')
                                    <span class="badge badge-warning">{{ 'kondisi ' . $inventory->condition }}</span>
                                @elseif($inventory->condition == 'Rusak')
                                    <span class="badge badge-danger">{{ 'kondisi ' . $inventory->condition }}</span>
                                @endif
                            </div>
                            @endif
                        </div>

                        <hr class="my-2">

                        @if($inventory->stored_location)
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Lokasi Penyimpanan</p>
                            <p class="mb-0"><i class="fa fa-map-marker text-primary"></i> {{ $inventory->stored_location }}</p>
                        </div>
                        @endif

                        @if($inventory->description)
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Deskripsi</p>
                            <div class="inventory-description">
                                {!! nl2br(e($inventory->description)) !!}
                            </div>
                        </div>
                        @endif

                        @if($inventory->photos && count($inventory->photos) > 0)
                        <div class="mt-3">
                            <p class="text-muted small mb-2">Foto Barang</p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($inventory->photos as $index => $photo)
                                <button type="button" class="btn btn-sm btn-primary mb-2 mr-2" data-toggle="modal" data-target="#photoModal{{ $inventory->id }}_{{ $index }}">
                                    <i class="fa fa-image"></i> Foto {{ $index + 1 }}
                                </button>
                                @endforeach
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
                    Menampilkan {{ $inventories->firstItem() ?? 0 }} - {{ $inventories->lastItem() ?? 0 }} dari {{ $inventories->total() }} barang
                </div>
                <div>
                    @if ($inventories->onFirstPage())
                        <span class="btn btn-sm btn-light disabled mr-2">« Sebelumnya</span>
                    @else
                        <a href="{{ $inventories->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Sebelumnya</a>
                    @endif

                    @if ($inventories->hasMorePages())
                        <a href="{{ $inventories->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Selanjutnya »</a>
                    @else
                        <span class="btn btn-sm btn-light disabled">Selanjutnya »</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Photo Modals -->
@if($inventories->isNotEmpty())
    @foreach($inventories as $inventory)
        @if($inventory->photos && count($inventory->photos) > 0)
            @foreach($inventory->photos as $index => $photo)
            <div class="modal fade" id="photoModal{{ $inventory->id }}_{{ $index }}" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel{{ $inventory->id }}_{{ $index }}" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="photoModalLabel{{ $inventory->id }}_{{ $index }}">
                                <i class="fa fa-image text-primary"></i> {{ $inventory->item_name }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center">
                            <div id="loading-spinner-{{ $inventory->id }}_{{ $index }}" class="mb-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2">Memuat foto...</p>
                            </div>
                            <img id="inventory-photo-{{ $inventory->id }}_{{ $index }}" src="" alt="Foto Barang" class="img-fluid" style="display: none; max-height: 70vh;">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    @endforeach
@endif
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Handle photo modals for inventory
        @if($inventories->isNotEmpty())
            @foreach($inventories as $inventory)
                @if($inventory->photos && count($inventory->photos) > 0)
                    @foreach($inventory->photos as $index => $photo)
                    $('#photoModal{{ $inventory->id }}_{{ $index }}').on('show.bs.modal', function (e) {
                        $('#loading-spinner-{{ $inventory->id }}_{{ $index }}').show();
                        $('#inventory-photo-{{ $inventory->id }}_{{ $index }}').hide();

                        var fileUrl = "{{ url('/inventaris/' . $inventory->id . '/photo/' . $index) }}";

                        $('#inventory-photo-{{ $inventory->id }}_{{ $index }}').attr('src', fileUrl);
                        $('#inventory-photo-{{ $inventory->id }}_{{ $index }}').on('load', function() {
                            $('#loading-spinner-{{ $inventory->id }}_{{ $index }}').hide();
                            $('#inventory-photo-{{ $inventory->id }}_{{ $index }}').show();
                        });
                    });
                    @endforeach
                @endif
            @endforeach
        @endif
    });
</script>

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

    h6 {
        font-size: 0.95rem;
    }

    .small {
        font-size: 0.8rem;
    }
}

/* Card hover effect */
.inventory-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border-left: 4px solid #4f46e5 !important;
}

.inventory-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.inventory-description {
    color: #495057;
    font-size: 0.9rem;
    line-height: 1.6;
    max-height: 150px;
    overflow-y: auto;
    padding: 8px;
    background-color: #f8f9fa;
    border-radius: 6px;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
@endsection
