@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Tagihan {{ $user->name }} ({{ $user->blok }})</h1>
    @include('admin.master._header_warga_detail', ['active' => 'tagihan', 'userId' => $user->id])

    {{-- Current Month Tagihan --}}
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-calendar"></i></span>
            <span class="app-card-title">Tagihan Bulan Ini ({{ date('F Y') }})</span>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if($currentTagihan->isEmpty())
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Tidak ada tagihan untuk bulan ini.
                </div>
            @else
                <!-- Mobile: stacked cards -->
                <div class="d-md-none list-group list-group-flush mb-3">
                    @foreach($currentTagihan as $index => $item)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong>{{ $item->billing_name }}</strong>
                            @if($item->payment_amount >= $item->billing_amount)
                                <span class="badge badge-success">{{ number_format($item->payment_amount, 0, ',', '.') }}</span>
                            @elseif($item->payment_amount > 0)
                                <span class="badge badge-warning">Sebagian</span>
                            @else
                                <span class="badge badge-danger">Belum Bayar</span>
                            @endif
                        </div>
                        <div class="text-muted small mb-2">{{ $item->billing_generated_date->format('d M Y') }}</div>
                        <div class="mb-2">Rp {{ number_format($item->billing_amount, 0, ',', '.') }}</div>
                        <div class="d-flex flex-wrap" style="gap: .4rem;">
                            @if($item->payment_amount < $item->billing_amount && $leftoverDeposit > 0)
                                <form id="deposit-form-current-m-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/bayar-dari-deposit') }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="tagihan_id" value="{{ $item->id }}">
                                </form>
                                <button type="button" class="btn btn-success btn-sm btn-bayar-deposit"
                                    data-form-id="deposit-form-current-m-{{ $item->id }}"
                                    data-tagihan-name="{{ $item->billing_name }}"
                                    data-billing-amount="{{ $item->billing_amount }}"
                                    data-payment-amount="{{ $item->payment_amount }}"
                                    data-sisa="{{ $item->billing_amount - $item->payment_amount }}"
                                    data-leftover-deposit="{{ $leftoverDeposit }}">
                                    <i class="fa fa-money"></i> Bayar dari Deposit
                                </button>
                            @endif
                            @if($item->payment_amount <= 0)
                                <button type="button" class="btn btn-primary btn-sm btn-edit-tagihan"
                                    data-tagihan-id="{{ $item->id }}"
                                    data-tagihan-name="{{ $item->billing_name }}"
                                    data-billing-amount="{{ $item->billing_amount }}">
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                                <form id="delete-form-current-m-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/' . $item->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" class="btn btn-danger btn-sm btn-delete-tagihan" data-form-id="delete-form-current-m-{{ $item->id }}" data-tagihan-name="{{ $item->billing_name }}">
                                    <i class="fa fa-trash"></i> Hapus
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm" disabled>
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" disabled>
                                    <i class="fa fa-trash"></i> Hapus
                                </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    <div class="list-group-item px-0 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Total: Rp {{ number_format($currentTagihan->sum('billing_amount'), 0, ',', '.') }}</strong>
                            @if($currentTagihan->sum('payment_amount') >= $currentTagihan->sum('billing_amount'))
                                <span class="badge badge-success"><i class="fa fa-check-circle"></i> Lunas</span>
                            @elseif($currentTagihan->sum('payment_amount') > 0)
                                <span class="badge badge-warning">Sebagian</span>
                            @else
                                <span class="badge badge-danger">Belum Bayar</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tablet/desktop: table -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="12%">Tanggal</th>
                                <th width="25%">Nama Tagihan</th>
                                <th width="13%">Jumlah</th>
                                <th width="13%">Terbayar</th>
                                <th width="12%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($currentTagihan as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->billing_generated_date->format('d M Y') }}</td>
                                    <td>{{ $item->billing_name }}</td>
                                    <td class="text-right">Rp {{ number_format($item->billing_amount, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if($item->payment_amount >= $item->billing_amount)
                                            <span class="badge badge-success">{{ number_format($item->payment_amount, 0, ',', '.') }}</span>
                                        @elseif($item->payment_amount > 0)
                                            <span class="badge badge-warning">Sebagian (Rp.{{ number_format($item->payment_amount, 0, ',', '.') }})</span>
                                        @else
                                            <span class="badge badge-danger">Belum Bayar</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->payment_amount < $item->billing_amount && $leftoverDeposit > 0)
                                            <form id="deposit-form-current-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/bayar-dari-deposit') }}" method="POST" style="display: inline;">
                                                @csrf
                                                <input type="hidden" name="tagihan_id" value="{{ $item->id }}">
                                            </form>
                                            <button type="button" class="btn btn-success btn-sm btn-bayar-deposit"
                                                data-form-id="deposit-form-current-{{ $item->id }}"
                                                data-tagihan-name="{{ $item->billing_name }}"
                                                data-billing-amount="{{ $item->billing_amount }}"
                                                data-payment-amount="{{ $item->payment_amount }}"
                                                data-sisa="{{ $item->billing_amount - $item->payment_amount }}"
                                                data-leftover-deposit="{{ $leftoverDeposit }}"
                                                title="Bayar dari Deposit">
                                                <i class="fa fa-money"></i>
                                            </button>
                                        @endif

                                        @if($item->payment_amount <= 0)
                                            <button type="button" class="btn btn-primary btn-sm btn-edit-tagihan"
                                                data-tagihan-id="{{ $item->id }}"
                                                data-tagihan-name="{{ $item->billing_name }}"
                                                data-billing-amount="{{ $item->billing_amount }}"
                                                title="Edit Tagihan">
                                                <i class="fa fa-edit"></i>
                                            </button>

                                            <form id="delete-form-current-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/' . $item->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-tagihan" data-form-id="delete-form-current-{{ $item->id }}" data-tagihan-name="{{ $item->billing_name }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-secondary btn-sm" disabled title="Tidak dapat diedit karena sudah ada pembayaran">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" disabled title="Tidak dapat dihapus karena sudah ada pembayaran">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Total:</th>
                                <th class="text-right">Rp {{ number_format($currentTagihan->sum('billing_amount'), 0, ',', '.') }}</th>
                                <th class="text-center">
                                    @if($currentTagihan->sum('payment_amount') >= $currentTagihan->sum('billing_amount'))
                                        <span class="badge badge-success"><i class="fa fa-check-circle"></i> Lunas</span>
                                    @elseif($currentTagihan->sum('payment_amount') > 0)
                                        <span class="badge badge-warning">Sebagian (Rp.{{ number_format($currentTagihan->sum('payment_amount'), 0, ',', '.') }})</span>
                                    @else
                                        <span class="badge badge-danger">Belum Bayar</span>
                                    @endif
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Manual Billing --}}
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-info"><i class="fa fa-file-invoice"></i></span>
            <span class="app-card-title">Tagihan Manual</span>
        </div>
        <div class="card-body">
            @if($manualBillings->isEmpty())
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Tidak ada tagihan manual.
                </div>
            @else
                <!-- Mobile: stacked cards -->
                <div class="d-md-none list-group list-group-flush mb-3">
                    @foreach($manualBillings as $index => $item)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong>{{ $item->billing_name }}</strong>
                            @if($item->payment_amount >= $item->billing_amount)
                                <span class="badge badge-success">{{ number_format($item->payment_amount, 0, ',', '.') }}</span>
                            @elseif($item->payment_amount > 0)
                                <span class="badge badge-warning">Sebagian</span>
                            @else
                                <span class="badge badge-danger">Belum Bayar</span>
                            @endif
                        </div>
                        <div class="text-muted small mb-2">{{ $item->billing_generated_date->format('d M Y') }}</div>
                        <div class="mb-1">Tagihan: Rp {{ number_format($item->billing_amount, 0, ',', '.') }}</div>
                        <div class="mb-1">
                            Terbayar:
                            @if($item->payment_amount >= $item->billing_amount)
                                Lunas
                            @elseif($item->payment_amount > 0)
                                Rp {{ number_format($item->payment_amount, 0, ',', '.') }}
                            @else
                                Belum Bayar
                            @endif
                        </div>
                        <div class="mb-2 text-danger"><strong>Sisa: Rp {{ number_format($item->billing_amount - $item->payment_amount, 0, ',', '.') }}</strong></div>
                        <div class="d-flex flex-wrap" style="gap: .4rem;">
                            @if($item->payment_amount < $item->billing_amount && $leftoverDeposit > 0)
                                <form id="deposit-form-manual-m-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/bayar-dari-deposit') }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="tagihan_id" value="{{ $item->id }}">
                                </form>
                                <button type="button" class="btn btn-success btn-sm btn-bayar-deposit"
                                    data-form-id="deposit-form-manual-m-{{ $item->id }}"
                                    data-tagihan-name="{{ $item->billing_name }}"
                                    data-billing-amount="{{ $item->billing_amount }}"
                                    data-payment-amount="{{ $item->payment_amount }}"
                                    data-sisa="{{ $item->billing_amount - $item->payment_amount }}"
                                    data-leftover-deposit="{{ $leftoverDeposit }}">
                                    <i class="fa fa-money"></i> Bayar dari Deposit
                                </button>
                            @endif
                            @if($item->payment_amount <= 0)
                                <button type="button" class="btn btn-primary btn-sm btn-edit-tagihan"
                                    data-tagihan-id="{{ $item->id }}"
                                    data-tagihan-name="{{ $item->billing_name }}"
                                    data-billing-amount="{{ $item->billing_amount }}">
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                                <form id="delete-form-manual-m-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/' . $item->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" class="btn btn-danger btn-sm btn-delete-tagihan" data-form-id="delete-form-manual-m-{{ $item->id }}" data-tagihan-name="{{ $item->billing_name }}">
                                    <i class="fa fa-trash"></i> Hapus
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm" disabled>
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" disabled>
                                    <i class="fa fa-trash"></i> Hapus
                                </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    <div class="list-group-item px-0 bg-light">
                        <div>Total Tagihan: Rp {{ number_format($manualBillings->sum('billing_amount'), 0, ',', '.') }}</div>
                        <div>Total Terbayar: Rp {{ number_format($manualBillings->sum('payment_amount'), 0, ',', '.') }}</div>
                        <div class="text-danger"><strong>Total Sisa: Rp {{ number_format($manualBillings->sum('billing_amount') - $manualBillings->sum('payment_amount'), 0, ',', '.') }}</strong></div>
                    </div>
                </div>

                <!-- Tablet/desktop: table -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="12%">Tanggal</th>
                                <th width="25%">Nama Tagihan</th>
                                <th width="13%">Jumlah</th>
                                <th width="13%">Terbayar</th>
                                <th width="12%">Sisa</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($manualBillings as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->billing_generated_date->format('d M Y') }}</td>
                                    <td>{{ $item->billing_name }}</td>
                                    <td class="text-right">Rp {{ number_format($item->billing_amount, 0, ',', '.') }}</td>
                                    <td class="text-right">
                                        @if($item->payment_amount >= $item->billing_amount)
                                            <span class="badge badge-success">Lunas</span>
                                        @elseif($item->payment_amount > 0)
                                            Rp {{ number_format($item->payment_amount, 0, ',', '.') }}
                                        @else
                                            <span class="badge badge-danger">Belum Bayar</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-danger">
                                        <strong>Rp {{ number_format($item->billing_amount - $item->payment_amount, 0, ',', '.') }}</strong>
                                    </td>
                                    <td>
                                        @if($item->payment_amount < $item->billing_amount && $leftoverDeposit > 0)
                                            <form id="deposit-form-manual-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/bayar-dari-deposit') }}" method="POST" style="display: inline;">
                                                @csrf
                                                <input type="hidden" name="tagihan_id" value="{{ $item->id }}">
                                            </form>
                                            <button type="button" class="btn btn-success btn-sm btn-bayar-deposit"
                                                data-form-id="deposit-form-manual-{{ $item->id }}"
                                                data-tagihan-name="{{ $item->billing_name }}"
                                                data-billing-amount="{{ $item->billing_amount }}"
                                                data-payment-amount="{{ $item->payment_amount }}"
                                                data-sisa="{{ $item->billing_amount - $item->payment_amount }}"
                                                data-leftover-deposit="{{ $leftoverDeposit }}"
                                                title="Bayar dari Deposit">
                                                <i class="fa fa-money"></i>
                                            </button>
                                        @endif

                                        @if($item->payment_amount <= 0)
                                            <button type="button" class="btn btn-primary btn-sm btn-edit-tagihan"
                                                data-tagihan-id="{{ $item->id }}"
                                                data-tagihan-name="{{ $item->billing_name }}"
                                                data-billing-amount="{{ $item->billing_amount }}"
                                                title="Edit Tagihan">
                                                <i class="fa fa-edit"></i>
                                            </button>

                                            <form id="delete-form-manual-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/' . $item->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-tagihan" data-form-id="delete-form-manual-{{ $item->id }}" data-tagihan-name="{{ $item->billing_name }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-secondary btn-sm" disabled title="Tidak dapat diedit karena sudah ada pembayaran">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" disabled title="Tidak dapat dihapus karena sudah ada pembayaran">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <th colspan="3" class="text-right">Total:</th>
                                <th class="text-right">Rp {{ number_format($manualBillings->sum('billing_amount'), 0, ',', '.') }}</th>
                                <th class="text-right">Rp {{ number_format($manualBillings->sum('payment_amount'), 0, ',', '.') }}</th>
                                <th class="text-right text-danger">
                                    <strong>Rp {{ number_format($manualBillings->sum('billing_amount') - $manualBillings->sum('payment_amount'), 0, ',', '.') }}</strong>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Tunggakan Iuran --}}
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-warning"><i class="fa fa-exclamation-triangle"></i></span>
            <span class="app-card-title">Tunggakan Iuran (Bulan Sebelumnya)</span>
        </div>
        <div class="card-body">
            @if($tunggakan->isEmpty())
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> Tidak ada tunggakan.
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i> <strong>Perhatian:</strong> Terdapat {{ $tunggakan->count() }} tunggakan yang belum lunas.
                </div>
                <!-- Mobile: stacked cards -->
                <div class="d-md-none list-group list-group-flush mb-3">
                    @foreach($tunggakan as $index => $item)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong>{{ $item->billing_name }}</strong>
                            @if($item->billing_type == 'rutin_perbulan')
                                <span class="badge badge-primary">Rutin</span>
                            @else
                                <span class="badge badge-warning">Tunggakan</span>
                            @endif
                        </div>
                        <div class="text-muted small mb-2">{{ $item->billing_generated_date->format('d M Y') }}</div>
                        <div class="mb-1">Tagihan: Rp {{ number_format($item->billing_amount, 0, ',', '.') }}</div>
                        <div class="mb-1">Terbayar: Rp {{ number_format($item->payment_amount, 0, ',', '.') }}</div>
                        <div class="mb-2 text-danger"><strong>Sisa: Rp {{ number_format($item->billing_amount - $item->payment_amount, 0, ',', '.') }}</strong></div>
                        <div class="d-flex flex-wrap" style="gap: .4rem;">
                            @if($item->payment_amount < $item->billing_amount && $leftoverDeposit > 0)
                                <form id="deposit-form-tunggakan-m-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/bayar-dari-deposit') }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="tagihan_id" value="{{ $item->id }}">
                                </form>
                                <button type="button" class="btn btn-success btn-sm btn-bayar-deposit"
                                    data-form-id="deposit-form-tunggakan-m-{{ $item->id }}"
                                    data-tagihan-name="{{ $item->billing_name }}"
                                    data-billing-amount="{{ $item->billing_amount }}"
                                    data-payment-amount="{{ $item->payment_amount }}"
                                    data-sisa="{{ $item->billing_amount - $item->payment_amount }}"
                                    data-leftover-deposit="{{ $leftoverDeposit }}">
                                    <i class="fa fa-money"></i> Bayar dari Deposit
                                </button>
                            @endif
                            @if($item->payment_amount <= 0)
                                <button type="button" class="btn btn-primary btn-sm btn-edit-tagihan"
                                    data-tagihan-id="{{ $item->id }}"
                                    data-tagihan-name="{{ $item->billing_name }}"
                                    data-billing-amount="{{ $item->billing_amount }}">
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                                <form id="delete-form-tunggakan-m-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/' . $item->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" class="btn btn-danger btn-sm btn-delete-tagihan" data-form-id="delete-form-tunggakan-m-{{ $item->id }}" data-tagihan-name="{{ $item->billing_name }}">
                                    <i class="fa fa-trash"></i> Hapus
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm" disabled>
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" disabled>
                                    <i class="fa fa-trash"></i> Hapus
                                </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    <div class="list-group-item px-0 bg-light">
                        <div>Total Tagihan: Rp {{ number_format($tunggakanTotal['billing_amount'], 0, ',', '.') }}</div>
                        <div>Total Terbayar: Rp {{ number_format($tunggakanTotal['payment_amount'], 0, ',', '.') }}</div>
                        <div class="text-danger"><strong>Total Sisa: Rp {{ number_format($tunggakanTotal['sisa'], 0, ',', '.') }}</strong></div>
                    </div>
                </div>

                <!-- Tablet/desktop: table -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="12%">Tanggal</th>
                                <th width="23%">Nama Tagihan</th>
                                <th width="8%">Tipe</th>
                                <th width="13%">Jumlah</th>
                                <th width="13%">Terbayar</th>
                                <th width="10%">Sisa</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tunggakan as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->billing_generated_date->format('d M Y') }}</td>
                                    <td>{{ $item->billing_name }}</td>
                                    <td>
                                        @if($item->billing_type == 'rutin_perbulan')
                                            <span class="badge badge-primary">Rutin</span>
                                        @elseif($item->billing_type == 'manual')
                                            <span class="badge badge-info">Manual</span>
                                        @else
                                            <span class="badge badge-warning">Tunggakan</span>
                                        @endif
                                    </td>
                                    <td class="text-right">Rp {{ number_format($item->billing_amount, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($item->payment_amount, 0, ',', '.') }}</td>
                                    <td class="text-right text-danger">
                                        <strong>Rp {{ number_format($item->billing_amount - $item->payment_amount, 0, ',', '.') }}</strong>
                                    </td>
                                    <td>
                                        @if($item->payment_amount < $item->billing_amount && $leftoverDeposit > 0)
                                            <form id="deposit-form-tunggakan-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/bayar-dari-deposit') }}" method="POST" style="display: inline;">
                                                @csrf
                                                <input type="hidden" name="tagihan_id" value="{{ $item->id }}">
                                            </form>
                                            <button type="button" class="btn btn-success btn-sm btn-bayar-deposit"
                                                data-form-id="deposit-form-tunggakan-{{ $item->id }}"
                                                data-tagihan-name="{{ $item->billing_name }}"
                                                data-billing-amount="{{ $item->billing_amount }}"
                                                data-payment-amount="{{ $item->payment_amount }}"
                                                data-sisa="{{ $item->billing_amount - $item->payment_amount }}"
                                                data-leftover-deposit="{{ $leftoverDeposit }}"
                                                title="Bayar dari Deposit">
                                                <i class="fa fa-money"></i>
                                            </button>
                                        @endif

                                        @if($item->payment_amount <= 0)
                                            <button type="button" class="btn btn-primary btn-sm btn-edit-tagihan"
                                                data-tagihan-id="{{ $item->id }}"
                                                data-tagihan-name="{{ $item->billing_name }}"
                                                data-billing-amount="{{ $item->billing_amount }}"
                                                title="Edit Tagihan">
                                                <i class="fa fa-edit"></i>
                                            </button>

                                            <form id="delete-form-tunggakan-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tagihan/' . $item->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-tagihan" data-form-id="delete-form-tunggakan-{{ $item->id }}" data-tagihan-name="{{ $item->billing_name }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-secondary btn-sm" disabled title="Tidak dapat diedit karena sudah ada pembayaran">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" disabled title="Tidak dapat dihapus karena sudah ada pembayaran">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <th colspan="4" class="text-right">Total Keseluruhan:</th>
                                <th class="text-right">Rp {{ number_format($tunggakanTotal['billing_amount'], 0, ',', '.') }}</th>
                                <th class="text-right">Rp {{ number_format($tunggakanTotal['payment_amount'], 0, ',', '.') }}</th>
                                <th class="text-right text-danger">
                                    <strong>Rp {{ number_format($tunggakanTotal['sisa'], 0, ',', '.') }}</strong>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="text-muted small mb-2 mb-sm-0">
                            Showing {{ $tunggakan->firstItem() }} to {{ $tunggakan->lastItem() }} of {{ $tunggakan->total() }} results
                        </div>
                        <div>
                            @if ($tunggakan->onFirstPage())
                                <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                            @else
                                <a href="{{ $tunggakan->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                            @endif

                            @if ($tunggakan->hasMorePages())
                                <a href="{{ $tunggakan->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
                            @else
                                <span class="btn btn-sm btn-light disabled">Next »</span>
                            @endif
                        </div>
                    </div>
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
                    <p>Apakah Anda yakin ingin menghapus tagihan ini?</p>
                    <p class="mb-0"><strong id="tagihan-name"></strong></p>
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

    <!-- Edit Tagihan Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="edit-form" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editModalLabel">
                            <i class="fa fa-edit"></i> Edit Tagihan
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label><strong>Nama Tagihan:</strong></label>
                            <p id="edit-tagihan-name" class="form-control-plaintext"></p>
                        </div>
                        <div class="form-group">
                            <label for="edit-billing-amount"><strong>Jumlah Tagihan: <span class="text-danger">*</span></strong></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="number" 
                                    class="form-control" 
                                    id="edit-billing-amount" 
                                    name="billing_amount" 
                                    min="0" 
                                    step="1" 
                                    required>
                            </div>
                            <small class="form-text text-muted">Jumlah tagihan sebelumnya: <span id="edit-old-amount"></span></small>
                        </div>
                        <div class="form-group">
                            <label for="edit-note"><strong>Alasan Perubahan: <span class="text-danger">*</span></strong></label>
                            <textarea 
                                class="form-control" 
                                id="edit-note" 
                                name="edit_note" 
                                rows="3" 
                                maxlength="500" 
                                placeholder="Jelaskan alasan mengapa jumlah tagihan diubah..."
                                required></textarea>
                            <small class="form-text text-muted"><span id="char-count">0</span>/500 karakter</small>
                        </div>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fa fa-exclamation-triangle"></i> Perubahan tagihan akan dicatat dalam log aktivitas.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fa fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bayar from Deposit Confirmation Modal -->
    <div class="modal fade" id="depositModal" tabindex="-1" role="dialog" aria-labelledby="depositModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="depositModalLabel">
                        <i class="fa fa-money"></i> Konfirmasi Bayar dari Deposit
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin membayar tagihan ini dari deposit?</p>
                    <hr>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="40%"><strong>Nama Tagihan:</strong></td>
                            <td id="deposit-tagihan-name"></td>
                        </tr>
                        <tr>
                            <td><strong>Total Tagihan:</strong></td>
                            <td id="deposit-billing-amount" class="text-primary"></td>
                        </tr>
                        <tr>
                            <td><strong>Sudah Dibayar:</strong></td>
                            <td id="deposit-payment-amount"></td>
                        </tr>
                        <tr>
                            <td><strong>Sisa Tagihan:</strong></td>
                            <td id="deposit-sisa" class="text-danger font-weight-bold"></td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Saldo Deposit:</strong></td>
                            <td id="deposit-leftover" class="text-success font-weight-bold"></td>
                        </tr>
                    </table>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fa fa-info-circle"></i> Pembayaran akan menggunakan saldo deposit yang tersedia.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-success" id="confirmDeposit">
                        <i class="fa fa-check"></i> Bayar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let formToSubmit = null;

        // Handle delete button clicks
        document.querySelectorAll('.btn-delete-tagihan').forEach(function(button) {
            button.addEventListener('click', function() {
                const formId = this.getAttribute('data-form-id');
                const tagihanName = this.getAttribute('data-tagihan-name');
                
                formToSubmit = document.getElementById(formId);
                document.getElementById('tagihan-name').textContent = tagihanName;
                
                $('#deleteModal').modal('show');
            });
        });

        // Handle confirm delete button
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });

        // Handle edit button clicks
        document.querySelectorAll('.btn-edit-tagihan').forEach(function(button) {
            button.addEventListener('click', function() {
                const tagihanId = this.getAttribute('data-tagihan-id');
                const tagihanName = this.getAttribute('data-tagihan-name');
                const billingAmount = this.getAttribute('data-billing-amount');
                
                // Set form action
                const editForm = document.getElementById('edit-form');
                editForm.action = '{{ url('/admin/master/warga/' . $user->id . '/tagihan') }}/' + tagihanId;
                
                // Set form values
                document.getElementById('edit-tagihan-name').textContent = tagihanName;
                document.getElementById('edit-billing-amount').value = billingAmount;
                document.getElementById('edit-old-amount').textContent = 'Rp ' + parseFloat(billingAmount).toLocaleString('id-ID');
                document.getElementById('edit-note').value = '';
                document.getElementById('char-count').textContent = '0';
                
                $('#editModal').modal('show');
            });
        });

        // Handle character count for edit note
        document.getElementById('edit-note').addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('char-count').textContent = charCount;
        });

        // Handle bayar from deposit button clicks
        document.querySelectorAll('.btn-bayar-deposit').forEach(function(button) {
            button.addEventListener('click', function() {
                const formId = this.getAttribute('data-form-id');
                const tagihanName = this.getAttribute('data-tagihan-name');
                const billingAmount = parseFloat(this.getAttribute('data-billing-amount'));
                const paymentAmount = parseFloat(this.getAttribute('data-payment-amount'));
                const sisa = parseFloat(this.getAttribute('data-sisa'));
                const leftoverDeposit = parseFloat(this.getAttribute('data-leftover-deposit'));
                
                formToSubmit = document.getElementById(formId);
                document.getElementById('deposit-tagihan-name').textContent = tagihanName;
                document.getElementById('deposit-billing-amount').textContent = 'Rp ' + billingAmount.toLocaleString('id-ID');
                document.getElementById('deposit-payment-amount').textContent = 'Rp ' + paymentAmount.toLocaleString('id-ID');
                document.getElementById('deposit-sisa').textContent = 'Rp ' + sisa.toLocaleString('id-ID');
                document.getElementById('deposit-leftover').textContent = 'Rp ' + leftoverDeposit.toLocaleString('id-ID');
                
                $('#depositModal').modal('show');
            });
        });

        // Handle confirm deposit payment button
        document.getElementById('confirmDeposit').addEventListener('click', function() {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });
    </script>
@endsection
