@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Warga Pengontrak</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Daftar Warga Pengontrak</li>
        </ol>
    </nav>
    
    <div class="alert alert-info mb-3">
        <i class="fa fa-info-circle"></i> 
        <strong>Informasi Warga Pengontrak:</strong> 
        Kelola data warga yang mengontrak atau menyewa rumah/unit di wilayah RT. 
        Data ini terpisah dari daftar warga tetap untuk memudahkan pengelolaan dan pencatatan warga yang bersifat sementara.
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: .75rem;">
                <form method="GET" action="{{ url('/admin/tenant') }}" class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                    <input type="text" name="q" class="form-control" placeholder="Cari nama, blok, atau no HP" value="{{ request('q') }}" style="width: 240px;">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    @if(request('q'))
                        <a href="{{ url('/admin/tenant') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </form>
                <a href="{{ url('/admin/tenant/create') }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> Tambah Warga Pengontrak
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

            @if($tenants->count() > 0)
            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($tenants as $index => $tenant)
                <div class="list-group-item px-0">
                    <strong>{{ $tenant->name }}</strong>
                    <div class="text-muted small mb-2">
                        {{ $tenant->unit_number ?? '-' }}
                        @if($tenant->contact_info) &middot; {{ $tenant->contact_info }} @endif
                    </div>
                    <div class="d-flex flex-wrap" style="gap: .4rem;">
                        <a href="{{ url('/admin/tenant/' . $tenant->id) }}" class="btn btn-sm btn-info">
                            <i class="fa fa-eye"></i> Lihat
                        </a>
                        <a href="{{ url('/admin/tenant/' . $tenant->id . '/edit') }}" class="btn btn-sm btn-warning">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-danger btn-delete-tenant" data-id="{{ $tenant->id }}" data-name="{{ $tenant->name }}">
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
                            <th width="35%">Nama</th>
                            <th width="18%">Blok Rumah</th>
                            <th width="24%">No HP</th>
                            <th width="13%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenants as $index => $tenant)
                        <tr>
                            <td>{{ $tenants->firstItem() + $index }}</td>
                            <td>{{ $tenant->name }}</td>
                            <td>{{ $tenant->unit_number ?? '-' }}</td>
                            <td>{{ $tenant->contact_info ?? '-' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ url('/admin/tenant/' . $tenant->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ url('/admin/tenant/' . $tenant->id . '/edit') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger btn-delete-tenant" data-id="{{ $tenant->id }}" data-name="{{ $tenant->name }}" title="Hapus">
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
                        Showing {{ $tenants->firstItem() }} to {{ $tenants->lastItem() }} of {{ $tenants->total() }} results
                    </div>
                    <div>
                        @if ($tenants->onFirstPage())
                            <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                        @else
                            <a href="{{ $tenants->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                        @endif

                        @if ($tenants->hasMorePages())
                            <a href="{{ $tenants->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
                        @else
                            <span class="btn btn-sm btn-light disabled">Next »</span>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                Tidak ada data warga pengontrak.
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
                    <p>Apakah Anda yakin ingin menghapus data warga pengontrak ini?</p>
                    <p class="mb-0"><strong id="tenant-name"></strong></p>
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
    let tenantIdToDelete = null;

    // Handle delete button clicks
    $('.btn-delete-tenant').on('click', function() {
        tenantIdToDelete = $(this).data('id');
        const tenantName = $(this).data('name');
        
        $('#tenant-name').text(tenantName);
        $('#deleteModal').modal('show');
    });

    // Handle confirm delete button
    $('#confirmDelete').on('click', function() {
        if (tenantIdToDelete) {
            fetch('/admin/tenant/' + tenantIdToDelete, {
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
