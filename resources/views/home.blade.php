@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- PWA Install Prompt -->
    <div id="installPrompt" class="alert alert-info alert-dismissible fade show" role="alert" style="display: none;">
        <div class="d-flex align-items-center">
            <i class="fa fa-mobile fa-2x mr-3"></i>
            <div class="flex-grow-1">
                <strong>Install Aplikasi</strong>
                <p class="mb-0 small">Tambahkan ke layar utama untuk akses lebih cepat!</p>
            </div>
            <button type="button" class="btn btn-sm btn-primary mr-2" id="installButton">
                <i class="fa fa-download"></i> Install
            </button>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Header -->
    <div class="app-hero">
        <div class="app-hero-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
        <div>
            <div class="app-hero-greeting">Selamat datang,</div>
            <div class="app-hero-name">{{ auth()->user()->name }}</div>
            <span class="chip-badge chip-badge-light">{{ auth()->user()->blok }}</span>
        </div>
    </div>

    <!-- Current Month Billings -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-calendar"></i></span>
            <span class="app-card-title">Tagihan iuran rutin bulan ini</span>
            <span class="chip-badge chip-badge-primary">{{ \Carbon\Carbon::now()->locale('id')->isoFormat('MMM YYYY') }}</span>
        </div>
        <div class="card-body p-0">
            @if($billings->isEmpty())
                <div class="p-4 text-center text-muted">
                    <i class="fa fa-check-circle fa-3x mb-3 text-success"></i>
                    <p class="mb-0">Tidak ada tagihan untuk bulan ini</p>
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach($billings as $billing)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    {{ $billing->billing_name }}
                                    <small class="text-muted" style="font-weight: normal;">• {{ $billing->billing_generated_date->format('d M Y') }}</small>
                                </h6>
                                @if($billing->billing && $billing->billing->description)
                                    <p class="mb-2 text-muted small">{{ $billing->billing->description }}</p>
                                @endif
                                <div class="d-flex align-items-center">
                                    <span class="font-weight-bold text-dark mr-2">
                                        Rp {{ number_format($billing->billing_amount, 0, ',', '.') }}
                                    </span>
                                    @if($billing->payment_amount >= $billing->billing_amount)
                                        <span class="badge badge-success">
                                            <i class="fa fa-check"></i> Lunas
                                        </span>
                                    @elseif($billing->payment_amount > 0)
                                        <span class="badge badge-warning">
                                            Kurang Rp {{ number_format($billing->billing_amount - $billing->payment_amount, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="badge badge-danger">
                                            <i class="fa fa-times"></i> Belum Bayar
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Total Summary -->
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted font-weight-bold small text-uppercase" style="letter-spacing: .03em;">Total Tagihan</span>
                        <span class="h4 mb-0 font-weight-bold text-primary">
                            Rp {{ number_format($billings->sum('billing_amount'), 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if(!$billings->isEmpty())
        @php
            $totalPaid = $billings->sum('payment_amount');
            $totalBilling = $billings->sum('billing_amount');
            $remaining = $totalBilling - $totalPaid;
        @endphp
        @if($remaining > 0)
        <div class="app-alert app-alert-danger mb-4">
            <span class="icon-chip icon-chip-danger"><i class="fa fa-exclamation-circle"></i></span>
            <div>
                <div class="app-alert-title">Belum dibayar bulan ini</div>
                <div class="app-alert-amount">Rp {{ number_format($remaining, 0, ',', '.') }}</div>
            </div>
        </div>
        @else
        <div class="app-alert app-alert-success mb-4">
            <span class="icon-chip icon-chip-success"><i class="fa fa-check-circle"></i></span>
            <div>
                <div class="app-alert-title mb-0">Semua tagihan iuran rutin bulan ini sudah lunas</div>
            </div>
        </div>
        @endif
    @endif

    <!-- Manual Billings -->
    @if($manualBillings && $manualBillings->count() > 0)
    <div class="card border-0 shadow-sm mb-4 mt-4" style="background: rgba(14, 165, 233, .06); border-left: 4px solid #0ea5e9 !important; border-top-left-radius: .75rem; border-bottom-left-radius: .75rem;">
        <div class="app-card-header" style="background: transparent;">
            <span class="icon-chip icon-chip-info"><i class="fa fa-file-text"></i></span>
            <span class="app-card-title">Iuran Khusus</span>
            <span class="chip-badge" style="background: rgba(14, 165, 233, .12); color: #0284c7;">{{ $manualBillings->count() }} Item</span>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @foreach($manualBillings as $billing)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                {{ $billing->billing_name }}
                                <small class="text-muted" style="font-weight: normal;">• {{ $billing->billing_generated_date->format('d M Y') }}</small>
                            </h6>
                            @if($billing->billing && $billing->billing->description)
                                <p class="mb-2 text-muted small">{{ $billing->billing->description }}</p>
                            @endif
                            <div class="d-flex align-items-center">
                                <span class="font-weight-bold text-dark mr-2">
                                    Rp {{ number_format($billing->billing_amount, 0, ',', '.') }}
                                </span>
                                @if($billing->payment_amount >= $billing->billing_amount)
                                    <span class="badge badge-success">
                                        <i class="fa fa-check"></i> Lunas
                                    </span>
                                @elseif($billing->payment_amount > 0)
                                    <span class="badge badge-warning">
                                        Kurang Rp {{ number_format($billing->billing_amount - $billing->payment_amount, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        <i class="fa fa-times"></i> Belum Bayar
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Total Summary -->
            <div class="card-footer" style="background: rgba(14, 165, 233, .1);">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>Total Iuran Khusus:</strong>
                    <h5 class="mb-0" style="color: #0284c7;">
                        Rp {{ number_format($manualBillings->sum('billing_amount'), 0, ',', '.') }}
                    </h5>
                </div>
                @php
                    $totalManualPaid = $manualBillings->sum('payment_amount');
                    $totalManualBilling = $manualBillings->sum('billing_amount');
                    $remainingManual = $totalManualBilling - $totalManualPaid;
                @endphp
                @if($remainingManual > 0)
                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                    <span class="text-muted">Sisa yang harus dibayar:</span>
                    <h5 class="mb-0 text-danger">
                        Rp {{ number_format($remainingManual, 0, ',', '.') }}
                    </h5>
                </div>
                @else
                <div class="text-center mt-2 pt-2 border-top">
                    <span class="text-success">
                        <i class="fa fa-check-circle"></i> Semua iuran khusus sudah lunas
                    </span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Tunggakan Alert (if exists) -->
    @if($sumPreviousBilling > 0)
    <a href="{{ route('user.tunggakan') }}" class="app-alert app-alert-danger app-alert-link mb-4 mt-1" role="alert">
        <span class="icon-chip icon-chip-danger"><i class="fa fa-exclamation-triangle"></i></span>
        <div class="app-alert-text flex-grow-1">
            <div class="app-alert-title">Tunggakan bulan sebelumnya</div>
            <div class="app-alert-desc">Anda memiliki tunggakan sebesar</div>
            <div class="app-alert-amount">Rp {{ number_format($sumPreviousBilling, 0, ',', '.') }}</div>
        </div>
        <i class="fa fa-chevron-right align-self-center text-danger"></i>
    </a>
    @endif

    <!-- Payment Confirmation Status -->
    @if($existingCurrentPayment || $existingPreviousPayment || $existingManualPayment)
    <div class="card border-0 shadow-sm mb-4 mt-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-info"><i class="fa fa-info-circle"></i></span>
            <span class="app-card-title">Status konfirmasi pembayaran</span>
        </div>
        <div class="card-body">
            @if($existingCurrentPayment)
                <div class="mb-3 pb-3 {{ $existingPreviousPayment ? 'border-bottom' : '' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <i class="fa fa-calendar"></i> Tagihan iuran rutin bulan ini
                            </h6>
                            <small class="text-muted d-block mb-2">
                                Diajukan: {{ $existingCurrentPayment->created_at->format('d/m/Y H:i') }}
                            </small>
                            <div>
                                <span class="font-weight-bold d-block">
                                    Rp {{ number_format($existingCurrentPayment->payment_amount, 0, ',', '.') }}
                                </span>
                                <span class="badge badge-{{ $existingCurrentPayment->status == 'verified' ? 'success' : ($existingCurrentPayment->status == 'rejected' ? 'danger' : 'warning') }}" style="font-size: 0.7rem;">
                                    {{ $existingCurrentPayment->getStatusLabel() }}
                                </span>
                            </div>
                            @if(isset($existingCurrentPayment->excess_amount) && $existingCurrentPayment->excess_amount > 0 && $existingCurrentPayment->status == 'pending')
                                <div class="alert alert-warning mt-2 mb-0 py-2 px-3" role="alert">
                                    <small>
                                        <i class="fa fa-exclamation-triangle"></i> 
                                        <strong>Kelebihan: Rp {{ number_format($existingCurrentPayment->excess_amount, 0, ',', '.') }}</strong>
                                        <br>
                                        Kelebihan pembayaran akan dijadikan deposit secara otomatis.
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            
            @if($existingPreviousPayment)
                <div class="{{ $existingManualPayment ? 'mb-3 pb-3 border-bottom' : '' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <i class="fa fa-exclamation-triangle"></i> Tunggakan
                            </h6>
                            <small class="text-muted d-block mb-2">
                                Diajukan: {{ $existingPreviousPayment->created_at->format('d/m/Y H:i') }}
                            </small>
                            <div>
                                <span class="font-weight-bold d-block">
                                    Rp {{ number_format($existingPreviousPayment->payment_amount, 0, ',', '.') }}
                                </span>
                                <span class="badge badge-{{ $existingPreviousPayment->status == 'verified' ? 'success' : ($existingPreviousPayment->status == 'rejected' ? 'danger' : 'warning') }}" style="font-size: 0.7rem;">
                                    {{ $existingPreviousPayment->getStatusLabel() }}
                                </span>
                            </div>
                            @if(isset($existingPreviousPayment->excess_amount) && $existingPreviousPayment->excess_amount > 0 && $existingPreviousPayment->status == 'pending')
                                <div class="alert alert-warning mt-2 mb-0 py-2 px-3" role="alert">
                                    <small>
                                        <i class="fa fa-exclamation-triangle"></i> 
                                        <strong>Kelebihan: Rp {{ number_format($existingPreviousPayment->excess_amount, 0, ',', '.') }}</strong>
                                        <br>
                                        Kelebihan pembayaran akan dijadikan deposit secara otomatis.
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            
            @if($existingManualPayment)
                <div>
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <i class="fa fa-file-text"></i> Tagihan iuran khusus
                            </h6>
                            <small class="text-muted d-block mb-2">
                                Diajukan: {{ $existingManualPayment->created_at->format('d/m/Y H:i') }}
                            </small>
                            <div>
                                <span class="font-weight-bold d-block">
                                    Rp {{ number_format($existingManualPayment->payment_amount, 0, ',', '.') }}
                                </span>
                                <span class="badge badge-{{ $existingManualPayment->status == 'verified' ? 'success' : ($existingManualPayment->status == 'rejected' ? 'danger' : 'warning') }}" style="font-size: 0.7rem;">
                                    {{ $existingManualPayment->getStatusLabel() }}
                                </span>
                            </div>
                            @if(isset($existingManualPayment->excess_amount) && $existingManualPayment->excess_amount > 0 && $existingManualPayment->status == 'pending')
                                <div class="alert alert-warning mt-2 mb-0 py-2 px-3" role="alert">
                                    <small>
                                        <i class="fa fa-exclamation-triangle"></i> 
                                        <strong>Kelebihan: Rp {{ number_format($existingManualPayment->excess_amount, 0, ',', '.') }}</strong>
                                        <br>
                                        Kelebihan pembayaran akan dijadikan deposit secara otomatis.
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif

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
        
        h5 {
            font-size: 1.1rem;
        }
        
        .list-group-item {
            padding: 1rem;
        }
        
        .badge {
            font-size: 0.75rem;
        }
    }
    
    /* Smooth shadows */
    .shadow-sm {
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.075) !important;
    }
    
    /* Opacity classes for icons */
    .bg-opacity-10 {
        opacity: 0.1;
    }
    
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

<script>
function copyAccountNumber(paymentId, accountNumber) {
    // Create a temporary input element
    const tempInput = document.createElement('input');
    tempInput.value = accountNumber;
    document.body.appendChild(tempInput);
    
    // Select and copy the text
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        
        // Show success message
        const successMsg = document.getElementById('copy-success-' + paymentId);
        if (successMsg) {
            successMsg.style.display = 'block';
            
            // Hide message after 2 seconds
            setTimeout(function() {
                successMsg.style.display = 'none';
            }, 2000);
        }
    } catch (err) {
        console.error('Failed to copy:', err);
    }
    
    // Remove temporary input
    document.body.removeChild(tempInput);
}

// PWA Install Prompt Handler
let deferredPrompt;
const installPrompt = document.getElementById('installPrompt');
const installButton = document.getElementById('installButton');

// Check if already installed or dismissed
const isInstallDismissed = localStorage.getItem('pwa-install-dismissed');

window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    
    // Stash the event so it can be triggered later
    deferredPrompt = e;
    
    // Show the install prompt if not dismissed
    if (!isInstallDismissed) {
        installPrompt.style.display = 'block';
    }
});

// Handle install button click
if (installButton) {
    installButton.addEventListener('click', async () => {
        if (!deferredPrompt) {
            return;
        }
        
        // Show the install prompt
        deferredPrompt.prompt();
        
        // Wait for the user to respond to the prompt
        const { outcome } = await deferredPrompt.userChoice;
        
        console.log(`User response to the install prompt: ${outcome}`);
        
        // Hide the prompt
        installPrompt.style.display = 'none';
        
        // Clear the deferredPrompt
        deferredPrompt = null;
    });
}

// Handle close button to remember dismissal
const closeButton = installPrompt?.querySelector('.close');
if (closeButton) {
    closeButton.addEventListener('click', () => {
        localStorage.setItem('pwa-install-dismissed', 'true');
    });
}

// Check if app is already installed
window.addEventListener('appinstalled', () => {
    console.log('PWA was installed');
    installPrompt.style.display = 'none';
    localStorage.setItem('pwa-install-dismissed', 'true');
});
</script>

@endsection
