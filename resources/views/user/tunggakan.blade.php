@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title"><i class="fa fa-exclamation-triangle"></i> Tunggakan bulan sebelumnya</h4>
        <p class="app-page-header-sub">Rincian tagihan dari bulan-bulan sebelumnya yang belum lunas</p>
    </div>

    @if($tunggakan->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
                <p class="text-muted mb-0">Tidak ada tunggakan. Semua tagihan sudah lunas.</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm mb-4">
            <div class="list-group list-group-flush">
                @foreach($tunggakan as $item)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                {{ $item->billing_name }}
                                <small class="text-muted" style="font-weight: normal;">• {{ $item->period }}</small>
                            </h6>
                            @if($item->billing && $item->billing->description)
                                <p class="mb-2 text-muted small">{{ $item->billing->description }}</p>
                            @endif
                            <div class="small text-muted">
                                Tagihan: Rp {{ number_format($item->billing_amount, 0, ',', '.') }}
                                @if($item->payment_amount > 0)
                                    &middot; Terbayar: Rp {{ number_format($item->payment_amount, 0, ',', '.') }}
                                @endif
                            </div>
                        </div>
                        <div class="ml-2 text-right">
                            <span class="font-weight-bold text-danger">
                                Rp {{ number_format($item->billing_amount - $item->payment_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted font-weight-bold small text-uppercase" style="letter-spacing: .03em;">Total Tunggakan</span>
                    <span class="h4 mb-0 font-weight-bold text-danger">
                        Rp {{ number_format($total, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        @if($paymentChannel && $paymentChannel->count() > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="app-card-header">
                <span class="icon-chip icon-chip-success"><i class="fa fa-credit-card"></i></span>
                <span class="app-card-title">Cara Pembayaran</span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($paymentChannel as $payment)
                    <div class="list-group-item">
                        <h6 class="mb-2">
                            <i class="fa {{ $payment->payment_type == 'bank_account' ? 'fa-university' : ($payment->payment_type == 'qr_payment' ? 'fa-qrcode' : 'fa-credit-card') }} text-primary"></i> {{ $payment->name }}
                        </h6>

                        @if($payment->payment_type == 'bank_account' && $payment->metadata)
                            @php
                                $metadata = is_string($payment->metadata) ? json_decode($payment->metadata, true) : $payment->metadata;
                            @endphp
                            <div class="mt-2">
                                <div class="row mb-2">
                                    <div class="col-5 col-md-3 text-muted small">Nama Bank:</div>
                                    <div class="col-7 col-md-9 font-weight-bold">{{ $metadata['bank_name'] ?? '-' }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 col-md-3 text-muted small">Nama Rekening:</div>
                                    <div class="col-7 col-md-9 font-weight-bold">{{ $metadata['account_name'] ?? '-' }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-5 col-md-3 text-muted small">No. Rekening:</div>
                                    <div class="col-7 col-md-9">
                                        <div class="d-flex align-items-center">
                                            <span class="font-weight-bold text-primary account-number-text" id="account-number-{{ $payment->id }}">
                                                {{ $metadata['account_number'] ?? '-' }}
                                            </span>
                                            <button type="button"
                                                    class="copy-btn ml-2"
                                                    onclick="copyAccountNumber('{{ $payment->id }}', '{{ $metadata['account_number'] ?? '' }}')"
                                                    title="Salin nomor rekening">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </div>
                                        <small class="text-success" id="copy-success-{{ $payment->id }}" style="display: none;">
                                            <i class="fa fa-check"></i> Tersalin!
                                        </small>
                                    </div>
                                </div>
                                <a href="{{ url('/pembayaran/konfirmasi?payment_id=' . $payment->id) }}" class="app-action-row">
                                    <span class="app-action-row-icon"><i class="fa fa-upload"></i></span>
                                    <div class="app-action-row-text">
                                        <div class="app-action-row-title">Konfirmasi Pembayaran</div>
                                        <div class="app-action-row-sub">Sudah transfer atau mau deposit? Upload bukti di sini</div>
                                    </div>
                                    <i class="fa fa-chevron-right app-action-row-arrow"></i>
                                </a>
                            </div>
                        @elseif($payment->payment_type == 'qr_payment' && $payment->metadata)
                            @php
                                $metadata = is_string($payment->metadata) ? json_decode($payment->metadata, true) : $payment->metadata;
                            @endphp
                            <div class="mt-2">
                                @if(isset($metadata['is_dynamic_qris']) && $metadata['is_dynamic_qris'])
                                    <div class="alert alert-info mb-3" style="font-size: 0.85rem;">
                                        <i class="fa fa-info-circle"></i>
                                        <strong>QRIS Dinamis:</strong>
                                        Biaya admin
                                        @if($metadata['fee_type'] == 'percent')
                                            {{ $metadata['fee_value'] }}%
                                        @else
                                            Rp {{ number_format($metadata['fee_value'], 0, ',', '.') }}
                                        @endif
                                        akan ditambahkan otomatis
                                    </div>
                                @endif
                                <a href="{{ url('/pembayaran/konfirmasi?payment_id=' . $payment->id) }}" class="app-action-row">
                                    <span class="app-action-row-icon"><i class="fa fa-qrcode"></i></span>
                                    <div class="app-action-row-text">
                                        <div class="app-action-row-title">Bayar dengan QRIS</div>
                                        <div class="app-action-row-sub">Lihat QR Code & lakukan pembayaran</div>
                                    </div>
                                    <i class="fa fa-chevron-right app-action-row-arrow"></i>
                                </a>
                            </div>
                        @else
                            <div class="mt-2">
                                <a href="{{ url('/pembayaran/konfirmasi?payment_id=' . $payment->id) }}" class="app-action-row">
                                    <span class="app-action-row-icon"><i class="fa fa-check-circle"></i></span>
                                    <div class="app-action-row-text">
                                        <div class="app-action-row-title">Konfirmasi Pembayaran</div>
                                        <div class="app-action-row-sub">{{ $payment->description ?? 'Upload bukti pembayaran Anda' }}</div>
                                    </div>
                                    <i class="fa fa-chevron-right app-action-row-arrow"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-light">
                <small class="text-muted">
                    <i class="fa fa-info-circle"></i> Setelah melakukan pembayaran, silakan konfirmasi dengan upload bukti pembayaran melalui aplikasi.
                </small>
            </div>
        </div>
        @endif
    @endif
</div>

<script>
function copyAccountNumber(paymentId, accountNumber) {
    const tempInput = document.createElement('input');
    tempInput.value = accountNumber;
    document.body.appendChild(tempInput);

    tempInput.select();
    tempInput.setSelectionRange(0, 99999);

    try {
        document.execCommand('copy');

        const successMsg = document.getElementById('copy-success-' + paymentId);
        if (successMsg) {
            successMsg.style.display = 'block';
            setTimeout(function() {
                successMsg.style.display = 'none';
            }, 2000);
        }
    } catch (err) {
        console.error('Failed to copy:', err);
    }

    document.body.removeChild(tempInput);
}
</script>

<style>
    /* Account number + copy button */
    .account-number-text {
        font-size: 0.95rem;
        letter-spacing: 0.5px;
    }

    .copy-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.75rem;
        height: 1.75rem;
        padding: 0;
        font-size: 0.75rem;
        line-height: 1;
        color: #007bff;
        background-color: transparent;
        border: 1px solid rgba(0, 123, 255, 0.3);
        border-radius: 0.25rem;
        flex-shrink: 0;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .copy-btn:hover,
    .copy-btn:focus {
        background-color: #007bff;
        color: #fff;
        outline: none;
    }
</style>
@endsection
