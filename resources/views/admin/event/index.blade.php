@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Kegiatan RT</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Daftar Kegiatan</li>
        </ol>
    </nav>
    
    <div class="alert alert-info mb-3">
        <i class="fa fa-info-circle"></i> 
        <strong>Informasi Kegiatan RT:</strong> 
        Kelola dan catat kegiatan-kegiatan RT seperti rapat warga, gotong royong, perayaan, atau acara lainnya. 
        Setiap kegiatan dapat dicatat dengan tanggal, lokasi, dan detail lainnya untuk dokumentasi dan koordinasi kegiatan RT.
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: .75rem;">
                <form method="GET" action="{{ url('/admin/event') }}" class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                    <input type="text" name="q" class="form-control" placeholder="Cari kegiatan" value="{{ request('q') }}" style="width: 200px;">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    @if(request('q'))
                        <a href="{{ url('/admin/event') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </form>
                <a href="{{ url('/admin/event/create') }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> Tambah Kegiatan
                </a>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif

            @if($events->count() > 0)
            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($events as $index => $event)
                <div class="list-group-item px-0">
                    <strong>{{ $event->title }}</strong>
                    <div class="text-muted small mb-2">
                        <i class="fa fa-calendar"></i> {{ $event->event_date->format('d/m/Y') }}
                        @if($event->event_time) &middot; {{ $event->event_time }} @endif
                        @if($event->location) &middot; <i class="fa fa-map-marker"></i> {{ $event->location }} @endif
                    </div>
                    <div class="d-flex flex-wrap" style="gap: .4rem;">
                        <a href="{{ url('/admin/event/' . $event->id) }}" class="btn btn-sm btn-info">
                            <i class="fa fa-eye"></i> Lihat
                        </a>
                        <a href="{{ url('/admin/event/' . $event->id . '/edit') }}" class="btn btn-sm btn-warning">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-danger btn-delete-event" data-id="{{ $event->id }}" data-title="{{ $event->title }}">
                            <i class="fa fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="12%">Tanggal</th>
                            <th width="10%">Waktu</th>
                            <th width="33%">Judul Kegiatan</th>
                            <th width="22%">Lokasi</th>
                            <th width="13%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $index => $event)
                        <tr>
                            <td>{{ $events->firstItem() + $index }}</td>
                            <td>{{ $event->event_date->format('d/m/Y') }}</td>
                            <td>{{ $event->event_time ?? '-' }}</td>
                            <td>{{ $event->title }}</td>
                            <td>{{ $event->location ?? '-' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ url('/admin/event/' . $event->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ url('/admin/event/' . $event->id . '/edit') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger btn-delete-event" data-id="{{ $event->id }}" data-title="{{ $event->title }}" title="Hapus">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="text-muted small mb-2 mb-sm-0">
                        Showing {{ $events->firstItem() }} to {{ $events->lastItem() }} of {{ $events->total() }} results
                    </div>
                    <div>
                        @if ($events->onFirstPage())
                            <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                        @else
                            <a href="{{ $events->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                        @endif

                        @if ($events->hasMorePages())
                            <a href="{{ $events->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
                        @else
                            <span class="btn btn-sm btn-light disabled">Next »</span>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                Tidak ada data kegiatan.
            </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fa fa-exclamation-triangle"></i> Konfirmasi Hapus
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data kegiatan ini?</p>
                    <p class="mb-0"><strong id="event-title"></strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fa fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    let eventIdToDelete = null;

    // Handle delete button clicks
    $('.btn-delete-event').on('click', function() {
        eventIdToDelete = $(this).data('id');
        const eventTitle = $(this).data('title');
        
        $('#event-title').text(eventTitle);
        $('#deleteModal').modal('show');
    });

    // Handle confirm delete button
    $('#confirmDelete').on('click', function() {
        if (eventIdToDelete) {
            fetch('/admin/event/' + eventIdToDelete, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                $('#deleteModal').modal('hide');
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                $('#deleteModal').modal('hide');
                console.error('Error:', error);
            });
        }
    });
</script>
@endsection
