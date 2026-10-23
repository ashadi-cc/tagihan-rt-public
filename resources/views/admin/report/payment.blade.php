@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Laporan Pembayaran Terverifikasi</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/master/payment') }}">Daftar Channel Pembayaran</a></li>
            <li class="breadcrumb-item active" aria-current="page">Laporan Pembayaran - {{ $payment->name }}</li>
        </ol>
    </nav>

    <!-- Payment Information Card -->
    <div class="card mb-3">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-info-circle"></i></span>
            <span class="app-card-title">Informasi Pembayaran</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%">Nama Pembayaran</th>
                            <td><strong>{{ $payment->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>Tipe Pembayaran</th>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%">Status</th>
                            <td>
                                @if($payment->disabled)
                                    <span class="badge badge-danger">Tidak Aktif</span>
                                @else
                                    <span class="badge badge-success">Aktif</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="app-summary-card app-summary-card-info app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-calendar"></i></span>
                <div>
                    <div class="app-summary-label">Periode Laporan</div>
                    <div class="app-summary-value">{{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="app-summary-card app-summary-card-primary app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-list"></i></span>
                <div>
                    <div class="app-summary-label">Total Pembayaran</div>
                    <div class="app-summary-value">{{ $pembayarans->total() }} Transaksi</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="app-summary-card app-summary-card-success app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-money"></i></span>
                <div>
                    <div class="app-summary-label">Total Nominal</div>
                    <div class="app-summary-value">Rp {{ number_format($total, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: .75rem;">
                <form method="GET" action="{{ route('admin.report.payment') }}" class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                    <input type="hidden" name="payment_id" value="{{ $payment->id }}">

                    <select name="month" class="form-control" style="width: 150px;">
                        <option value="">-- Pilih Bulan --</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}" {{ $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endfor
                    </select>

                    <select name="year" class="form-control" style="width: 110px;">
                        <option value="">-- Pilih Tahun --</option>
                        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>

                    <select name="payment_type" class="form-control" style="width: 170px;">
                        <option value="all" {{ $paymentType == 'all' ? 'selected' : '' }}>Semua Tipe</option>
                        <option value="current_billing" {{ $paymentType == 'current_billing' ? 'selected' : '' }}>Iuran Bulanan</option>
                        <option value="previous_billing" {{ $paymentType == 'previous_billing' ? 'selected' : '' }}>Tunggakan</option>
                        <option value="user_deposit" {{ $paymentType == 'user_deposit' ? 'selected' : '' }}>Deposit</option>
                        <option value="manual_billing" {{ $paymentType == 'manual_billing' ? 'selected' : '' }}>Iuran Khusus</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>
                    @if(request('month') || request('year') || request('payment_type'))
                        <a href="{{ route('admin.report.payment', ['payment_id' => $payment->id, 'month' => date('m'), 'year' => date('Y'), 'payment_type' => 'all']) }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </form>
                <a href="{{ url('/admin/master/payment') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Kembali
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

            @if($pembayarans->count() > 0)
            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($pembayarans as $index => $pembayaran)
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <strong>{{ $pembayaran->user_name ?? '-' }}</strong>
                            <div class="text-muted small">{{ $pembayaran->user_blok ?? '-' }}</div>
                        </div>
                        <span class="text-success font-weight-bold">Rp {{ number_format($pembayaran->payment_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="mb-1">
                        @if($pembayaran->payment_type == 'current_billing')
                            <span class="badge badge-primary">{{ $pembayaran->formatted_payment_type }}</span>
                            @if($pembayaran->payment_type_metadata)
                                <span class="text-muted small">{{ $pembayaran->payment_type_metadata }}</span>
                            @endif
                        @elseif($pembayaran->payment_type == 'previous_billing')
                            <span class="badge badge-warning">{{ $pembayaran->formatted_payment_type }}</span>
                        @elseif($pembayaran->payment_type == 'user_deposit')
                            <span class="badge badge-info">{{ $pembayaran->formatted_payment_type }}</span>
                        @else
                            <span class="badge badge-secondary">{{ $pembayaran->formatted_payment_type }}</span>
                        @endif
                    </div>
                    <div class="text-muted small mb-2">{{ $pembayaran->payment_date->format('d/m/Y') }}</div>
                    <div class="d-flex flex-wrap align-items-center" style="gap: .4rem;">
                        @if($pembayaran->file_path)
                            <button type="button" class="btn btn-sm btn-primary btn-view-proof"
                                    data-id="{{ $pembayaran->id }}"
                                    data-name="{{ $pembayaran->user_name }}"
                                    data-file-path="{{ $pembayaran->file_path }}"
                                    data-file-driver="{{ $pembayaran->file_driver }}"
                                    data-toggle="modal"
                                    data-target="#proofModal">
                                <i class="fa fa-image"></i> Bukti
                            </button>
                        @endif
                        @if($pembayaran->user_id)
                            <a href="{{ $pembayaran->payment_type == 'user_deposit' ? route('admin.warga.deposit.show', ['userID' => $pembayaran->user_id, 'depositID' => $pembayaran->id]) : route('admin.warga.pembayaran.show', ['userID' => $pembayaran->user_id, 'pembayaranID' => $pembayaran->id]) }}" class="btn btn-sm btn-info">
                                <i class="fa fa-eye"></i> Lihat
                            </a>
                        @else
                            <button type="button" class="btn btn-sm btn-secondary" disabled>
                                <i class="fa fa-eye-slash"></i> Lihat
                            </button>
                        @endif
                        @if($availablePayments->count() > 0)
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-warning dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-exchange"></i> Pindah
                                </button>
                                <div class="dropdown-menu">
                                    <h6 class="dropdown-header">Pindahkan ke:</h6>
                                    @foreach($availablePayments as $availPayment)
                                        <a class="dropdown-item btn-move-payment" href="#"
                                           data-pembayaran-id="{{ $pembayaran->id }}"
                                           data-new-payment-id="{{ $availPayment->id }}"
                                           data-new-payment-name="{{ $availPayment->name }}"
                                           data-user-name="{{ $pembayaran->user_name }}">
                                            {{ $availPayment->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
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
                            <th width="10%">Tanggal</th>
                            <th width="20%">Warga</th>
                            <th width="15%">Tipe Pembayaran</th>
                            <th width="15%">Jumlah</th>
                            <th width="10%">Bukti</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pembayarans as $index => $pembayaran)
                        <tr>
                            <td>{{ $pembayarans->firstItem() + $index }}</td>
                            <td>{{ $pembayaran->payment_date->format('d/m/Y') }}</td>
                            <td>
                                <strong>{{ $pembayaran->user_name ?? '-' }}</strong>
                                <br>
                                <small class="text-muted">{{ $pembayaran->user_blok ?? '-' }}</small>
                            </td>
                            <td>
                                @if($pembayaran->payment_type == 'current_billing')
                                    <span class="badge badge-primary">{{ $pembayaran->formatted_payment_type }}</span>
                                    @if($pembayaran->payment_type_metadata)
                                        <br><small class="text-muted">{{ $pembayaran->payment_type_metadata }}</small>
                                    @endif
                                @elseif($pembayaran->payment_type == 'previous_billing')
                                    <span class="badge badge-warning">{{ $pembayaran->formatted_payment_type }}</span>
                                @elseif($pembayaran->payment_type == 'user_deposit')
                                    <span class="badge badge-info">{{ $pembayaran->formatted_payment_type }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ $pembayaran->formatted_payment_type }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-success font-weight-bold">Rp {{ number_format($pembayaran->payment_amount, 0, ',', '.') }}</span>
                            </td>
                            <td>
                                @if($pembayaran->file_path)
                                    <button type="button" class="btn btn-sm btn-primary btn-view-proof"
                                            data-id="{{ $pembayaran->id }}"
                                            data-name="{{ $pembayaran->user_name }}"
                                            data-file-path="{{ $pembayaran->file_path }}"
                                            data-file-driver="{{ $pembayaran->file_driver }}"
                                            data-toggle="modal"
                                            data-target="#proofModal">
                                        <i class="fa fa-image"></i>
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    @if($pembayaran->user_id)
                                        <a href="{{ $pembayaran->payment_type == 'user_deposit' ? route('admin.warga.deposit.show', ['userID' => $pembayaran->user_id, 'depositID' => $pembayaran->id]) : route('admin.warga.pembayaran.show', ['userID' => $pembayaran->user_id, 'pembayaranID' => $pembayaran->id]) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-secondary" disabled title="Data tidak lengkap">
                                            <i class="fa fa-eye-slash"></i>
                                        </button>
                                    @endif

                                    @if($availablePayments->count() > 0)
                                        <button type="button" class="btn btn-sm btn-warning dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pindah ke Payment Lain">
                                            <i class="fa fa-exchange"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <h6 class="dropdown-header">Pindahkan ke:</h6>
                                            @foreach($availablePayments as $availPayment)
                                                <a class="dropdown-item btn-move-payment" href="#"
                                                   data-pembayaran-id="{{ $pembayaran->id }}"
                                                   data-new-payment-id="{{ $availPayment->id }}"
                                                   data-new-payment-name="{{ $availPayment->name }}"
                                                   data-user-name="{{ $pembayaran->user_name }}">
                                                    {{ $availPayment->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="text-muted small mb-2 mb-sm-0">
                        Showing {{ $pembayarans->firstItem() }} to {{ $pembayarans->lastItem() }} of {{ $pembayarans->total() }} results
                    </div>
                    <div>
                        @if ($pembayarans->onFirstPage())
                            <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                        @else
                            <a href="{{ $pembayarans->appends(['payment_id' => $payment->id, 'month' => $month, 'year' => $year, 'payment_type' => $paymentType])->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                        @endif

                        @if ($pembayarans->hasMorePages())
                            <a href="{{ $pembayarans->appends(['payment_id' => $payment->id, 'month' => $month, 'year' => $year, 'payment_type' => $paymentType])->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
                        @else
                            <span class="btn btn-sm btn-light disabled">Next »</span>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                Tidak ada data pembayaran terverifikasi untuk periode ini.
            </div>
            @endif
        </div>
    </div>

    <!-- Proof Modal -->
    <div class="modal fade" id="proofModal" tabindex="-1" role="dialog" aria-labelledby="proofModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="proofModalLabel">Bukti Pembayaran - <span id="modal-user-name"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div id="loading-spinner" class="mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat bukti...</p>
                    </div>
                    <img id="proof-image" src="" alt="Bukti Pembayaran" class="img-fluid" style="display: none; max-height: 70vh;">
                    <iframe id="proof-pdf" src="" style="display: none; width: 100%; height: 70vh; border: none;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Move Payment Confirmation Modal -->
    <div class="modal fade" id="movePaymentModal" tabindex="-1" role="dialog" aria-labelledby="movePaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="movePaymentModalLabel">
                        <i class="fa fa-exchange"></i> Konfirmasi Pindah Payment
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin memindahkan pembayaran ini?</p>
                    <p><strong>Warga:</strong> <span id="move-user-name"></span></p>
                    <p><strong>Pindah ke Payment:</strong> <span id="move-payment-name"></span></p>
                    <p class="text-muted small mb-0">Catatan: Pembayaran ini akan dicatat sebagai pembayaran untuk payment yang baru dipilih.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-warning" id="confirmMovePayment">
                        <i class="fa fa-exchange"></i> Pindahkan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    let pembayaranIdToMove = null;
    let newPaymentId = null;

    $(document).ready(function() {
        // Handle proof modal
        $('.btn-view-proof').on('click', function() {
            const userName = $(this).data('name');
            const filePath = $(this).data('file-path');
            const fileDriver = $(this).data('file-driver');

            $('#modal-user-name').text(userName);

            // Determine file type from extension
            const extension = filePath.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            // Build file URL - match rt-transaction implementation
            const baseUrl = "{{ url('/admin/file') }}/" + encodeURIComponent(fileDriver);
            const fileUrl = baseUrl + "?path=" + encodeURIComponent(filePath);

            if (imageExtensions.includes(extension)) {
                // It's an image
                $('#loading-spinner').show();
                $('#proof-image').hide();
                $('#proof-pdf').hide();

                $('#proof-image').attr('src', fileUrl);
                $('#proof-image').on('load', function() {
                    $('#loading-spinner').hide();
                    $('#proof-image').show();
                });
                $('#proof-image').on('error', function() {
                    $('#loading-spinner').hide();
                    alert('Gagal memuat gambar');
                });
            } else {
                // It's a PDF or other file
                $('#loading-spinner').hide();
                $('#proof-image').hide();
                $('#proof-pdf').attr('src', fileUrl);
                $('#proof-pdf').show();
            }
        });

        // Reset modal when closed
        $('#proofModal').on('hidden.bs.modal', function () {
            $('#proof-image').attr('src', '');
            $('#proof-pdf').attr('src', '');
            $('#proof-image').off('load error');
        });

        // Handle move payment dropdown click
        $(document).on('click', '.btn-move-payment', function(e) {
            e.preventDefault();

            pembayaranIdToMove = $(this).data('pembayaran-id');
            newPaymentId = $(this).data('new-payment-id');
            const newPaymentName = $(this).data('new-payment-name');
            const userName = $(this).data('user-name');

            $('#move-user-name').text(userName);
            $('#move-payment-name').text(newPaymentName);
            $('#movePaymentModal').modal('show');
        });

        // Handle confirm move payment
        $('#confirmMovePayment').on('click', function() {
            if (pembayaranIdToMove && newPaymentId) {
                const url = '/admin/report/payment/' + pembayaranIdToMove + '/move';

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        new_payment_id: newPaymentId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        $('#movePaymentModal').modal('hide');
                        console.error('Failed to move payment');
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    location.reload();
                });
            }
        });
    });
</script>
@endsection
