@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Pemasukan & Pengeluaran RT</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Daftar Transaksi</li>
        </ol>
    </nav>
    
    <div class="alert alert-info mb-3">
        <i class="fa fa-info-circle"></i> 
        <strong>Informasi Keuangan RT:</strong> 
        Kelola pemasukan dan pengeluaran RT untuk transparansi keuangan. Catat semua transaksi keuangan dengan detail tanggal, jumlah, dan bukti transaksi.
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-6 col-md-4 mb-3 mb-md-0">
            <div class="app-summary-card app-summary-card-success app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-arrow-down"></i></span>
                <div>
                    <div class="app-summary-label">Total Pemasukan</div>
                    <div class="app-summary-value">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 mb-3 mb-md-0">
            <div class="app-summary-card app-summary-card-danger app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-arrow-up"></i></span>
                <div>
                    <div class="app-summary-label">Total Pengeluaran</div>
                    <div class="app-summary-value">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="app-summary-card {{ $balance >= 0 ? 'app-summary-card-primary' : 'app-summary-card-warning' }} app-summary-card-compact">
                <span class="icon-chip icon-chip-lg"><i class="fa fa-balance-scale"></i></span>
                <div>
                    <div class="app-summary-label">Saldo</div>
                    <div class="app-summary-value">Rp {{ number_format($balance, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <form method="GET" action="{{ url('/admin/rt-transaction') }}" class="mb-3">
                <div class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                    <input type="text" name="q" class="form-control" style="flex: 1 1 160px; width: auto;" placeholder="Cari transaksi" value="{{ request('q') }}">
                    <select name="type" class="form-control" style="width: 160px;">
                        <option value="">Semua Jenis</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                    </select>
                    <input type="date" name="start_date" class="form-control" style="width: 165px;" placeholder="Tanggal Mulai" value="{{ request('start_date') }}">
                    <input type="date" name="end_date" class="form-control" style="width: 165px;" placeholder="Tanggal Akhir" value="{{ request('end_date') }}">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    @if(request('q') || request('type') || request('start_date') || request('end_date'))
                        <a href="{{ url('/admin/rt-transaction') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
            <div class="d-flex flex-wrap" style="gap: .5rem;">
                <a href="{{ url('/admin/rt-transaction/create') }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> Tambah Transaksi
                </a>
                <a href="{{ url('/admin/rt-transaction/download') }}?q={{ request('q') }}&type={{ request('type') }}&start_date={{ request('start_date') }}&end_date={{ request('end_date') }}" class="btn btn-info">
                    <i class="fa fa-download"></i> Download Laporan
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

            @if($transactions->count() > 0)
            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($transactions as $index => $transaction)
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <strong>{{ $transaction->name }}</strong>
                        @if($transaction->isIncome())
                            <span class="badge badge-success">Pemasukan</span>
                        @else
                            <span class="badge badge-danger">Pengeluaran</span>
                        @endif
                    </div>
                    <div class="text-muted small mb-2">
                        {{ $transaction->payment_date->format('d/m/Y') }} &middot; {{ $transaction->created_by ?? 'Sistem' }}
                    </div>
                    <div class="mb-2">
                        @if($transaction->isIncome())
                            <span class="text-success font-weight-bold">+ Rp {{ number_format($transaction->credit, 0, ',', '.') }}</span>
                        @else
                            <span class="text-danger font-weight-bold">- Rp {{ number_format($transaction->debit, 0, ',', '.') }}</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap" style="gap: .4rem;">
                        <a href="{{ url('/admin/rt-transaction/' . $transaction->id) }}" class="btn btn-sm btn-info">
                            <i class="fa fa-eye"></i> Lihat
                        </a>
                        @if(strtolower($transaction->created_by ?? '') !== 'system')
                            <a href="{{ url('/admin/rt-transaction/' . $transaction->id . '/edit') }}" class="btn btn-sm btn-warning">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <button type="button" class="btn btn-sm btn-danger btn-delete-transaction" data-id="{{ $transaction->id }}" data-name="{{ $transaction->name }}">
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-secondary" disabled>
                                <i class="fa fa-lock"></i> Transaksi Sistem
                            </button>
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
                            <th width="12%">Tanggal</th>
                            <th width="10%">Jenis</th>
                            <th width="28%">Nama</th>
                            <th width="18%">Jumlah</th>
                            <th width="15%">Dibuat Oleh</th>
                            <th width="12%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $index => $transaction)
                        <tr>
                            <td>{{ $transactions->firstItem() + $index }}</td>
                            <td>{{ $transaction->payment_date->format('d/m/Y') }}</td>
                            <td>
                                @if($transaction->isIncome())
                                    <span class="badge badge-success">Pemasukan</span>
                                @else
                                    <span class="badge badge-danger">Pengeluaran</span>
                                @endif
                            </td>
                            <td>{{ $transaction->name }}</td>
                            <td>
                                @if($transaction->isIncome())
                                    <span class="text-success">+ Rp {{ number_format($transaction->credit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-danger">- Rp {{ number_format($transaction->debit, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td>{{ $transaction->created_by ?? 'Sistem' }}</td>
                            <td class="text-nowrap">
                                <a href="{{ url('/admin/rt-transaction/' . $transaction->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                    <i class="fa fa-eye"></i>
                                </a>
                                @if(strtolower($transaction->created_by ?? '') !== 'system')
                                    <a href="{{ url('/admin/rt-transaction/' . $transaction->id . '/edit') }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete-transaction" data-id="{{ $transaction->id }}" data-name="{{ $transaction->name }}" title="Hapus">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-secondary" disabled title="Tidak dapat diedit (Transaksi Sistem)">
                                        <i class="fa fa-lock"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="text-muted small mb-2 mb-sm-0">
                        Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} results
                    </div>
                    <div>
                        @if ($transactions->onFirstPage())
                            <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                        @else
                            <a href="{{ $transactions->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                        @endif

                        @if ($transactions->hasMorePages())
                            <a href="{{ $transactions->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
                        @else
                            <span class="btn btn-sm btn-light disabled">Next »</span>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                Tidak ada data transaksi.
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
                    <p>Apakah Anda yakin ingin menghapus transaksi ini?</p>
                    <p class="mb-0"><strong id="transaction-name"></strong></p>
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
    let transactionIdToDelete = null;

    // Handle delete button clicks
    $('.btn-delete-transaction').on('click', function() {
        transactionIdToDelete = $(this).data('id');
        const transactionName = $(this).data('name');
        
        $('#transaction-name').text(transactionName);
        $('#deleteModal').modal('show');
    });

    // Handle confirm delete button
    $('#confirmDelete').on('click', function() {
        if (transactionIdToDelete) {
            fetch('/admin/rt-transaction/' + transactionIdToDelete, {
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
