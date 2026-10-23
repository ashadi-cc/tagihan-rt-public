@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Inventaris</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Daftar Inventaris</li>
        </ol>
    </nav>
    
    <div class="alert alert-info mb-3">
        <i class="fa fa-info-circle"></i> 
        <strong>Informasi Inventaris:</strong> 
        Kelola dan catat barang-barang inventaris milik RT seperti peralatan, perlengkapan kegiatan, atau aset lainnya. 
        Setiap barang dapat dicatat dengan detail jumlah, kondisi, dan lokasi penyimpanan untuk memudahkan pengelolaan inventaris RT.
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: .75rem;">
                <form method="GET" action="{{ url('/admin/inventory') }}" class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                    <input type="text" name="q" class="form-control" placeholder="Cari nama barang atau lokasi" value="{{ request('q') }}" style="width: 220px;">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    @if(request('q'))
                        <a href="{{ url('/admin/inventory') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </form>
                <a href="{{ url('/admin/inventory/create') }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> Tambah Inventaris
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

            @if($inventories->count() > 0)
            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($inventories as $index => $inventory)
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <strong>{{ $inventory->item_name }}</strong>
                        <span class="text-muted small">Jml: {{ $inventory->quantity }}</span>
                    </div>
                    <div class="text-muted small mb-2">
                        {{ $inventory->stored_location ?? '-' }}
                    </div>
                    <div class="d-flex flex-wrap" style="gap: .4rem;">
                        <a href="{{ url('/admin/inventory/' . $inventory->id) }}" class="btn btn-sm btn-info">
                            <i class="fa fa-eye"></i> Lihat
                        </a>
                        <a href="{{ url('/admin/inventory/' . $inventory->id . '/edit') }}" class="btn btn-sm btn-warning">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-danger btn-delete-inventory" data-id="{{ $inventory->id }}" data-name="{{ $inventory->item_name }}">
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
                            <th width="40%">Nama Barang</th>
                            <th width="15%">Jumlah</th>
                            <th width="27%">Lokasi Penyimpanan</th>
                            <th width="13%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inventories as $index => $inventory)
                        <tr>
                            <td>{{ $inventories->firstItem() + $index }}</td>
                            <td>{{ $inventory->item_name }}</td>
                            <td>{{ $inventory->quantity }}</td>
                            <td>{{ $inventory->stored_location ?? '-' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ url('/admin/inventory/' . $inventory->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ url('/admin/inventory/' . $inventory->id . '/edit') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger btn-delete-inventory" data-id="{{ $inventory->id }}" data-name="{{ $inventory->item_name }}" title="Hapus">
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
                        Showing {{ $inventories->firstItem() }} to {{ $inventories->lastItem() }} of {{ $inventories->total() }} results
                    </div>
                    <div>
                        @if ($inventories->onFirstPage())
                            <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                        @else
                            <a href="{{ $inventories->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                        @endif

                        @if ($inventories->hasMorePages())
                            <a href="{{ $inventories->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
                        @else
                            <span class="btn btn-sm btn-light disabled">Next »</span>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                Tidak ada data inventaris.
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
                    <p>Apakah Anda yakin ingin menghapus inventaris ini?</p>
                    <p class="mb-0"><strong id="inventory-name"></strong></p>
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
    let inventoryIdToDelete = null;

    // Handle delete button clicks
    $('.btn-delete-inventory').on('click', function() {
        inventoryIdToDelete = $(this).data('id');
        const inventoryName = $(this).data('name');
        
        $('#inventory-name').text(inventoryName);
        $('#deleteModal').modal('show');
    });

    // Handle confirm delete button
    $('#confirmDelete').on('click', function() {
        if (inventoryIdToDelete) {
            fetch('/admin/inventory/' + inventoryIdToDelete, {
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
