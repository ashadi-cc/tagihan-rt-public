@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title"><i class="fa fa-calendar"></i> Kegiatan RT</h4>
        <p class="app-page-header-sub">Daftar kegiatan RT yang telah dijadwalkan</p>
    </div>

    <!-- Persistent Events Section -->
    @if($persistentEvents->isNotEmpty())
    <div class="mb-4">
        <div class="d-flex align-items-center mb-3">
            <h5 class="mb-0"><i class="fa fa-star text-warning"></i> Kegiatan Tetap</h5>
            <span class="badge badge-warning ml-2">{{ $persistentEvents->count() }}</span>
        </div>
        
        <div class="row">
            @foreach($persistentEvents as $event)
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm event-card persistent-event-card">
                    <div class="card-body p-3">
                        <!-- Persistent Badge -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    <h6 class="mb-0 font-weight-bold">{{ $event->title }}</h6>
                                    <span class="badge badge-warning badge-sm ml-2">
                                        <i class="fa fa-star"></i> Tetap
                                    </span>
                                </div>
                                @if($event->location)
                                <p class="text-muted small mb-0">
                                    <i class="fa fa-map-marker"></i> {{ $event->location }}
                                </p>
                                @endif
                            </div>
                            <div class="ml-2">
                                <div class="date-badge persistent-date-badge">
                                    <div class="date-badge-month">{{ $event->event_date->locale('id')->format('M') }}</div>
                                    <div class="date-badge-day">{{ $event->event_date->format('d') }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <hr class="my-2">

                        <!-- Event Details -->
                        <div class="row">
                            <div class="col-12">
                                <p class="text-muted small mb-1">Tanggal</p>
                                <p class="mb-2">
                                    <i class="fa fa-calendar text-warning"></i> 
                                    <strong>{{ $event->event_date->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</strong>
                                </p>
                                @if($event->event_time)
                                <p class="text-muted small mb-1">Waktu</p>
                                <p class="mb-2">
                                    <i class="fa fa-clock-o text-warning"></i> 
                                    <strong>{{ $event->event_time }}</strong>
                                </p>
                                @endif
                            </div>
                        </div>

                        <!-- Description -->
                        @if($event->description)
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Deskripsi</p>
                            <div class="event-description">
                                {!! nl2br(e($event->description)) !!}
                            </div>
                        </div>
                        @endif

                        <!-- Attachment Files -->
                        @if($event->files && count($event->files) > 0)
                        <div class="mt-2">
                            @php $typeCounts = []; @endphp
                            @foreach($event->files as $index => $file)
                            @php
                                $ext = strtolower(pathinfo($file['original_name'] ?? $file['path'] ?? '', PATHINFO_EXTENSION));
                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                                $icon = $isImage ? 'fa-picture-o' : 'fa-file-pdf-o';
                                $typeLabel = $isImage ? 'Foto' : 'Berkas';
                                $typeCounts[$typeLabel] = ($typeCounts[$typeLabel] ?? 0) + 1;
                            @endphp
                            <button type="button" class="btn btn-sm btn-warning mr-1 mb-1" data-toggle="modal" data-target="#pdfModalPersistent{{ $event->id }}_{{ $index }}" title="{{ $file['original_name'] ?? '' }}">
                                <i class="fa {{ $icon }}"></i> {{ $typeLabel }} {{ $typeCounts[$typeLabel] }}
                            </button>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination for Persistent Events -->
        @if($persistentEvents->hasPages())
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-muted small mb-2 mb-sm-0">
                    Menampilkan {{ $persistentEvents->firstItem() ?? 0 }} - {{ $persistentEvents->lastItem() ?? 0 }} dari {{ $persistentEvents->total() }} data
                </div>
                <div>
                    @if ($persistentEvents->onFirstPage())
                        <span class="btn btn-sm btn-light disabled mr-2">« Sebelumnya</span>
                    @else
                        <a href="{{ $persistentEvents->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Sebelumnya</a>
                    @endif

                    @if ($persistentEvents->hasMorePages())
                        <a href="{{ $persistentEvents->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Selanjutnya »</a>
                    @else
                        <span class="btn btn-sm btn-light disabled">Selanjutnya »</span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Upcoming Events Section -->
    <div class="mb-3">
        <div class="d-flex align-items-center mb-3">
            <h5 class="mb-0"><i class="fa fa-calendar-o"></i> Kegiatan RT</h5>
            @if($events->total() > 0)
            <span class="badge badge-primary ml-2">{{ $events->total() }}</span>
            @endif
        </div>
    </div>

    <!-- Events List -->
    @if($events->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-calendar-o fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">Belum ada kegiatan yang dijadwalkan</p>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($events as $event)
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm event-card">
                    <div class="card-body p-3">
                        <!-- Header with Date Badge -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 font-weight-bold">{{ $event->title }}</h6>
                                @if($event->location)
                                <p class="text-muted small mb-0">
                                    <i class="fa fa-map-marker"></i> {{ $event->location }}
                                </p>
                                @endif
                            </div>
                            <div class="ml-2">
                                <div class="date-badge">
                                    <div class="date-badge-month">{{ $event->event_date->locale('id')->format('M') }}</div>
                                    <div class="date-badge-day">{{ $event->event_date->format('d') }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <hr class="my-2">

                        <!-- Event Details -->
                        <div class="row">
                            <div class="col-12">
                                <p class="text-muted small mb-1">Tanggal</p>
                                <p class="mb-2">
                                    <i class="fa fa-calendar text-primary"></i> 
                                    <strong>{{ $event->event_date->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</strong>
                                </p>
                                @if($event->event_time)
                                <p class="text-muted small mb-1">Waktu</p>
                                <p class="mb-2">
                                    <i class="fa fa-clock-o text-primary"></i> 
                                    <strong>{{ $event->event_time }}</strong>
                                </p>
                                @endif
                            </div>
                        </div>

                        <!-- Description -->
                        @if($event->description)
                        <div class="mt-2">
                            <p class="text-muted small mb-1">Deskripsi</p>
                            <div class="event-description">
                                {!! nl2br(e($event->description)) !!}
                            </div>
                        </div>
                        @endif

                        <!-- Attachment Files -->
                        @if($event->files && count($event->files) > 0)
                        <div class="mt-2">
                            @php $typeCounts = []; @endphp
                            @foreach($event->files as $index => $file)
                            @php
                                $ext = strtolower(pathinfo($file['original_name'] ?? $file['path'] ?? '', PATHINFO_EXTENSION));
                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                                $icon = $isImage ? 'fa-picture-o' : 'fa-file-pdf-o';
                                $typeLabel = $isImage ? 'Foto' : 'Berkas';
                                $typeCounts[$typeLabel] = ($typeCounts[$typeLabel] ?? 0) + 1;
                            @endphp
                            <button type="button" class="btn btn-sm btn-primary mr-1 mb-1" data-toggle="modal" data-target="#pdfModal{{ $event->id }}_{{ $index }}" title="{{ $file['original_name'] ?? '' }}">
                                <i class="fa {{ $icon }}"></i> {{ $typeLabel }} {{ $typeCounts[$typeLabel] }}
                            </button>
                            @endforeach
                        </div>
                        @endif

                        <!-- Days Until Event -->
                        @php
                            $daysUntil = now()->startOfDay()->diffInDays($event->event_date->startOfDay(), false);
                        @endphp
                        @if($daysUntil >= 0)
                        <div class="mt-3">
                            @if($daysUntil == 0)
                                <div class="alert alert-success py-2 px-3 mb-0 small">
                                    <i class="fa fa-bell"></i> <strong>Kegiatan hari ini!</strong>
                                </div>
                            @elseif($daysUntil == 1)
                                <div class="alert alert-warning py-2 px-3 mb-0 small">
                                    <i class="fa fa-clock-o"></i> <strong>Besok</strong>
                                </div>
                            @else
                                <div class="alert alert-info py-2 px-3 mb-0 small">
                                    <i class="fa fa-info-circle"></i> {{ $daysUntil }} hari lagi
                                </div>
                            @endif
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
                    Menampilkan {{ $events->firstItem() ?? 0 }} - {{ $events->lastItem() ?? 0 }} dari {{ $events->total() }} data
                </div>
                <div>
                    @if ($events->onFirstPage())
                        <span class="btn btn-sm btn-light disabled mr-2">« Sebelumnya</span>
                    @else
                        <a href="{{ $events->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Sebelumnya</a>
                    @endif

                    @if ($events->hasMorePages())
                        <a href="{{ $events->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Selanjutnya »</a>
                    @else
                        <span class="btn btn-sm btn-light disabled">Selanjutnya »</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<!-- File Modals for Persistent Events -->
@if($persistentEvents->isNotEmpty())
    @foreach($persistentEvents as $event)
        @if($event->files && count($event->files) > 0)
            @foreach($event->files as $index => $file)
            @php
                $modalExt = strtolower(pathinfo($file['original_name'] ?? $file['path'] ?? '', PATHINFO_EXTENSION));
                $modalIsImage = in_array($modalExt, ['jpg', 'jpeg', 'png', 'gif']);
            @endphp
            <div class="modal fade" id="pdfModalPersistent{{ $event->id }}_{{ $index }}" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel{{ $event->id }}_{{ $index }}" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="pdfModalLabel{{ $event->id }}_{{ $index }}">
                                <i class="fa {{ $modalIsImage ? 'fa-picture-o' : 'fa-file-pdf-o' }} text-warning"></i>
                                {{ $file['original_name'] ?? ('File PDF - ' . $event->title) }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="loading-spinner-persistent-{{ $event->id }}_{{ $index }}" class="text-center mb-3">
                                <div class="spinner-border text-warning" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2">Memuat file...</p>
                            </div>
                            <iframe id="pdf-viewer-persistent-{{ $event->id }}_{{ $index }}" src="" data-src="{{ route('user.event.file', ['eventId' => $event->id, 'fileIndex' => $index]) }}" style="width: 100%; height: 70vh; display: none;" frameborder="0"></iframe>
                        </div>
                        <div class="modal-footer">
                            <a href="{{ route('user.event.file', ['eventId' => $event->id, 'fileIndex' => $index]) }}" class="btn btn-warning" target="_blank">
                                <i class="fa fa-download"></i> Download
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

<!-- File Modals for Upcoming Events -->
@if($events->isNotEmpty())
    @foreach($events as $event)
        @if($event->files && count($event->files) > 0)
            @foreach($event->files as $index => $file)
            @php
                $modalExt = strtolower(pathinfo($file['original_name'] ?? $file['path'] ?? '', PATHINFO_EXTENSION));
                $modalIsImage = in_array($modalExt, ['jpg', 'jpeg', 'png', 'gif']);
            @endphp
            <div class="modal fade" id="pdfModal{{ $event->id }}_{{ $index }}" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel{{ $event->id }}_{{ $index }}" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="pdfModalLabel{{ $event->id }}_{{ $index }}">
                                <i class="fa {{ $modalIsImage ? 'fa-picture-o' : 'fa-file-pdf-o' }} text-primary"></i>
                                {{ $file['original_name'] ?? ('File PDF - ' . $event->title) }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="loading-spinner-{{ $event->id }}_{{ $index }}" class="text-center mb-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2">Memuat file...</p>
                            </div>
                            <iframe id="pdf-viewer-{{ $event->id }}_{{ $index }}" src="" data-src="{{ route('user.event.file', ['eventId' => $event->id, 'fileIndex' => $index]) }}" style="width: 100%; height: 70vh; display: none;" frameborder="0"></iframe>
                        </div>
                        <div class="modal-footer">
                            <a href="{{ route('user.event.file', ['eventId' => $event->id, 'fileIndex' => $index]) }}" class="btn btn-primary" target="_blank">
                                <i class="fa fa-download"></i> Download
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
        // Handle file modals for persistent events
        @if($persistentEvents->isNotEmpty())
            @foreach($persistentEvents as $event)
                @if($event->files && count($event->files) > 0)
                    @foreach($event->files as $index => $file)
                    $('#pdfModalPersistent{{ $event->id }}_{{ $index }}').on('show.bs.modal', function (e) {
                        $('#loading-spinner-persistent-{{ $event->id }}_{{ $index }}').show();
                        $('#pdf-viewer-persistent-{{ $event->id }}_{{ $index }}').hide();

                        var iframe = document.getElementById('pdf-viewer-persistent-{{ $event->id }}_{{ $index }}');
                        iframe.src = iframe.getAttribute('data-src');

                        $('#pdf-viewer-persistent-{{ $event->id }}_{{ $index }}').on('load', function() {
                            $('#loading-spinner-persistent-{{ $event->id }}_{{ $index }}').hide();
                            $('#pdf-viewer-persistent-{{ $event->id }}_{{ $index }}').show();
                        });
                    });
                    @endforeach
                @endif
            @endforeach
        @endif

        // Handle file modals for upcoming events
        @if($events->isNotEmpty())
            @foreach($events as $event)
                @if($event->files && count($event->files) > 0)
                    @foreach($event->files as $index => $file)
                    $('#pdfModal{{ $event->id }}_{{ $index }}').on('show.bs.modal', function (e) {
                        $('#loading-spinner-{{ $event->id }}_{{ $index }}').show();
                        $('#pdf-viewer-{{ $event->id }}_{{ $index }}').hide();

                        var iframe = document.getElementById('pdf-viewer-{{ $event->id }}_{{ $index }}');
                        iframe.src = iframe.getAttribute('data-src');

                        $('#pdf-viewer-{{ $event->id }}_{{ $index }}').on('load', function() {
                            $('#loading-spinner-{{ $event->id }}_{{ $index }}').hide();
                            $('#pdf-viewer-{{ $event->id }}_{{ $index }}').show();
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
.event-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.event-card:hover {
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

/* Persistent Event Badge - Gold/Warning color */
.persistent-date-badge {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

/* Persistent Event Card Styling */
.persistent-event-card {
    border-left: 4px solid #ffc107 !important;
    background: linear-gradient(to right, #fff9e6 0%, #ffffff 100%);
}

.persistent-event-card:hover {
    background: linear-gradient(to right, #fff5cc 0%, #ffffff 100%);
}

.badge-sm {
    font-size: 0.7rem;
    padding: 2px 6px;
}

/* Event Description */
.event-description {
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

/* Alert styling */
.alert {
    border-radius: 8px;
    border: none;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
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
.event-description::-webkit-scrollbar {
    width: 6px;
}

.event-description::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.event-description::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.event-description::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
@endsection
