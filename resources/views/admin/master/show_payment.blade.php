@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Channel Pembayaran</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/master/payment') }}">Daftar Channel Pembayaran</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detail Pembayaran</li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-credit-card"></i></span>
            <span class="app-card-title">Detail Channel Pembayaran</span>
            <a href="{{ url('/admin/master/payment/edit/' . $payment->id) }}" class="btn btn-sm btn-warning">
                <i class="fa fa-edit"></i> Edit
            </a>
            <a href="{{ url('/admin/master/payment') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <!-- Mobile: stacked label/value rows -->
                    <div class="d-md-none">
                        <div class="mb-3">
                            <div class="text-muted small">Nama Channel</div>
                            <div class="font-weight-bold">{{ $payment->name }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Jenis Pembayaran</div>
                            <div>
                                @if($payment->payment_type == 'bank_account')
                                    <span class="badge badge-info">Rekening Bank</span>
                                @elseif($payment->payment_type == 'qr_payment')
                                    <span class="badge badge-success">QRIS Code</span>
                                @else
                                    {{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}
                                @endif
                            </div>
                        </div>
                        @if($payment->payment_type == 'bank_account' && $payment->metadata)
                        <div class="mb-3">
                            <div class="text-muted small">Detail Rekening</div>
                            <div>
                                @if(isset($payment->metadata['bank_name']))
                                    <strong>Bank:</strong> {{ $payment->metadata['bank_name'] }}<br>
                                @endif
                                @if(isset($payment->metadata['account_name']))
                                    <strong>Nama Pemilik:</strong> {{ $payment->metadata['account_name'] }}<br>
                                @endif
                                @if(isset($payment->metadata['account_number']))
                                    <strong>Nomor Rekening:</strong> {{ $payment->metadata['account_number'] }}
                                @endif
                            </div>
                        </div>
                        @elseif($payment->payment_type == 'qr_payment' && $payment->metadata)
                        <div class="mb-3">
                            <div class="text-muted small mb-1">QR Code</div>
                            <div>
                                @if(isset($payment->metadata['qr_image_path']))
                                    <div class="mb-2">
                                        <img src="{{ route('payment.qr.image', $payment->id) }}"
                                             alt="QR Code"
                                             style="max-width: 100%; border: 1px solid #ddd; padding: 10px; background: white;">
                                    </div>
                                    <a href="{{ route('payment.qr.image', $payment->id) }}"
                                       download="qr_{{ $payment->name }}.png"
                                       class="btn btn-sm btn-primary">
                                        <i class="fa fa-download"></i> Download QR
                                    </a>
                                @else
                                    <span class="text-muted">QR Code tidak tersedia</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Dynamic QRIS</div>
                            <div>
                                @if(isset($payment->metadata['is_dynamic_qris']) && $payment->metadata['is_dynamic_qris'])
                                    <span class="badge badge-success">Ya</span>
                                    <div class="mt-2">
                                        <strong>Tipe Fee:</strong>
                                        {{ $payment->metadata['fee_type'] == 'percent' ? 'Persen (%)' : 'Rupiah (Rp)' }}<br>
                                        <strong>Nilai Fee:</strong>
                                        @if($payment->metadata['fee_type'] == 'percent')
                                            {{ $payment->metadata['fee_value'] }}%
                                        @else
                                            Rp {{ number_format($payment->metadata['fee_value'], 0, ',', '.') }}
                                        @endif
                                    </div>
                                @else
                                    <span class="badge badge-secondary">Tidak</span>
                                @endif
                            </div>
                        </div>
                        @endif
                        <div class="mb-3">
                            <div class="text-muted small">Deskripsi</div>
                            <div>{{ $payment->description ?? '-' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Status</div>
                            <div>
                                @if(!$payment->disabled)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Tidak Aktif</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Dibuat Pada</div>
                            <div>{{ $payment->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Terakhir Diupdate</div>
                            <div>{{ $payment->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>

                    <!-- Tablet/desktop: bordered table -->
                    <table class="table table-bordered d-none d-md-table">
                        <tr>
                            <th width="30%">Nama Channel</th>
                            <td>{{ $payment->name }}</td>
                        </tr>
                        <tr>
                            <th>Jenis Pembayaran</th>
                            <td>
                                @if($payment->payment_type == 'bank_account')
                                    <span class="badge badge-info">Rekening Bank</span>
                                @elseif($payment->payment_type == 'qr_payment')
                                    <span class="badge badge-success">QRIS Code</span>
                                @else
                                    {{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}
                                @endif
                            </td>
                        </tr>
                        @if($payment->payment_type == 'bank_account' && $payment->metadata)
                        <tr>
                            <th>Detail Rekening</th>
                            <td>
                                @if(isset($payment->metadata['bank_name']))
                                    <strong>Bank:</strong> {{ $payment->metadata['bank_name'] }}<br>
                                @endif
                                @if(isset($payment->metadata['account_name']))
                                    <strong>Nama Pemilik:</strong> {{ $payment->metadata['account_name'] }}<br>
                                @endif
                                @if(isset($payment->metadata['account_number']))
                                    <strong>Nomor Rekening:</strong> {{ $payment->metadata['account_number'] }}
                                @endif
                            </td>
                        </tr>
                        @elseif($payment->payment_type == 'qr_payment' && $payment->metadata)
                        <tr>
                            <th>QR Code</th>
                            <td>
                                @if(isset($payment->metadata['qr_image_path']))
                                    <div class="mb-2">
                                        <img src="{{ route('payment.qr.image', $payment->id) }}"
                                             alt="QR Code"
                                             style="max-width: 300px; border: 1px solid #ddd; padding: 10px; background: white;">
                                    </div>
                                    <a href="{{ route('payment.qr.image', $payment->id) }}"
                                       download="qr_{{ $payment->name }}.png"
                                       class="btn btn-sm btn-primary">
                                        <i class="fa fa-download"></i> Download QR
                                    </a>
                                @else
                                    <span class="text-muted">QR Code tidak tersedia</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Dynamic QRIS</th>
                            <td>
                                @if(isset($payment->metadata['is_dynamic_qris']) && $payment->metadata['is_dynamic_qris'])
                                    <span class="badge badge-success">Ya</span>
                                    <div class="mt-2">
                                        <strong>Tipe Fee:</strong>
                                        {{ $payment->metadata['fee_type'] == 'percent' ? 'Persen (%)' : 'Rupiah (Rp)' }}<br>
                                        <strong>Nilai Fee:</strong>
                                        @if($payment->metadata['fee_type'] == 'percent')
                                            {{ $payment->metadata['fee_value'] }}%
                                        @else
                                            Rp {{ number_format($payment->metadata['fee_value'], 0, ',', '.') }}
                                        @endif
                                    </div>
                                @else
                                    <span class="badge badge-secondary">Tidak</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Deskripsi</th>
                            <td>{{ $payment->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if(!$payment->disabled)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Dibuat Pada</th>
                            <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Terakhir Diupdate</th>
                            <td>{{ $payment->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($payment->logs()->count() > 0)
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="d-flex align-items-center mb-2" style="gap: .5rem;">
                        <span class="icon-chip icon-chip-info"><i class="fa fa-history"></i></span>
                        <h5 class="mb-0">Riwayat Aktivitas Terakhir</h5>
                    </div>

                    <!-- Mobile: stacked cards -->
                    <div class="d-md-none border rounded mb-3">
                        <div class="list-group list-group-flush">
                            @foreach($payment->logs()->orderBy('created_at', 'desc')->limit(10)->get() as $log)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong class="small">{{ $log->username }}</strong>
                                    <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                                <div class="small">{!! nl2br(e($log->description)) !!}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tablet/desktop: table -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th width="20%">Tanggal</th>
                                    <th width="20%">User</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payment->logs()->orderBy('created_at', 'desc')->limit(10)->get() as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $log->username }}</td>
                                    <td>{!! nl2br(e($log->description)) !!}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
