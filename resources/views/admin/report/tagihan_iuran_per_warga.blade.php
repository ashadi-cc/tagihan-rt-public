@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Laporan Tagihan Iuran Per Warga</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tagihan Iuran Per Warga</li>
        </ol>
    </nav>
    
    <!-- Summary Cards -->

    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-3 mb-md-0">
            <div class="app-summary-card app-summary-card-primary app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-file-text"></i></span>
                <div>
                    <div class="app-summary-label">Total Tagihan</div>
                    <div class="app-summary-value">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3 mb-md-0">
            <div class="app-summary-card app-summary-card-success app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-check-circle"></i></span>
                <div>
                    <div class="app-summary-label">Total Pembayaran</div>
                    <div class="app-summary-value">Rp {{ number_format($totalPembayaran, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="app-summary-card app-summary-card-warning app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-exclamation-triangle"></i></span>
                <div>
                    <div class="app-summary-label">Total Tunggakan</div>
                    <div class="app-summary-value">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="app-summary-card app-summary-card-info app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-list"></i></span>
                <div>
                    <div class="app-summary-label">Total Data</div>
                    <div class="app-summary-value">{{ $tagihans->total() }} item</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4 small text-muted">
        <i class="fa fa-check text-success"></i> {{ $countLunas }} Lunas &nbsp;
        <i class="fa fa-clock-o text-warning"></i> {{ $countCicilan }} Cicilan &nbsp;
        <i class="fa fa-times text-danger"></i> {{ $countBelumBayar }} Belum
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex align-items-center mb-3">
                        <span class="icon-chip icon-chip-primary mr-2"><i class="fa fa-filter"></i></span>
                        <span class="app-card-title">Filter Data</span>
                    </div>
                    <form method="GET" action="{{ url('/admin/iuran-per-warga') }}" id="filterForm">
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <label class="small">Tanggal Mulai</label>
                                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small">Tanggal Akhir</label>
                                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="small">Nama / Blok Warga</label>
                                <input type="text" name="user_search" class="form-control" 
                                       placeholder="Cari nama atau blok..." value="{{ request('user_search') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="small">Jenis Iuran</label>
                                <select name="billing_id" class="form-control">
                                    <option value="">-- Semua Iuran --</option>
                                    @foreach($billings as $billing)
                                        <option value="{{ $billing->id }}" {{ request('billing_id') == $billing->id ? 'selected' : '' }}>
                                            {{ $billing->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="small">Status Bayar</label>
                                <select name="status" class="form-control">
                                    <option value="">-- Semua --</option>
                                    <option value="lunas" {{ request('status') == 'lunas' ? 'selected' : '' }}>Lunas</option>
                                    <option value="kurang_bayar" {{ request('status') == 'kurang_bayar' ? 'selected' : '' }}>Kurang Bayar</option>
                                    <option value="belum_bayar" {{ request('status') == 'belum_bayar' ? 'selected' : '' }}>Belum Bayar</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12 d-flex flex-wrap" style="gap: .5rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Filter
                                </button>
                                @if(request()->hasAny(['start_date', 'end_date', 'user_search', 'billing_id', 'status']))
                                    <a href="{{ url('/admin/iuran-per-warga') }}" class="btn btn-outline-secondary">
                                        <i class="fa fa-refresh"></i> Reset
                                    </a>
                                @endif
                                @if(request()->filled('start_date') && request()->filled('end_date'))
                                    <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                        <i class="fa fa-file-excel-o"></i> Download Excel
                                    </button>
                                @else
                                    <button type="button" class="btn btn-secondary" disabled title="Pilih tanggal mulai dan akhir untuk download Excel">
                                        <i class="fa fa-file-excel-o"></i> Download Excel
                                    </button>
                                @endif
                            </div>
                        </div>
                    </form>
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

            @if(!request()->filled('start_date') || !request()->filled('end_date'))
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> <strong>Tips:</strong> Pilih <strong>tanggal mulai</strong> dan <strong>tanggal akhir</strong> untuk mengaktifkan tombol download Excel.
            </div>
            @endif

            @if($tagihans->count() > 0)
            <div class="alert alert-light border">
                <div class="mb-2"><i class="fa fa-info-circle"></i> <strong>Keterangan Status:</strong></div>
                <div class="d-flex align-items-center mb-1">
                    <span class="badge badge-success mr-2" style="min-width: 85px;">Lunas</span>
                    <span class="small text-muted">Tagihan sudah dibayar penuh</span>
                </div>
                <div class="d-flex align-items-center mb-1">
                    <span class="badge badge-warning mr-2" style="min-width: 85px;">Cicilan</span>
                    <span class="small text-muted">Tagihan dibayar sebagian</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge badge-danger mr-2" style="min-width: 85px;">Belum Bayar</span>
                    <span class="small text-muted">Tagihan belum dibayar</span>
                </div>
            </div>
            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($tagihans as $index => $tagihan)
                @php
                    $isPaid = $tagihan->tunggakan <= 0;
                @endphp
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <strong>{{ $tagihan->user_name }}</strong>
                            <span class="badge badge-info ml-1">{{ $tagihan->user_blok_number }}</span>
                        </div>
                        @if($isPaid)
                            <span class="badge badge-success">Lunas</span>
                        @elseif($tagihan->payment_amount > 0)
                            <span class="badge badge-warning">Cicilan</span>
                        @else
                            <span class="badge badge-danger">Belum Bayar</span>
                        @endif
                    </div>
                    <div class="text-muted small mb-2">
                        {{ $tagihan->billing_name }} &middot; {{ $tagihan->billing_generated_date->format('d/m/Y') }}
                    </div>
                    <div class="row small mb-2">
                        <div class="col-4">
                            <div class="text-muted">Tagihan</div>
                            <div>Rp {{ number_format($tagihan->billing_amount, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted">Dibayar</div>
                            <div class="text-success font-weight-bold">Rp {{ number_format($tagihan->payment_amount, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted">Tunggakan</div>
                            @if($tagihan->tunggakan > 0)
                                <div class="text-danger font-weight-bold">Rp {{ number_format($tagihan->tunggakan, 0, ',', '.') }}</div>
                            @else
                                <div class="text-muted">-</div>
                            @endif
                        </div>
                    </div>
                    <a href="{{ url('/admin/master/warga/' . $tagihan->user_id . '/tagihan') }}" class="btn btn-sm btn-outline-primary btn-block">
                        <i class="fa fa-eye"></i> Lihat Tagihan
                    </a>
                </div>
                @endforeach
            </div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="10%">Tanggal Tagihan</th>
                            <th width="18%">Nama Warga</th>
                            <th width="8%">Blok</th>
                            <th width="15%">Jenis Iuran</th>
                            <th width="11%" class="text-right">Tagihan</th>
                            <th width="11%" class="text-right">Dibayar</th>
                            <th width="11%" class="text-right">Tunggakan</th>
                            <th width="8%" class="text-center">Status</th>
                            <th width="8%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tagihans as $index => $tagihan)
                        @php
                            $isPaid = $tagihan->tunggakan <= 0;
                            $rowClass = $isPaid ? 'table-success' : ($tagihan->payment_amount > 0 ? 'table-warning' : 'table-danger');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="text-center">{{ ($tagihans->currentPage() - 1) * $tagihans->perPage() + $index + 1 }}</td>
                            <td>{{ $tagihan->billing_generated_date->format('d/m/Y') }}</td>
                            <td><strong>{{ $tagihan->user_name }}</strong></td>
                            <td class="text-center">
                                <span class="badge badge-info">{{ $tagihan->user_blok_number }}</span>
                            </td>
                            <td>{{ $tagihan->billing_name }}</td>
                            <td class="text-right">Rp {{ number_format($tagihan->billing_amount, 0, ',', '.') }}</td>
                            <td class="text-right">
                                <span class="text-success font-weight-bold">Rp {{ number_format($tagihan->payment_amount, 0, ',', '.') }}</span>
                            </td>
                            <td class="text-right">
                                @if($tagihan->tunggakan > 0)
                                    <span class="text-danger font-weight-bold">Rp {{ number_format($tagihan->tunggakan, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($isPaid)
                                    <span class="badge badge-success">Lunas</span>
                                @elseif($tagihan->payment_amount > 0)
                                    <span class="badge badge-warning">Cicilan</span>
                                @else
                                    <span class="badge badge-danger">Belum Bayar</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ url('/admin/master/warga/' . $tagihan->user_id . '/tagihan') }}" 
                                   class="btn btn-sm btn-primary" 
                                   title="Lihat Tagihan Warga">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="text-muted small mb-2 mb-sm-0">
                        Showing {{ $tagihans->firstItem() }} to {{ $tagihans->lastItem() }} of {{ $tagihans->total() }} results
                    </div>
                    <div>
                        @if ($tagihans->onFirstPage())
                            <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                        @else
                            <a href="{{ $tagihans->appends(request()->query())->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                        @endif

                        @if ($tagihans->hasMorePages())
                            <a href="{{ $tagihans->appends(request()->query())->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
                        @else
                            <span class="btn btn-sm btn-light disabled">Next »</span>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-circle"></i> Tidak ada data tagihan sesuai dengan filter yang dipilih.
            </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
<script>
function exportToExcel() {
    // Get current filter parameters
    const params = new URLSearchParams(window.location.search);
    const exportUrl = '{{ url("/admin/iuran-per-warga/export") }}?' + params.toString();
    
    // Redirect to export URL
    window.location.href = exportUrl;
}
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
    
    .table-danger {
        background-color: #f8d7da !important;
    }
    
    .table-hover tbody tr:hover {
        opacity: 0.85;
    }
    
    @media print {
        .btn, .breadcrumb, .card-header .row, .alert {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .table-success, .table-warning, .table-danger {
            background-color: white !important;
        }
    }
</style>
@endsection
