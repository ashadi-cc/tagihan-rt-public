@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Warga Pengontrak</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/tenant') }}">Daftar Warga Pengontrak</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detail Warga Pengontrak</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-home"></i></span>
            <span class="app-card-title">Detail Warga Pengontrak</span>
            <a href="{{ url('/admin/tenant/' . $tenant->id . '/edit') }}" class="btn btn-sm btn-warning">
                <i class="fa fa-edit"></i> Edit
            </a>
            <a href="{{ url('/admin/tenant') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <!-- Mobile: stacked label/value rows -->
                    <div class="d-md-none">
                        <div class="mb-3">
                            <div class="text-muted small">Nama Warga Pengontrak</div>
                            <div class="font-weight-bold">{{ $tenant->name }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Blok Rumah</div>
                            <div>{{ $tenant->unit_number ?? '-' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">No HP Pengontrak</div>
                            <div>{{ $tenant->contact_info ?? '-' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Alamat Asal Pengontrak</div>
                            <div>{!! nl2br(e($tenant->address ?? '-')) !!}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Keterangan</div>
                            <div>{!! nl2br(e($tenant->description ?? '-')) !!}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Foto Identitas</div>
                            @if($tenant->file_path && $tenant->file_driver)
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#photoModal">
                                    <i class="fa fa-image"></i> Lihat Foto
                                </button>
                            @else
                                <span class="text-muted">Tidak ada foto</span>
                            @endif
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Dibuat Pada</div>
                            <div>{{ $tenant->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Terakhir Diupdate</div>
                            <div>{{ $tenant->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>

                    <!-- Tablet/desktop: bordered table -->
                    <table class="table table-bordered d-none d-md-table">
                        <tr>
                            <th width="30%">Nama Warga Pengontrak</th>
                            <td>{{ $tenant->name }}</td>
                        </tr>
                        <tr>
                            <th>Blok Rumah</th>
                            <td>{{ $tenant->unit_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>No HP Pengontrak</th>
                            <td>{{ $tenant->contact_info ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Alamat Asal Pengontrak</th>
                            <td>{!! nl2br(e($tenant->address ?? '-')) !!}</td>
                        </tr>
                        <tr>
                            <th>Keterangan</th>
                            <td>{!! nl2br(e($tenant->description ?? '-')) !!}</td>
                        </tr>
                        <tr>
                            <th>Foto Identitas</th>
                            <td>
                                @if($tenant->file_path && $tenant->file_driver)
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#photoModal">
                                        <i class="fa fa-image"></i> Lihat Foto
                                    </button>
                                @else
                                    <span class="text-muted">Tidak ada foto</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Dibuat Pada</th>
                            <td>{{ $tenant->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Terakhir Diupdate</th>
                            <td>{{ $tenant->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($tenant->logs()->count() > 0)
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center mb-2" style="gap: .5rem;">
                        <span class="icon-chip icon-chip-info"><i class="fa fa-history"></i></span>
                        <h5 class="mb-0">Riwayat Aktivitas</h5>
                    </div>

                    <!-- Mobile: stacked cards -->
                    <div class="d-md-none border rounded mb-3">
                        <div class="list-group list-group-flush">
                            @foreach($tenant->logs()->orderBy('created_at', 'desc')->get() as $log)
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
                                @foreach($tenant->logs()->orderBy('created_at', 'desc')->get() as $log)
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

    @if($tenant->file_path && $tenant->file_driver)
    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">Foto Identitas - {{ $tenant->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div id="photoLoading" class="mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat foto...</p>
                    </div>
                    <img id="photoImage" src="" alt="Foto Identitas" class="img-fluid" style="display: none;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('scripts')
@if($tenant->file_path && $tenant->file_driver)
<script>
$(document).ready(function() {
    $('#photoModal').on('show.bs.modal', function (e) {
        $('#photoLoading').show();
        $('#photoImage').hide();
        
        var fileUrl = "{{ route('file.show', ['driver' => $tenant->file_driver]) }}?path={{ urlencode($tenant->file_path) }}";
        
        $('#photoImage').attr('src', fileUrl);
        $('#photoImage').on('load', function() {
            $('#photoLoading').hide();
            $('#photoImage').show();
        });
        
        $('#photoImage').on('error', function() {
            $('#photoLoading').html('<p class="text-danger">Gagal memuat foto</p>');
        });
    });
});
</script>
@endif
@endsection
