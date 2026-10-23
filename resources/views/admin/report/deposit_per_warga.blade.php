@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Laporan Deposit Per Warga</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Deposit Per Warga</li>
        </ol>
    </nav>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-3 mb-md-0">
            <div class="app-summary-card app-summary-card-info app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-arrow-down"></i></span>
                <div>
                    <div class="app-summary-label">Total Deposit Masuk</div>
                    <div class="app-summary-value">Rp {{ number_format($totalDeposit, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3 mb-md-0">
            <div class="app-summary-card app-summary-card-warning app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-shopping-cart"></i></span>
                <div>
                    <div class="app-summary-label">Total Terpakai</div>
                    <div class="app-summary-value">Rp {{ number_format($totalUsedDeposit, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="app-summary-card app-summary-card-success app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-check-circle"></i></span>
                <div>
                    <div class="app-summary-label">Sisa Deposit Tersedia</div>
                    <div class="app-summary-value">Rp {{ number_format($availableDeposit, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="app-summary-card app-summary-card-primary app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-users"></i></span>
                <div>
                    <div class="app-summary-label">Jumlah Warga</div>
                    <div class="app-summary-value">{{ $totalWargaWithDeposit }}</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-table"></i></span>
            <span class="app-card-title">Daftar Deposit Per Warga</span>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="fa fa-print"></i> Cetak
            </button>
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

            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <strong><i class="fa fa-exclamation-triangle"></i> Error:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif

            <div class="alert alert-info mb-4">
                <i class="fa fa-info-circle"></i> 
                <strong>Informasi:</strong> Halaman ini menampilkan ringkasan deposit untuk setiap warga yang pernah melakukan deposit.
                <ul class="mb-0 mt-2">
                    <li><strong>Total Deposit:</strong> Jumlah total deposit yang telah diverifikasi</li>
                    <li><strong>Total Terpakai:</strong> Jumlah deposit yang sudah digunakan untuk pembayaran iuran</li>
                    <li><strong>Sisa Deposit:</strong> Saldo deposit yang masih tersedia untuk digunakan</li>
                </ul>
            </div>

            @if($depositsPerWarga->count() > 0)
            <div>
                <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
                <div class="d-md-none list-group list-group-flush mb-3">
                    @foreach($depositsPerWarga as $index => $deposit)
                    @php
                        $hasRemainingDeposit = $deposit->remaining_deposit > 0;
                    @endphp
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>{{ $deposit->user_name }}</strong>
                                <div><span class="badge badge-info">{{ $deposit->user_blok }}</span></div>
                            </div>
                            @if($hasRemainingDeposit)
                                <span class="badge badge-success">Tersedia</span>
                            @else
                                <span class="badge badge-secondary">Habis</span>
                            @endif
                        </div>
                        <div class="row small mb-2">
                            <div class="col-4">
                                <div class="text-muted">Total</div>
                                <div class="text-primary font-weight-bold">Rp {{ number_format($deposit->total_deposit, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Terpakai</div>
                                @if($deposit->total_used_deposit > 0)
                                    <div class="text-warning font-weight-bold">Rp {{ number_format($deposit->total_used_deposit, 0, ',', '.') }}</div>
                                @else
                                    <div class="text-muted">-</div>
                                @endif
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Sisa</div>
                                @if($deposit->remaining_deposit > 0)
                                    <div class="text-success font-weight-bold">Rp {{ number_format($deposit->remaining_deposit, 0, ',', '.') }}</div>
                                @elseif($deposit->remaining_deposit < 0)
                                    <div class="text-danger font-weight-bold">Rp {{ number_format($deposit->remaining_deposit, 0, ',', '.') }}</div>
                                @else
                                    <div class="text-muted">Rp 0</div>
                                @endif
                            </div>
                        </div>
                        <a href="{{ url('/admin/master/warga/' . $deposit->user_id . '/deposit') }}" class="btn btn-sm btn-outline-primary btn-block">
                            <i class="fa fa-eye"></i> Detail
                        </a>
                    </div>
                    @endforeach
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between font-weight-bold">
                            <span>TOTAL</span>
                        </div>
                        <div class="row small">
                            <div class="col-4">
                                <div class="text-primary font-weight-bold">Rp {{ number_format($depositsPerWarga->sum('total_deposit'), 0, ',', '.') }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-warning font-weight-bold">Rp {{ number_format($depositsPerWarga->sum('total_used_deposit'), 0, ',', '.') }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-success font-weight-bold">Rp {{ number_format($depositsPerWarga->sum('remaining_deposit'), 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tablet/desktop: table -->
                <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-bordered table-hover" id="depositTable">
                    <thead class="thead-dark">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="20%">Nama Warga</th>
                            <th width="10%" class="text-center">Blok</th>
                            <th width="15%" class="text-right">Total Deposit</th>
                            <th width="15%" class="text-right">Total Terpakai</th>
                            <th width="15%" class="text-right">Sisa Deposit</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($depositsPerWarga as $index => $deposit)
                        @php
                            $hasRemainingDeposit = $deposit->remaining_deposit > 0;
                            $rowClass = $hasRemainingDeposit ? 'table-success' : ($deposit->total_deposit > 0 ? 'table-warning' : '');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td><strong>{{ $deposit->user_name }}</strong></td>
                            <td class="text-center">
                                <span class="badge badge-info">{{ $deposit->user_blok }}</span>
                            </td>
                            <td class="text-right">
                                <span class="text-primary font-weight-bold">Rp {{ number_format($deposit->total_deposit, 0, ',', '.') }}</span>
                            </td>
                            <td class="text-right">
                                @if($deposit->total_used_deposit > 0)
                                    <span class="text-warning font-weight-bold">Rp {{ number_format($deposit->total_used_deposit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($deposit->remaining_deposit > 0)
                                    <span class="text-success font-weight-bold">Rp {{ number_format($deposit->remaining_deposit, 0, ',', '.') }}</span>
                                @elseif($deposit->remaining_deposit < 0)
                                    <span class="text-danger font-weight-bold">Rp {{ number_format($deposit->remaining_deposit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">Rp 0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($hasRemainingDeposit)
                                    <span class="badge badge-success">Tersedia</span>
                                @else
                                    <span class="badge badge-secondary">Habis</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ url('/admin/master/warga/' . $deposit->user_id . '/deposit') }}" 
                                   class="btn btn-sm btn-primary" 
                                   title="Lihat Detail Deposit">
                                    <i class="fa fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="thead-light">
                        <tr>
                            <th colspan="3" class="text-right">TOTAL:</th>
                            <th class="text-right">
                                <strong class="text-primary">Rp {{ number_format($depositsPerWarga->sum('total_deposit'), 0, ',', '.') }}</strong>
                            </th>
                            <th class="text-right">
                                <strong class="text-warning">Rp {{ number_format($depositsPerWarga->sum('total_used_deposit'), 0, ',', '.') }}</strong>
                            </th>
                            <th class="text-right">
                                <strong class="text-success">Rp {{ number_format($depositsPerWarga->sum('remaining_deposit'), 0, ',', '.') }}</strong>
                            </th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </div>
            @else
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-circle"></i> Tidak ada data deposit yang ditemukan. Belum ada warga yang melakukan deposit.
            </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Optional: Add search functionality for table
    $(document).ready(function() {
        // Add search box above table if needed
        if ($('#depositTable tbody tr').length > 10) {
            $('#depositTable').before('<div class="mb-3"><input type="text" id="tableSearch" class="form-control" placeholder="Cari nama warga atau blok..."></div>');
            
            $('#tableSearch').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#depositTable tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        }
    });
</script>

<style>
    .table th {
        vertical-align: middle;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 0.85rem;
        padding: 0.35em 0.65em;
    }
    
    /* Row color customization for better visibility */
    .table-success {
        background-color: #d4edda !important;
    }
    
    .table-warning {
        background-color: #fff3cd !important;
    }
    
    .table-hover tbody tr:hover {
        opacity: 0.85;
    }
    
    @media print {
        .btn, .breadcrumb, #tableSearch, .alert {
            display: none !important;
        }
        .card {
            border: none !important;
        }
        .card-header {
            background-color: #f8f9fa !important;
            border: 1px solid #dee2e6 !important;
        }
    }
</style>
@endsection
