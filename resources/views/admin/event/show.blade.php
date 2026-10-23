@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Kegiatan RT</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/event') }}">Daftar Kegiatan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detail Kegiatan</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-calendar"></i></span>
            <span class="app-card-title">Detail Kegiatan</span>
            <a href="{{ url('/admin/event/' . $event->id . '/edit') }}" class="btn btn-sm btn-warning">
                <i class="fa fa-edit"></i> Edit
            </a>
            <a href="{{ url('/admin/event') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <!-- Mobile: stacked label/value rows -->
                    <div class="d-md-none">
                        <div class="mb-3">
                            <div class="text-muted small">Judul Kegiatan</div>
                            <div class="font-weight-bold">{{ $event->title }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Tanggal</div>
                            <div>{{ $event->event_date->format('d/m/Y') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Waktu</div>
                            <div>{{ $event->event_time ?? '-' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Lokasi</div>
                            <div>{{ $event->location ?? '-' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Deskripsi</div>
                            <div>{!! nl2br(e($event->description ?? '-')) !!}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Kegiatan Tetap</div>
                            <div>
                                @if($event->persistent_event)
                                    <span class="badge badge-success">
                                        <i class="fa fa-check-circle"></i> Ya
                                    </span>
                                    <small class="text-muted d-block mt-1">Kegiatan ini akan selalu ditampilkan di halaman pengguna</small>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="fa fa-times-circle"></i> Tidak
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if($event->files && count($event->files) > 0)
                        <div class="mb-3">
                            <div class="text-muted small mb-1">File Lampiran</div>
                            @php $typeCountsMobile = []; @endphp
                            <div class="d-flex flex-wrap" style="gap: .4rem;">
                                @foreach($event->files as $index => $file)
                                @php
                                    $ext = strtolower(pathinfo($file['original_name'] ?? $file['path'] ?? '', PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                                    $icon = $isImage ? 'fa-picture-o' : 'fa-file-pdf-o';
                                    $typeLabel = $isImage ? 'Foto' : 'Berkas';
                                    $typeCountsMobile[$typeLabel] = ($typeCountsMobile[$typeLabel] ?? 0) + 1;
                                @endphp
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#pdfModal{{ $index }}" title="{{ $file['original_name'] ?? '' }}">
                                    <i class="fa {{ $icon }}"></i> {{ $typeLabel }} {{ $typeCountsMobile[$typeLabel] }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <div class="mb-3">
                            <div class="text-muted small">Dibuat Pada</div>
                            <div>{{ $event->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Terakhir Diupdate</div>
                            <div>{{ $event->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>

                    <!-- Tablet/desktop: bordered table -->
                    <table class="table table-bordered d-none d-md-table">
                        <tr>
                            <th width="30%">Judul Kegiatan</th>
                            <td>{{ $event->title }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal</th>
                            <td>{{ $event->event_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Waktu</th>
                            <td>{{ $event->event_time ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Lokasi</th>
                            <td>{{ $event->location ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td>{!! nl2br(e($event->description ?? '-')) !!}</td>
                        </tr>
                        <tr>
                            <th>Kegiatan Tetap</th>
                            <td>
                                @if($event->persistent_event)
                                    <span class="badge badge-success">
                                        <i class="fa fa-check-circle"></i> Ya
                                    </span>
                                    <small class="text-muted d-block mt-1">Kegiatan ini akan selalu ditampilkan di halaman pengguna</small>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="fa fa-times-circle"></i> Tidak
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @if($event->files && count($event->files) > 0)
                        <tr>
                            <th>File Lampiran</th>
                            <td>
                                @php $typeCounts = []; @endphp
                                @foreach($event->files as $index => $file)
                                @php
                                    $ext = strtolower(pathinfo($file['original_name'] ?? $file['path'] ?? '', PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                                    $icon = $isImage ? 'fa-picture-o' : 'fa-file-pdf-o';
                                    $typeLabel = $isImage ? 'Foto' : 'Berkas';
                                    $typeCounts[$typeLabel] = ($typeCounts[$typeLabel] ?? 0) + 1;
                                @endphp
                                <button type="button" class="btn btn-sm btn-primary mr-1 mb-1" data-toggle="modal" data-target="#pdfModal{{ $index }}" title="{{ $file['original_name'] ?? '' }}">
                                    <i class="fa {{ $icon }}"></i> {{ $typeLabel }} {{ $typeCounts[$typeLabel] }}
                                </button>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Dibuat Pada</th>
                            <td>{{ $event->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Terakhir Diupdate</th>
                            <td>{{ $event->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($event->logs()->count() > 0)
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center mb-2" style="gap: .5rem;">
                        <span class="icon-chip icon-chip-info"><i class="fa fa-history"></i></span>
                        <h5 class="mb-0">Riwayat Aktivitas</h5>
                    </div>

                    <!-- Mobile: stacked cards -->
                    <div class="d-md-none border rounded mb-3">
                        <div class="list-group list-group-flush">
                            @foreach($event->logs()->orderBy('created_at', 'desc')->get() as $log)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong class="small">{{ $log->username }}</strong>
                                    <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                                <div class="small">{!! nl2br(e($log->description)) !!}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tablet/desktop: table -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th width="20%">Tanggal</th>
                                    <th width="20%">User</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($event->logs()->orderBy('created_at', 'desc')->get() as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $log->username }}</td>
                                    <td>{!! nl2br(e($log->description)) !!}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($event->files && count($event->files) > 0)
    @foreach($event->files as $index => $file)
    <!-- File Modal -->
    <div class="modal fade" id="pdfModal{{ $index }}" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel{{ $index }}" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel{{ $index }}">{{ $file['original_name'] ?? 'File' }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="loading-spinner-{{ $index }}" class="text-center mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat file...</p>
                    </div>
                    <iframe
                        id="pdf-frame-{{ $index }}"
                        src=""
                        data-src="{{ url('/admin/event/' . $event->id . '/file/' . $index) }}"
                        style="width: 100%; height: 80vh; border: none; display: none;"
                        onload="document.getElementById('loading-spinner-{{ $index }}').style.display='none'; this.style.display='block';">
                    </iframe>
                </div>
                <div class="modal-footer">
                    <a href="{{ url('/admin/event/' . $event->id . '/file/' . $index) }}" class="btn btn-primary" target="_blank">
                        <i class="fa fa-download"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @endif
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    @if($event->files && count($event->files) > 0)
    @foreach($event->files as $index => $file)
    $('#pdfModal{{ $index }}').on('show.bs.modal', function (e) {
        $('#loading-spinner-{{ $index }}').show();
        $('#pdf-frame-{{ $index }}').hide();

        var iframe = document.getElementById('pdf-frame-{{ $index }}');
        iframe.src = iframe.getAttribute('data-src');

        $('#pdf-frame-{{ $index }}').on('load', function() {
            $('#loading-spinner-{{ $index }}').hide();
            $('#pdf-frame-{{ $index }}').show();
        });
    });
    @endforeach
    @endif
});
</script>
@endsection
