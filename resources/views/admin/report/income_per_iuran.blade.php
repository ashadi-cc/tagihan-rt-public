@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Laporan Pemasukan Per Iuran</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pemasukan Per Iuran</li>
        </ol>
    </nav>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-6">
            <div class="app-summary-card app-summary-card-info app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-calendar"></i></span>
                <div>
                    <div class="app-summary-label">Periode Laporan</div>
                    <div class="app-summary-value">{{ $periodDisplay }}</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="app-summary-card app-summary-card-success app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-money"></i></span>
                <div>
                    <div class="app-summary-label">Total Pemasukan</div>
                    <div class="app-summary-value">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-start justify-content-between" style="gap: .75rem;">
                <form method="GET" action="{{ route('admin.report.income_per_iuran') }}" class="flex-grow-1">
                    <div class="font-weight-bold small text-muted mb-2">Filter</div>
                    <div class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                        <select name="month" class="form-control" style="width: 130px;">
                            <option value="">Bulan</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}" {{ $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                </option>
                            @endfor
                        </select>
                        <select name="year" class="form-control" style="width: 100px;">
                            <option value="">Tahun</option>
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                        <button type="submit" class="btn btn-primary text-nowrap">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        @if(request('month') || request('year'))
                            <a href="{{ route('admin.report.income_per_iuran', ['month' => date('m'), 'year' => date('Y')]) }}" class="btn btn-outline-secondary text-nowrap">
                                <i class="fa fa-refresh"></i> Reset
                            </a>
                        @endif
                    </div>
                </form>
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fa fa-print"></i> Cetak
                </button>
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

            @if($incomePerIuran->count() > 0)
            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($incomePerIuran as $index => $item)
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>{{ $item->billing_name }}</strong>
                        <span class="text-success font-weight-bold">Rp {{ number_format($item->total_payment, 0, ',', '.') }}</span>
                    </div>
                    <a href="#" class="badge badge-primary badge-lg btn-show-details mt-2"
                       data-billing-id="{{ $item->billing_id }}"
                       data-billing-name="{{ $item->billing_name }}"
                       data-month="{{ $month }}"
                       data-year="{{ $year }}"
                       style="cursor: pointer; text-decoration: none;">
                        {{ $item->jumlah_pembayar }} Warga
                    </a>
                </div>
                @endforeach
                <div class="list-group-item px-0 d-flex justify-content-between font-weight-bold">
                    <span>Total Keseluruhan</span>
                    <span class="text-success">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th width="5%">No</th>
                            <th width="35%">Nama Iuran</th>
                            <th width="20%">Jumlah Pembayar</th>
                            <th width="20%">Total Pemasukan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incomePerIuran as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $item->billing_name }}</strong>
                            </td>
                            <td class="text-center">
                                <a href="#" class="badge badge-primary badge-lg btn-show-details" 
                                   data-billing-id="{{ $item->billing_id }}" 
                                   data-billing-name="{{ $item->billing_name }}"
                                   data-month="{{ $month }}"
                                   data-year="{{ $year }}"
                                   style="cursor: pointer; text-decoration: none;">
                                    {{ $item->jumlah_pembayar }} Warga
                                </a>
                            </td>
                            <td class="text-right">
                                <span class="text-success font-weight-bold">Rp {{ number_format($item->total_payment, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="thead-light">
                        <tr>
                            <th colspan="3" class="text-right">Total Keseluruhan:</th>
                            <th class="text-right">
                                <span class="text-success font-weight-bold">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> Tidak ada data pemasukan untuk periode ini.
            </div>
            @endif
        </div>
    </div>

    <!-- Modal for User Details -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailsModalLabel">Detail Pembayar - <span id="billingNameModal"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="loadingSpinner" class="text-center" style="display: none;">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <div id="userDetailsContent">
                        <!-- Mobile: stacked cards -->
                        <div class="d-md-none list-group list-group-flush mb-3" id="userDetailsCards">
                            <!-- Data will be populated via AJAX -->
                        </div>

                        <!-- Tablet/desktop: table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-striped table-bordered" id="userDetailsTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="30%">Nama / Blok</th>
                                        <th width="20%">Tagihan</th>
                                        <th width="20%">Dibayar</th>
                                        <th width="20%">Sisa</th>
                                        <th width="5%">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="userDetailsBody">
                                    <!-- Data will be populated via AJAX -->
                                </tbody>
                                <tfoot class="thead-light">
                                    <tr>
                                        <th colspan="2" class="text-right">Total:</th>
                                        <th class="text-right" id="totalBilling">-</th>
                                        <th class="text-right" id="totalPaid">-</th>
                                        <th class="text-right" id="totalRemaining">-</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<style>
    @media print {
        .btn, .breadcrumb, .card-header form, .alert {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .card-header {
            background-color: #f8f9fa !important;
            border-bottom: 2px solid #000 !important;
        }
        .table {
            page-break-inside: auto;
        }
        .table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
    }
    
    .btn-show-details:hover {
        opacity: 0.8;
        transform: scale(1.05);
        transition: all 0.2s;
    }
    
    #userDetailsModal .modal-body {
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }
</style>

<script>
$(document).ready(function() {
    $('.btn-show-details').on('click', function(e) {
        e.preventDefault();
        
        const billingId = $(this).data('billing-id');
        const billingName = $(this).data('billing-name');
        const month = $(this).data('month');
        const year = $(this).data('year');
        
        // Set modal title
        $('#billingNameModal').text(billingName);
        
        // Show modal
        $('#userDetailsModal').modal('show');
        
        // Show loading spinner
        $('#loadingSpinner').show();
        $('#userDetailsContent').hide();
        
        // Fetch user details via AJAX
        $.ajax({
            url: '{{ route("admin.report.income_per_iuran.details") }}',
            type: 'GET',
            data: {
                billing_id: billingId,
                month: month,
                year: year
            },
            success: function(response) {
                // Hide loading spinner
                $('#loadingSpinner').hide();
                $('#userDetailsContent').show();
                
                // Populate table
                let tbody = $('#userDetailsBody');
                tbody.empty();

                let cards = $('#userDetailsCards');
                cards.empty();

                let totalBilling = 0;
                let totalPaid = 0;
                let totalRemaining = 0;

                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(user, index) {
                        let remaining = user.billing_amount - user.paid_amount;
                        let statusBadge = '';

                        if (user.paid_amount >= user.billing_amount) {
                            statusBadge = '<span class="badge badge-success">Lunas</span>';
                        } else if (user.paid_amount > 0) {
                            statusBadge = '<span class="badge badge-warning">Sebagian</span>';
                        } else {
                            statusBadge = '<span class="badge badge-danger">Belum</span>';
                        }

                        totalBilling += parseFloat(user.billing_amount);
                        totalPaid += parseFloat(user.paid_amount);
                        totalRemaining += parseFloat(remaining);

                        let row = `
                            <tr>
                                <td class="text-center">${index + 1}</td>
                                <td>
                                    <strong>${user.user_name}</strong><br>
                                    <small class="text-muted">${user.user_blok_number}</small>
                                </td>
                                <td class="text-right">Rp ${formatNumber(user.billing_amount)}</td>
                                <td class="text-right text-success">Rp ${formatNumber(user.paid_amount)}</td>
                                <td class="text-right ${remaining > 0 ? 'text-danger' : 'text-success'}">
                                    Rp ${formatNumber(remaining)}
                                </td>
                                <td class="text-center">${statusBadge}</td>
                            </tr>
                        `;
                        tbody.append(row);

                        let card = `
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>${user.user_name}</strong><br>
                                        <small class="text-muted">${user.user_blok_number}</small>
                                    </div>
                                    ${statusBadge}
                                </div>
                                <div class="d-flex justify-content-between mt-2 small">
                                    <span class="text-muted">Tagihan</span>
                                    <span>Rp ${formatNumber(user.billing_amount)}</span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Dibayar</span>
                                    <span class="text-success">Rp ${formatNumber(user.paid_amount)}</span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Sisa</span>
                                    <span class="${remaining > 0 ? 'text-danger' : 'text-success'}">Rp ${formatNumber(remaining)}</span>
                                </div>
                            </div>
                        `;
                        cards.append(card);
                    });

                    // Update footer totals
                    $('#totalBilling').text('Rp ' + formatNumber(totalBilling));
                    $('#totalPaid').text('Rp ' + formatNumber(totalPaid));
                    $('#totalRemaining').text('Rp ' + formatNumber(totalRemaining));

                    cards.append(`
                        <div class="list-group-item px-3 bg-light border-top border-dark">
                            <div class="d-flex justify-content-between font-weight-bold">
                                <span>Total</span>
                                <span></span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Tagihan</span>
                                <span>Rp ${formatNumber(totalBilling)}</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Dibayar</span>
                                <span class="text-success">Rp ${formatNumber(totalPaid)}</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Sisa</span>
                                <span class="${totalRemaining > 0 ? 'text-danger' : 'text-success'}">Rp ${formatNumber(totalRemaining)}</span>
                            </div>
                        </div>
                    `);
                } else {
                    tbody.append('<tr><td colspan="6" class="text-center">Tidak ada data</td></tr>');
                    cards.append('<div class="list-group-item px-0 text-center">Tidak ada data</div>');
                    $('#totalBilling').text('-');
                    $('#totalPaid').text('-');
                    $('#totalRemaining').text('-');
                }
            },
            error: function(xhr) {
                $('#loadingSpinner').hide();
                $('#userDetailsContent').show();
                $('#userDetailsBody').html('<tr><td colspan="6" class="text-center text-danger">Terjadi kesalahan saat memuat data</td></tr>');
                console.error('Error:', xhr);
            }
        });
    });
    
    // Helper function to format numbers
    function formatNumber(number) {
        return parseFloat(number).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
});
</script>
</style>
@endsection
