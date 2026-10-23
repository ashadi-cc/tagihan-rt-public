@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Arsip Surat</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/surat-menyurat') }}">Daftar Surat</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detail Surat</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-envelope"></i></span>
            <span class="app-card-title">Detail Surat</span>
            <a href="{{ url('/admin/surat-menyurat/' . $letter->id . '/edit') }}" class="btn btn-sm btn-warning">
                <i class="fa fa-edit"></i> Edit
            </a>
            <a href="{{ url('/admin/surat-menyurat') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <!-- Mobile: stacked label/value rows -->
                    <div class="d-md-none">
                        <div class="mb-3">
                            <div class="text-muted small">Judul Surat</div>
                            <div class="font-weight-bold">{{ $letter->title }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Nomor Surat</div>
                            <div>{{ $letter->nomor_surat ?? '-' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Tanggal Surat</div>
                            <div>{{ $letter->tanggal_surat->format('d/m/Y') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Deskripsi</div>
                            <div>{!! nl2br(e($letter->description ?? '-')) !!}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Status</div>
                            <div>
                                @if($letter->status == 'published')
                                    <span class="badge badge-success">
                                        <i class="fa fa-check-circle"></i> Published
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="fa fa-file-o"></i> Draft
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Tampil di Halaman Publik</div>
                            <div>
                                @if($letter->show_on_public)
                                    <span class="badge badge-success">
                                        <i class="fa fa-eye"></i> Ya
                                    </span>
                                    <small class="text-muted d-block mt-1">Surat ini akan terlihat di menu "Arsip Surat" warga</small>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="fa fa-eye-slash"></i> Tidak
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if($letter->files && count($letter->files) > 0)
                        <div class="mb-3">
                            <div class="text-muted small mb-1">File PDF</div>
                            <div class="d-flex flex-wrap" style="gap: .4rem;">
                                @foreach($letter->files as $index => $file)
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#pdfModal{{ $index }}" title="{{ $file['original_name'] ?? 'File ' . ($index + 1) }}">
                                    <i class="fa fa-file-pdf-o"></i> PDF {{ $index + 1 }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @if($letter->external_links && count($letter->external_links) > 0)
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Link Eksternal</div>
                            @foreach($letter->external_links as $index => $link)
                            <div class="mb-1">
                                <a href="{{ $link }}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fa fa-external-link"></i> Link {{ $index + 1 }}
                                </a>
                                <small class="text-muted d-block">{{ $link }}</small>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        <div class="mb-3">
                            <div class="text-muted small">Dibuat Pada</div>
                            <div>{{ $letter->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Terakhir Diupdate</div>
                            <div>{{ $letter->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>

                    <!-- Tablet/desktop: bordered table -->
                    <table class="table table-bordered d-none d-md-table">
                        <tr>
                            <th width="30%">Judul Surat</th>
                            <td>{{ $letter->title }}</td>
                        </tr>
                        <tr>
                            <th>Nomor Surat</th>
                            <td>{{ $letter->nomor_surat ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Surat</th>
                            <td>{{ $letter->tanggal_surat->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td>{!! nl2br(e($letter->description ?? '-')) !!}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($letter->status == 'published')
                                    <span class="badge badge-success">
                                        <i class="fa fa-check-circle"></i> Published
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="fa fa-file-o"></i> Draft
                                    </span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Tampil di Halaman Publik</th>
                            <td>
                                @if($letter->show_on_public)
                                    <span class="badge badge-success">
                                        <i class="fa fa-eye"></i> Ya
                                    </span>
                                    <small class="text-muted d-block mt-1">Surat ini akan terlihat di menu "Arsip Surat" warga</small>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="fa fa-eye-slash"></i> Tidak
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @if($letter->files && count($letter->files) > 0)
                        <tr>
                            <th>File PDF</th>
                            <td>
                                @foreach($letter->files as $index => $file)
                                <button type="button" class="btn btn-sm btn-primary mr-1" data-toggle="modal" data-target="#pdfModal{{ $index }}" title="{{ $file['original_name'] ?? 'File ' . ($index + 1) }}">
                                    <i class="fa fa-file-pdf-o"></i> PDF {{ $index + 1 }}
                                </button>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @if($letter->external_links && count($letter->external_links) > 0)
                        <tr>
                            <th>Link Eksternal</th>
                            <td>
                                @foreach($letter->external_links as $index => $link)
                                <div class="mb-1">
                                    <a href="{{ $link }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fa fa-external-link"></i> Link {{ $index + 1 }}
                                    </a>
                                    <small class="text-muted d-block">{{ $link }}</small>
                                </div>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Dibuat Pada</th>
                            <td>{{ $letter->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Terakhir Diupdate</th>
                            <td>{{ $letter->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($letter->logs()->count() > 0)
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center mb-2" style="gap: .5rem;">
                        <span class="icon-chip icon-chip-info"><i class="fa fa-history"></i></span>
                        <h5 class="mb-0">Riwayat Aktivitas</h5>
                    </div>

                    <!-- Mobile: stacked cards -->
                    <div class="d-md-none border rounded mb-3">
                        <div class="list-group list-group-flush">
                            @foreach($letter->logs()->orderBy('created_at', 'desc')->get() as $log)
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
                                @foreach($letter->logs()->orderBy('created_at', 'desc')->get() as $log)
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

    @if($letter->files && count($letter->files) > 0)
    @foreach($letter->files as $index => $file)
    <!-- PDF Modal -->
    <div class="modal fade" id="pdfModal{{ $index }}" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel{{ $index }}" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel{{ $index }}">{{ $file['original_name'] ?? 'File PDF' }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="loading-spinner-{{ $index }}" class="text-center mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat file PDF...</p>
                    </div>
                    <iframe 
                        id="pdf-frame-{{ $index }}"
                        src="" 
                        data-src="{{ url('/admin/surat-menyurat/' . $letter->id . '/file/' . $index) }}" 
                        style="width: 100%; height: 80vh; border: none; display: none;"
                        onload="document.getElementById('loading-spinner-{{ $index }}').style.display='none'; this.style.display='block';">
                    </iframe>
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
    @if($letter->files && count($letter->files) > 0)
    @foreach($letter->files as $index => $file)
    $('#pdfModal{{ $index }}').on('show.bs.modal', function (e) {
        $('#loading-spinner-{{ $index }}').show();
        $('#pdf-frame-{{ $index }}').hide();
        
        var iframe = document.getElementById('pdf-frame-{{ $index }}');
        var fileUrl = iframe.getAttribute('data-src');
        
        iframe.src = fileUrl;
        
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
