@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Daftar Warga</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Daftar Warga</li>
        </ol>
    </nav>

    <div class="alert alert-info mb-3">
        <i class="fa fa-info-circle"></i>
        <strong>Informasi Daftar Warga:</strong>
        <ul class="mb-0 mt-2">
            <li><strong>Daftar Warga:</strong> Tampilan seluruh data warga RT yang terdaftar beserta informasi kontak dan status</li>
            <li><strong>Tambah Warga:</strong> Form untuk menambahkan data warga baru satu per satu secara manual</li>
            <li><strong>Upload:</strong> Fitur untuk menambahkan banyak data warga sekaligus menggunakan file Excel</li>
        </ul>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: .75rem;">
                <div class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                    <input type="text" id="search-input" class="form-control" style="flex: 1 1 220px; width: auto;" placeholder="Cari blok atau nama">
                </div>
                <div class="d-flex flex-wrap" style="gap: .5rem;">
                    <a href="{{ url('/admin/master/warga/create') }}" class="btn btn-success">
                        <i class="fa fa-plus"></i> Tambah Warga
                    </a>
                    <a href="{{ url('/admin/master/warga/upload') }}" class="btn btn-primary">
                        <i class="fa fa-upload"></i> Upload
                    </a>
                    <a href="{{ $baseUrl }}/download" class="btn btn-outline-secondary">
                        <i class="fa fa-download"></i> Download
                    </a>
                </div>
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

            <div id="loading-indicator" class="text-center text-muted py-4">
                <i class="fa fa-spinner fa-spin"></i> Memuat data...
            </div>
            <div id="empty-indicator" class="alert alert-info" style="display: none;">
                Tidak ada data warga.
            </div>

            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div id="mobile-list" class="d-md-none list-group list-group-flush mb-3"></div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th width="4%">No</th>
                            <th width="9%">Username</th>
                            <th width="7%">Blok</th>
                            <th width="20%">Nama</th>
                            <th width="12%">Telepon</th>
                            <th width="14%">Tagihan</th>
                            <th width="10%">WA Register?</th>
                            <th width="8%">Admin?</th>
                            <th width="14%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="desktop-tbody"></tbody>
                </table>
            </div>
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
                    <p>Apakah Anda yakin ingin menghapus warga ini?</p>
                    <p class="mb-0"><strong id="warga-name"></strong></p>
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
    var baseUrl = @json(rtrim($baseUrl, '/'));
    var wargaIdToDelete = null;
    var searchDebounce = null;

    function escapeHtml(str) {
        return $('<div>').text(str || '').html();
    }

    function loadData(query) {
        $('#loading-indicator').show();
        $('#empty-indicator').hide();
        $('#mobile-list').empty();
        $('#desktop-tbody').empty();

        $.get(baseUrl + '/get', { q: query || '' })
            .done(function(data) {
                $('#loading-indicator').hide();

                if (!data || data.length === 0) {
                    $('#empty-indicator').show();
                    return;
                }

                data.forEach(function(item, index) {
                    var username = escapeHtml(item.username);
                    var blok = escapeHtml(item.blok);
                    var name = escapeHtml(item.name);
                    var phone = escapeHtml(item.phone_number || '-');

                    var card = '' +
                        '<div class="list-group-item px-0">' +
                            '<div class="d-flex justify-content-between align-items-start mb-1">' +
                                '<div>' +
                                    '<strong>' + blok + '</strong> &middot; ' + name +
                                    '<div class="text-muted small">' + username + '</div>' +
                                '</div>' +
                                item.is_admin +
                            '</div>' +
                            '<div class="text-muted small mb-1">' + phone + '</div>' +
                            '<div class="mb-2">' + item.tunggakan + '</div>' +
                            '<div class="mb-2">' + item.wa_register + '</div>' +
                            '<div class="d-flex flex-wrap" style="gap: .4rem;">' +
                                '<a href="' + baseUrl + '/view/' + item.id + '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> Lihat</a>' +
                                '<a href="' + baseUrl + '/edit/' + item.id + '" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i> Edit</a>' +
                                '<button type="button" class="btn btn-sm btn-danger btn-delete-warga" data-id="' + item.id + '" data-name="' + name + '"><i class="fa fa-trash"></i> Hapus</button>' +
                            '</div>' +
                        '</div>';
                    $('#mobile-list').append(card);

                    var row = '' +
                        '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + username + '</td>' +
                            '<td>' + blok + '</td>' +
                            '<td>' + name + '</td>' +
                            '<td>' + phone + '</td>' +
                            '<td>' + item.tunggakan + '</td>' +
                            '<td>' + item.wa_register + '</td>' +
                            '<td>' + item.is_admin + '</td>' +
                            '<td class="text-nowrap">' +
                                '<a href="' + baseUrl + '/view/' + item.id + '" class="btn btn-sm btn-info" title="Lihat Detail"><i class="fa fa-eye"></i></a> ' +
                                '<a href="' + baseUrl + '/edit/' + item.id + '" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-edit"></i></a> ' +
                                '<button type="button" class="btn btn-sm btn-danger btn-delete-warga" data-id="' + item.id + '" data-name="' + name + '" title="Hapus"><i class="fa fa-trash"></i></button>' +
                            '</td>' +
                        '</tr>';
                    $('#desktop-tbody').append(row);
                });
            })
            .fail(function() {
                $('#loading-indicator').hide();
                $('#empty-indicator').text('Gagal memuat data.').show();
            });
    }

    $(document).ready(function() {
        loadData('');

        $('#search-input').on('input', function() {
            var q = $(this).val();
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(function() {
                loadData(q);
            }, 500);
        });

        $(document).on('click', '.btn-delete-warga', function() {
            wargaIdToDelete = $(this).data('id');
            $('#warga-name').text($(this).data('name'));
            $('#deleteModal').modal('show');
        });

        $('#confirmDelete').on('click', function() {
            if (!wargaIdToDelete) {
                return;
            }
            fetch(baseUrl + '/' + wargaIdToDelete, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                $('#deleteModal').modal('hide');
                if (data.success) {
                    loadData($('#search-input').val());
                } else {
                    alert('Tidak bisa menghapus data ini. Data mungkin terkait dengan akun Anda sendiri atau data lain.');
                }
            })
            .catch(function() {
                $('#deleteModal').modal('hide');
            });
        });
    });
</script>
@endsection
