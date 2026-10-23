@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Inventaris</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/inventory') }}">Daftar Inventaris</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detail Inventaris</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-archive"></i></span>
            <span class="app-card-title">Detail Inventaris</span>
            <a href="{{ url('/admin/inventory/' . $inventory->id . '/edit') }}" class="btn btn-sm btn-warning">
                <i class="fa fa-edit"></i> Edit
            </a>
            <a href="{{ url('/admin/inventory') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <!-- Mobile: stacked label/value rows -->
                    <div class="d-md-none">
                        <div class="mb-3">
                            <div class="text-muted small">Nama Barang</div>
                            <div class="font-weight-bold">{{ $inventory->item_name }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Jumlah</div>
                            <div>{{ $inventory->quantity }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Kondisi</div>
                            <div>
                                @if($inventory->condition == 'Baik')
                                    <span class="badge badge-success">{{ $inventory->condition }}</span>
                                @elseif($inventory->condition == 'Cukup')
                                    <span class="badge badge-warning">{{ $inventory->condition }}</span>
                                @elseif($inventory->condition == 'Rusak')
                                    <span class="badge badge-danger">{{ $inventory->condition }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Lokasi Penyimpanan</div>
                            <div>{{ $inventory->stored_location ?? '-' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Deskripsi</div>
                            <div>{{ $inventory->description ?? '-' }}</div>
                        </div>
                        @if($inventory->photos && count($inventory->photos) > 0)
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Foto Barang</div>
                            <div class="d-flex flex-wrap" style="gap: .4rem;">
                                @foreach($inventory->photos as $index => $photo)
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#photoModal{{ $index }}">
                                    <i class="fa fa-image"></i> Foto {{ $index + 1 }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <div class="mb-3">
                            <div class="text-muted small">Dibuat Pada</div>
                            <div>{{ $inventory->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Terakhir Diupdate</div>
                            <div>{{ $inventory->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>

                    <!-- Tablet/desktop: bordered table -->
                    <table class="table table-bordered d-none d-md-table">
                        <tr>
                            <th width="30%">Nama Barang</th>
                            <td>{{ $inventory->item_name }}</td>
                        </tr>
                        <tr>
                            <th>Jumlah</th>
                            <td>{{ $inventory->quantity }}</td>
                        </tr>
                        <tr>
                            <th>Kondisi</th>
                            <td>
                                @if($inventory->condition == 'Baik')
                                    <span class="badge badge-success">{{ $inventory->condition }}</span>
                                @elseif($inventory->condition == 'Cukup')
                                    <span class="badge badge-warning">{{ $inventory->condition }}</span>
                                @elseif($inventory->condition == 'Rusak')
                                    <span class="badge badge-danger">{{ $inventory->condition }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Lokasi Penyimpanan</th>
                            <td>{{ $inventory->stored_location ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td>{{ $inventory->description ?? '-' }}</td>
                        </tr>
                        @if($inventory->photos && count($inventory->photos) > 0)
                        <tr>
                            <th>Foto Barang</th>
                            <td>
                                @foreach($inventory->photos as $index => $photo)
                                <button type="button" class="btn btn-sm btn-primary mr-1" data-toggle="modal" data-target="#photoModal{{ $index }}">
                                    <i class="fa fa-image"></i> Foto {{ $index + 1 }}
                                </button>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Dibuat Pada</th>
                            <td>{{ $inventory->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Terakhir Diupdate</th>
                            <td>{{ $inventory->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($inventory->logs()->count() > 0)
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center mb-2" style="gap: .5rem;">
                        <span class="icon-chip icon-chip-info"><i class="fa fa-history"></i></span>
                        <h5 class="mb-0">Riwayat Aktivitas</h5>
                    </div>

                    <!-- Mobile: stacked cards -->
                    <div class="d-md-none border rounded mb-3">
                        <div class="list-group list-group-flush">
                            @foreach($inventory->logs()->orderBy('created_at', 'desc')->get() as $log)
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
                                @foreach($inventory->logs()->orderBy('created_at', 'desc')->get() as $log)
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

    @if($inventory->photos && count($inventory->photos) > 0)
    @foreach($inventory->photos as $index => $photo)
    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal{{ $index }}" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel{{ $index }}" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel{{ $index }}">Foto Barang - {{ $inventory->item_name }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div id="loading-spinner-{{ $index }}" class="mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat foto...</p>
                    </div>
                    <img id="inventory-photo-{{ $index }}" src="" alt="Foto Barang" class="img-fluid" style="display: none; max-height: 70vh;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @endif
@endsection

@section('scripts')
    @if($inventory->photos && count($inventory->photos) > 0)
    <script>
        $(document).ready(function() {
            @foreach($inventory->photos as $index => $photo)
            $('#photoModal{{ $index }}').on('show.bs.modal', function (e) {
                $('#loading-spinner-{{ $index }}').show();
                $('#inventory-photo-{{ $index }}').hide();

                var fileUrl = "{{ route('file.show', ['driver' => $photo['driver']]) }}?path={{ urlencode($photo['path']) }}";

                $('#inventory-photo-{{ $index }}').attr('src', fileUrl);
                $('#inventory-photo-{{ $index }}').on('load', function() {
                    $('#loading-spinner-{{ $index }}').hide();
                    $('#inventory-photo-{{ $index }}').show();
                });
            });
            @endforeach
        });
    </script>
    @endif
@endsection
