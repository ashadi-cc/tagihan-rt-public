@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title"><i class="fa fa-envelope"></i> Arsip Surat</h4>
        <p class="app-page-header-sub">Koleksi surat resmi dan dokumen RT</p>
    </div>

    <!-- Letters List -->
    @if($letters->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-envelope-o fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Belum ada surat yang dipublikasikan</p>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($letters as $letter)
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm letter-card">
                    <div class="card-body p-3">
                        <!-- Header with Date Badge -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 font-weight-bold">{{ $letter->title }}</h6>
                                @if($letter->nomor_surat)
                                <p class="text-muted small mb-0">
                                    <i class="fa fa-file-text-o"></i> {{ $letter->nomor_surat }}
                                </p>
                                @endif
                            </div>
                            <div class="ml-2">
                                <div class="date-badge">
                                    <div class="date-badge-month">{{ $letter->tanggal_surat->locale('id')->format('M') }}</div>
                                    <div class="date-badge-day">{{ $letter->tanggal_surat->format('d') }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <hr class="my-2">

                        <!-- Letter Details -->
                        <div class="row">
                            <div class="col-12">
                                <p class="text-muted small mb-1">Tanggal Surat</p>
                                <p class="mb-2">
                                    <i class="fa fa-calendar text-primary"></i> 
                                    <strong>{{ $letter->tanggal_surat->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</strong>
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        @if($letter->description)
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Perihal</p>
                            <div class="letter-description">
                                {!! nl2br(e($letter->description)) !!}
                            </div>
                        </div>
                        @endif

                        <!-- Files and Links -->
                        @if(($letter->files && count($letter->files) > 0) || ($letter->external_links && count($letter->external_links) > 0))
                        <div class="mt-3">
                            <p class="text-muted small mb-2">Lampiran</p>
                            <div class="d-flex flex-wrap gap-2">
                                @if($letter->files && count($letter->files) > 0)
                                    @foreach($letter->files as $index => $file)
                                    <button type="button" class="btn btn-sm btn-primary mb-2 mr-2" data-toggle="modal" data-target="#pdfModal{{ $letter->id }}_{{ $index }}" title="{{ $file['original_name'] ?? 'File ' . ($index + 1) }}">
                                        <i class="fa fa-file-pdf-o"></i> PDF {{ $index + 1 }}
                                    </button>
                                    @endforeach
                                @endif
                                
                                @if($letter->external_links && count($letter->external_links) > 0)
                                    @foreach($letter->external_links as $index => $link)
                                    <a href="{{ $link }}" target="_blank" class="btn btn-sm btn-info mb-2 mr-2">
                                        <i class="fa fa-external-link"></i> Link {{ $index + 1 }}
                                    </a>
                                    @endforeach
                                @endif
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
                    Menampilkan {{ $letters->firstItem() ?? 0 }} - {{ $letters->lastItem() ?? 0 }} dari {{ $letters->total() }} surat
                </div>
                <div>
                    @if ($letters->onFirstPage())
                        <span class="btn btn-sm btn-light disabled mr-2">« Sebelumnya</span>
                    @else
                        <a href="{{ $letters->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Sebelumnya</a>
                    @endif

                    @if ($letters->hasMorePages())
                        <a href="{{ $letters->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Selanjutnya »</a>
                    @else
                        <span class="btn btn-sm btn-light disabled">Selanjutnya »</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<!-- PDF Modals -->
@if($letters->isNotEmpty())
    @foreach($letters as $letter)
        @if($letter->files && count($letter->files) > 0)
            @foreach($letter->files as $index => $file)
            <div class="modal fade" id="pdfModal{{ $letter->id }}_{{ $index }}" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel{{ $letter->id }}_{{ $index }}" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="pdfModalLabel{{ $letter->id }}_{{ $index }}">
                                <i class="fa fa-file-pdf-o text-primary"></i> {{ $file['original_name'] ?? 'File PDF' }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="loading-spinner-{{ $letter->id }}_{{ $index }}" class="text-center mb-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2">Memuat file PDF...</p>
                            </div>
                            <iframe id="pdf-viewer-{{ $letter->id }}_{{ $index }}" src="" style="width: 100%; height: 70vh; display: none;" frameborder="0"></iframe>
                        </div>
                        <div class="modal-footer">
                            <a id="download-pdf-{{ $letter->id }}_{{ $index }}" href="" class="btn btn-primary" target="_blank">
                                <i class="fa fa-download"></i> Download PDF
                            </a>
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
        // Handle PDF modals for letters
        @if($letters->isNotEmpty())
            @foreach($letters as $letter)
                @if($letter->files && count($letter->files) > 0)
                    @foreach($letter->files as $index => $file)
                    $('#pdfModal{{ $letter->id }}_{{ $index }}').on('show.bs.modal', function (e) {
                        $('#loading-spinner-{{ $letter->id }}_{{ $index }}').show();
                        $('#pdf-viewer-{{ $letter->id }}_{{ $index }}').hide();
                        
                        var fileUrl = "{{ url('/letter/' . $letter->id . '/file/' . $index) }}";
                        
                        $('#pdf-viewer-{{ $letter->id }}_{{ $index }}').attr('src', fileUrl);
                        $('#download-pdf-{{ $letter->id }}_{{ $index }}').attr('href', fileUrl);
                        
                        $('#pdf-viewer-{{ $letter->id }}_{{ $index }}').on('load', function() {
                            $('#loading-spinner-{{ $letter->id }}_{{ $index }}').hide();
                            $('#pdf-viewer-{{ $letter->id }}_{{ $index }}').show();
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
.letter-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border-left: 4px solid #4f46e5 !important;
}

.letter-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* Date Badge Styling */
.date-badge {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    border-radius: 8px;
    padding: 8px 12px;
    text-align: center;
    min-width: 60px;
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
}

.date-badge-month {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.date-badge-day {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    margin-top: 2px;
}

/* Letter Description */
.letter-description {
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
    .date-badge {
        min-width: 70px;
        padding: 10px 15px;
    }
    
    .date-badge-month {
        font-size: 0.75rem;
    }
    
    .date-badge-day {
        font-size: 1.75rem;
    }
}

/* Improve text legibility */
strong {
    font-weight: 600;
}

/* Custom scrollbar for description */
.letter-description::-webkit-scrollbar {
    width: 6px;
}

.letter-description::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.letter-description::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.letter-description::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Gap utility for buttons */
.gap-2 {
    gap: 0.5rem;
}
</style>
@endsection
