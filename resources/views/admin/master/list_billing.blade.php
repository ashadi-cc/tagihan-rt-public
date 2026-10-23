@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Master Iuran</h1>
    @include('admin.master._header_billing', ['billing_list' => 'active', 'billing_upload' => ''])
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                <input type="text" id="search-input" class="form-control" style="flex: 1 1 220px; width: auto;" placeholder="Cari nama iuran">
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
            <div class="alert alert-info">
                <h6 class="mb-2"><i class="fa fa-info-circle"></i> Informasi Penting:</h6>
                <ul class="mb-0">
                    <li><strong>Jenis Tagihan "Rutin Per Bulan"</strong>: Iuran dengan tipe ini akan ditagihkan secara otomatis ke semua warga setiap bulannya.</li>
                    <li><strong>Status Aktif</strong>: Jika status iuran tidak aktif, maka tagihan rutin tidak akan dibuat secara otomatis.</li>
                    <li><strong>Prioritas</strong>: Urutan prioritas untuk pembayaran iuran (nilai lebih rendah = prioritas lebih tinggi).</li>
                </ul>
            </div>

            <div id="loading-indicator" class="text-center text-muted py-4">
                <i class="fa fa-spinner fa-spin"></i> Memuat data...
            </div>
            <div id="empty-indicator" class="alert alert-info" style="display: none;">
                Tidak ada data iuran.
            </div>

            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div id="mobile-list" class="d-md-none list-group list-group-flush mb-3"></div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="33%">Nama</th>
                            <th width="15%">Nominal</th>
                            <th width="12%">Prioritas</th>
                            <th width="15%">Jenis Tagihan</th>
                            <th width="8%">Aktif</th>
                            <th width="12%">Aksi</th>
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
                    <p>Apakah Anda yakin ingin menghapus iuran ini?</p>
                    <p class="mb-0"><strong id="billing-name"></strong></p>
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
    var billingIdToDelete = null;
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
                    var name = escapeHtml(item.name);
                    var billingType = escapeHtml(item.billing_type);

                    var card = '' +
                        '<div class="list-group-item px-0">' +
                            '<div class="d-flex justify-content-between align-items-start mb-1">' +
                                '<strong>' + name + '</strong>' +
                                item.disabled +
                            '</div>' +
                            '<div class="text-muted small mb-1">' + billingType + ' &middot; Prioritas ' + item.priority + '</div>' +
                            '<div class="mb-2 font-weight-bold text-success">Rp ' + item.amount + '</div>' +
                            '<div class="d-flex flex-wrap" style="gap: .4rem;">' +
                                '<a href="' + baseUrl + '/view/' + item.id + '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> Lihat</a>' +
                                '<a href="' + baseUrl + '/edit/' + item.id + '" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i> Edit</a>' +
                                '<button type="button" class="btn btn-sm btn-danger btn-delete-billing" data-id="' + item.id + '" data-name="' + name + '"><i class="fa fa-trash"></i> Hapus</button>' +
                            '</div>' +
                        '</div>';
                    $('#mobile-list').append(card);

                    var row = '' +
                        '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + name + '</td>' +
                            '<td>Rp ' + item.amount + '</td>' +
                            '<td>' + item.priority + '</td>' +
                            '<td>' + billingType + '</td>' +
                            '<td>' + item.disabled + '</td>' +
                            '<td class="text-nowrap">' +
                                '<a href="' + baseUrl + '/view/' + item.id + '" class="btn btn-sm btn-info" title="Lihat Detail"><i class="fa fa-eye"></i></a> ' +
                                '<a href="' + baseUrl + '/edit/' + item.id + '" class="btn btn-sm btn-warning" title="Edit"><i class="fa fa-edit"></i></a> ' +
                                '<button type="button" class="btn btn-sm btn-danger btn-delete-billing" data-id="' + item.id + '" data-name="' + name + '" title="Hapus"><i class="fa fa-trash"></i></button>' +
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

        $(document).on('click', '.btn-delete-billing', function() {
            billingIdToDelete = $(this).data('id');
            $('#billing-name').text($(this).data('name'));
            $('#deleteModal').modal('show');
        });

        $('#confirmDelete').on('click', function() {
            if (!billingIdToDelete) {
                return;
            }
            fetch(baseUrl + '/' + billingIdToDelete, {
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
                    alert('Tidak bisa menghapus data ini. Data dipakai di tempat lain.');
                }
            })
            .catch(function() {
                $('#deleteModal').modal('hide');
            });
        });
    });
</script>
@endsection
