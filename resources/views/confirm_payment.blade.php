@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title">Konfirmasi Pembayaran</h4>
        <p class="app-page-header-sub">Pilih jenis tagihan yang akan dibayar</p>
    </div>

    <!-- Payment Method Info -->
    @if($payment->payment_type != 'qr_payment')
    <div class="card border-0 shadow-sm mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-info"><i class="fa fa-info-circle"></i></span>
            <span class="app-card-title">Metode Pembayaran</span>
        </div>
        <div class="card-body">
            <h6 class="mb-2">{{ $payment->name }}</h6>
            @if($payment->payment_type == 'bank_account' && $payment->metadata)
                @php
                    $metadata = is_string($payment->metadata) ? json_decode($payment->metadata, true) : $payment->metadata;
                @endphp
                <div class="row">
                    <div class="col-4 text-muted small">Bank:</div>
                    <div class="col-8"><strong>{{ $metadata['bank_name'] ?? '-' }}</strong></div>
                </div>
                <div class="row">
                    <div class="col-4 text-muted small">Rekening:</div>
                    <div class="col-8"><strong>{{ $metadata['account_name'] ?? '-' }}</strong></div>
                </div>
                <div class="row">
                    <div class="col-4 text-muted small">No. Rekening:</div>
                    <div class="col-8"><strong class="text-primary">{{ $metadata['account_number'] ?? '-' }}</strong></div>
                </div>
            @endif
        </div>
    </div>
    @else
    <!-- QRIS Payment Info -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-success"><i class="fa fa-qrcode"></i></span>
            <span class="app-card-title">Pembayaran QRIS</span>
        </div>
        <div class="card-body">
            <h6 class="mb-2">{{ $payment->name }}</h6>
            <p class="text-muted small mb-3">
                <i class="fa fa-info-circle"></i> 
                QR Code akan ditampilkan setelah Anda memilih jenis pembayaran
            </p>
            @if($payment->metadata)
                @php
                    $metadata = is_string($payment->metadata) ? json_decode($payment->metadata, true) : $payment->metadata;
                @endphp
                @if(isset($metadata['is_dynamic_qris']) && $metadata['is_dynamic_qris'])
                <div class="alert alert-info mb-0" style="font-size: 0.9rem;">
                    <strong><i class="fa fa-calculator"></i> Informasi Biaya Admin:</strong><br>
                    Setiap pembayaran akan dikenakan biaya admin sebesar 
                    <strong>
                        @if($metadata['fee_type'] == 'percent')
                            {{ $metadata['fee_value'] }}%
                        @else
                            Rp {{ number_format($metadata['fee_value'], 0, ',', '.') }}
                        @endif
                    </strong>
                    <br>
                    <small class="text-muted">Biaya admin akan otomatis ditambahkan ke total pembayaran</small>
                </div>
                @endif
            @endif
        </div>
    </div>
    @endif

    <!-- Billing Options -->
    <div class="mb-3">
        <p class="text-muted">Pilih jenis tagihan yang akan dikonfirmasi:</p>
    </div>

    <!-- Option 1: Current Month Billing -->
    <div class="card mb-3 {{ $totalCurrentTagihan <= 0 ? 'border-secondary' : ($existingCurrentPayment ? 'border-info' : 'border-primary') }} shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="card-title mb-2">
                        <i class="fa fa-calendar"></i> Bayar tagihan bulan ini
                    </h6>
                    <p class="card-text mb-1">
                        <small class="text-muted">Iuran rutin {{ \Carbon\Carbon::now()->locale('id')->isoFormat('MMMM YYYY') }}</small>
                    </p>
                    <p class="card-text mb-0">
                        @if($totalCurrentTagihan > 0)
                        <strong class="text-{{ $totalCurrentTagihan > 0 ? 'danger' : 'success' }}">
                            Rp {{ number_format($totalCurrentTagihan, 0, ',', '.') }}
                        </strong>
                        @endif
                    </p>
                    @if($totalCurrentTagihan <= 0)
                        <small class="text-success"><i class="fa fa-check-circle"></i> Sudah lunas</small>
                    @elseif($existingCurrentPayment)
                        <small class="text-info">
                            <i class="fa fa-clock-o"></i> 
                            Konfirmasi sudah dibuat ({{ $existingCurrentPayment->created_at->format('d/m/Y H:i') }})
                            <br>
                            <span class="badge badge-{{ $existingCurrentPayment->status == 'verified' ? 'success' : ($existingCurrentPayment->status == 'rejected' ? 'danger' : 'warning') }}">
                                {{ $existingCurrentPayment->getStatusLabel() }}
                            </span>
                        </small>
                    @endif
                </div>
                <div>
                    @if($hasRemainingCurrentTagihan)
                        <a href="{{ url('/pembayaran/konfirmasi/form?payment_id=' . $payment->id . '&type=current_billing') }}" 
                           class="btn btn-primary">
                            <i class="fa fa-check"></i> Pilih
                        </a>
                    @else
                        <button class="btn btn-secondary" disabled>
                            <i class="fa fa-check"></i> Pilih
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Option 2: Manual Billing (Iuran Khusus) -->
    @if(!is_null($existingManualPayment) || $totalManualBilling > 0)
    <div class="card mb-4 {{ $totalManualBilling <= 0 ? 'border-secondary' : ($existingManualPayment ? 'border-info' : 'border-primary') }} shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="card-title mb-2">
                        <i class="fa fa-file-text"></i> Bayar iuran khusus
                    </h6>
                    <p class="card-text mb-1">
                        <small class="text-muted">Tagihan iuran khusus</small>
                    </p>
                    <p class="card-text mb-0">
                        @if($totalManualBilling > 0)
                        <strong class="text-{{ $totalManualBilling > 0 ? 'danger' : 'success' }}">
                            Rp {{ number_format($totalManualBilling, 0, ',', '.') }}
                        </strong>
                        @endif
                    </p>
                    @if($totalManualBilling <= 0)
                        <small class="text-success"><i class="fa fa-check-circle"></i> Sudah lunas</small>
                    @elseif($existingManualPayment)
                        <small class="text-info">
                            <i class="fa fa-clock-o"></i> 
                            Konfirmasi sudah dibuat ({{ $existingManualPayment->created_at->format('d/m/Y H:i') }})
                            <br>
                            <span class="badge badge-{{ $existingManualPayment->status == 'verified' ? 'success' : ($existingManualPayment->status == 'rejected' ? 'danger' : 'warning') }}">
                                {{ $existingManualPayment->getStatusLabel() }}
                            </span>
                        </small>
                    @endif
                </div>
                <div>
                    @if($hasRemainingManualBilling)
                        <a href="{{ url('/pembayaran/konfirmasi/form?payment_id=' . $payment->id . '&type=manual_billing') }}" 
                           class="btn btn-primary">
                            <i class="fa fa-check"></i> Pilih
                        </a>
                    @else
                        <button class="btn btn-secondary" disabled>
                            <i class="fa fa-check"></i> Pilih
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Option 3: Previous Billing (Tunggakan) -->
    <div class="card mb-4 {{ $totalTunggakan <= 0 ? 'border-secondary' : ($existingPreviousPayment ? 'border-info' : 'border-warning') }} shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="card-title mb-2">
                        <i class="fa fa-exclamation-triangle"></i> Bayar tunggakan
                    </h6>
                    <p class="card-text mb-1">
                        <small class="text-muted">Tagihan iuran rutin bulan sebelumnya yang belum lunas</small>
                    </p>
                    <p class="card-text mb-0">
                        @if($totalTunggakan > 0)
                        <strong class="text-{{ $totalTunggakan > 0 ? 'danger' : 'success' }}">
                            Rp {{ number_format($totalTunggakan, 0, ',', '.') }}
                        </strong>
                        @endif
                    </p>
                    @if($totalTunggakan <= 0)
                        <small class="text-success"><i class="fa fa-check-circle"></i> Tidak ada tunggakan</small>
                    @elseif($existingPreviousPayment)
                        <small class="text-info">
                            <i class="fa fa-clock-o"></i> 
                            Konfirmasi sudah dibuat ({{ $existingPreviousPayment->created_at->format('d/m/Y H:i') }})
                            <br>
                            <span class="badge badge-{{ $existingPreviousPayment->status == 'verified' ? 'success' : ($existingPreviousPayment->status == 'rejected' ? 'danger' : 'warning') }}">
                                {{ $existingPreviousPayment->getStatusLabel() }}
                            </span>
                        </small>
                    @endif
                </div>
                <div>
                    @if($hasRemainingTunggakan)
                        <a href="{{ url('/pembayaran/konfirmasi/form?payment_id=' . $payment->id . '&type=previous_billing') }}" 
                           class="btn btn-warning">
                            <i class="fa fa-check"></i> Pilih
                        </a>
                    @else
                        <button class="btn btn-secondary" disabled>
                            <i class="fa fa-check"></i> Pilih
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Option 4: Deposit -->
    <div class="card mb-4 {{ ($totalCurrentTagihan <= 0 && $totalTunggakan <= 0) ? 'border-success' : 'border-secondary' }} shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="card-title mb-2">
                        <i class="fa fa-plus-circle"></i> Deposit/Setoran
                    </h6>
                    <p class="card-text mb-1">
                        <small class="text-muted">Setor dana sesuai keinginan</small>
                    </p>
                    @if($totalCurrentTagihan <= 0 && $totalTunggakan <= 0 && !$existingPendingDeposit)
                        <p class="card-text mb-0">
                            <small class="text-success">
                                <i class="fa fa-info-circle"></i> Masukkan jumlah sesuai keinginan
                            </small>
                        </p>
                    @elseif($existingPendingDeposit)
                        <p class="card-text mb-0">
                            <small class="text-info">
                                <i class="fa fa-clock-o"></i> 
                                Konfirmasi sudah dibuat ({{ $existingPendingDeposit->created_at->format('d/m/Y H:i') }})
                                <br>
                                <span class="badge badge-{{ $existingPendingDeposit->status == 'verified' ? 'success' : ($existingPendingDeposit->status == 'rejected' ? 'danger' : 'warning') }}">
                                    {{ $existingPendingDeposit->getStatusLabel() }}
                                </span>
                            </small>
                        </p>
                    @else
                        <p class="card-text mb-0">
                            <small class="text-warning">
                                <i class="fa fa-exclamation-circle"></i> Selesaikan tagihan terlebih dahulu
                            </small>
                        </p>
                    @endif
                </div>
                <div>
                    @if($totalCurrentTagihan <= 0 && $totalTunggakan <= 0 && !$existingPendingDeposit)
                        <a href="{{ url('/pembayaran/deposit?payment_id=' . $payment->id) }}" 
                           class="btn btn-success">
                            <i class="fa fa-check"></i> Pilih
                        </a>
                    @else
                        <button class="btn btn-secondary" disabled>
                            <i class="fa fa-check"></i> Pilih
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="text-center">
        <a href="{{ url('/') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>
</div>

<style>
    /* Mobile optimization */
    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 15px !important;
            padding-right: 15px !important;
        }
        
        h4 {
            font-size: 1.25rem;
        }
        
        h6 {
            font-size: 1rem;
        }
        
        .card-body {
            padding: 1rem;
        }
    }
    
    /* Smooth shadows */
    .shadow-sm {
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.075) !important;
    }
</style>
@endsection
