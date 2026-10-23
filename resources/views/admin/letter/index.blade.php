@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Arsip Surat</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Daftar Surat</li>
        </ol>
    </nav>
    
    <div class="alert alert-info mb-3">
        <i class="fa fa-info-circle"></i> 
        <strong>Informasi Arsip Surat:</strong> 
        Kelola dan arsipkan surat-surat resmi RT seperti surat edaran, pengumuman, undangan, atau surat-surat penting lainnya. 
        Setiap surat dapat dilengkapi dengan nomor surat, tanggal, file lampiran (PDF), dan tautan eksternal untuk kemudahan akses.
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: .75rem;">
                <form method="GET" action="{{ url('/admin/surat-menyurat') }}" class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                    <input type="text" name="q" class="form-control" placeholder="Cari surat" value="{{ request('q') }}" style="width: 200px;">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    @if(request('q'))
                        <a href="{{ url('/admin/surat-menyurat') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </form>
                <a href="{{ url('/admin/surat-menyurat/create') }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> Tambah Surat
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

            @if($letters->count() > 0)
            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($letters as $index => $letter)
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <strong>{{ $letter->title }}</strong>
                        @if($letter->status == 'published')
                            <span class="badge badge-success">Published</span>
                        @else
                            <span class="badge badge-secondary">Draft</span>
                        @endif
                    </div>
                    <div class="text-muted small mb-2">
                        {{ $letter->tanggal_surat->format('d/m/Y') }}
                        @if($letter->nomor_surat) &middot; {{ $letter->nomor_surat }} @endif
                        &middot;
                        @if($letter->show_on_public)
                            <i class="fa fa-eye text-success"></i> Publik
                        @else
                            <i class="fa fa-eye-slash"></i> Tersembunyi
                        @endif
                    </div>
                    <div class="d-flex flex-wrap" style="gap: .4rem;">
                        <a href="{{ url('/admin/surat-menyurat/' . $letter->id) }}" class="btn btn-sm btn-info">
                            <i class="fa fa-eye"></i> Lihat
                        </a>
                        <a href="{{ url('/admin/surat-menyurat/' . $letter->id . '/edit') }}" class="btn btn-sm btn-warning">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-danger btn-delete-letter" data-id="{{ $letter->id }}" data-title="{{ $letter->title }}">
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
                            <th width="12%">Tanggal Surat</th>
                            <th width="15%">Nomor Surat</th>
                            <th width="38%">Judul</th>
                            <th width="10%">Status</th>
                            <th width="8%">Publik</th>
                            <th width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($letters as $index => $letter)
                        <tr>
                            <td>{{ $letters->firstItem() + $index }}</td>
                            <td>{{ $letter->tanggal_surat->format('d/m/Y') }}</td>
                            <td>{{ $letter->nomor_surat ?? '-' }}</td>
                            <td>{{ $letter->title }}</td>
                            <td>
                                @if($letter->status == 'published')
                                    <span class="badge badge-success">Published</span>
                                @else
                                    <span class="badge badge-secondary">Draft</span>
                                @endif
                            </td>
                            <td>
                                @if($letter->show_on_public)
                                    <i class="fa fa-eye text-success" title="Tampil"></i>
                                @else
                                    <i class="fa fa-eye-slash text-muted" title="Sembunyikan"></i>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ url('/admin/surat-menyurat/' . $letter->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ url('/admin/surat-menyurat/' . $letter->id . '/edit') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger btn-delete-letter" data-id="{{ $letter->id }}" data-title="{{ $letter->title }}" title="Hapus">
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
                        Showing {{ $letters->firstItem() }} to {{ $letters->lastItem() }} of {{ $letters->total() }} results
                    </div>
                    <div>
                        @if ($letters->onFirstPage())
                            <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                        @else
                            <a href="{{ $letters->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                        @endif

                        @if ($letters->hasMorePages())
                            <a href="{{ $letters->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
                        @else
                            <span class="btn btn-sm btn-light disabled">Next »</span>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i> Belum ada data surat.
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
                    <p>Apakah Anda yakin ingin menghapus surat ini?</p>
                    <p class="mb-0"><strong id="letter-title"></strong></p>
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
    let letterIdToDelete = null;

    // Handle delete button clicks
    $('.btn-delete-letter').on('click', function() {
        letterIdToDelete = $(this).data('id');
        const letterTitle = $(this).data('title');

        $('#letter-title').text(letterTitle);
        $('#deleteModal').modal('show');
    });

    // Handle confirm delete button
    $('#confirmDelete').on('click', function() {
        if (letterIdToDelete) {
            fetch('/admin/surat-menyurat/' + letterIdToDelete, {
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
