@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title">Deposit/Setoran</h4>
        <p class="app-page-header-sub">Setor dana deposit ke Kas RT</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Payment Information -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-success"><i class="fa fa-info-circle"></i></span>
            <span class="app-card-title">Informasi Pembayaran</span>
        </div>
        <div class="card-body">
            @if($payment->payment_type !== 'qr_payment')
            <div class="row mb-2">
                <div class="col-5 text-muted">Jenis:</div>
                <div class="col-7"><strong>Deposit</strong></div>
            </div>
            <div class="row mb-2">
                <div class="col-5 text-muted">Metode Bayar:</div>
                <div class="col-7"><strong>{{ $payment->name }}</strong></div>
            </div>
            @endif
            @if($payment->payment_type === 'qr_payment' && $payment->metadata)
                @php
                    $metadata = is_string($payment->metadata) ? json_decode($payment->metadata, true) : $payment->metadata;
                @endphp
                <!-- Display Static QR Code for QRIS -->
                <div class="row mb-3">
                    <div class="col-12 text-center">
                        @if(isset($metadata['qr_image_path']) && $metadata['qr_image_path'])
                            <div class="alert alert-warning mb-3">
                                <i class="fa fa-exclamation-circle"></i>
                                <strong>Perhatian:</strong> Setelah melakukan pembayaran via QRIS, Anda <strong>WAJIB</strong> mengupload bukti pembayaran di formulir di bawah untuk verifikasi manual oleh admin.
                            </div>
                            <div class="qr-code-wrapper mb-3">
                                <img src="{{ route('qris.image', ['payment_id' => $payment->id, 'amount' => 0, 'type' => 'deposit', 'v' => $payment->updated_at?->timestamp ?? time()]) }}" 
                                     alt="QR Code"
                                     class="qr-code-image"
                                     style="max-width: 300px; width: 100%; border: 2px solid #ddd; border-radius: 8px; padding: 10px;">
                            </div>
                            <p class="text-muted mb-0">
                                <i class="fa fa-qrcode"></i> Scan QR Code di atas untuk melakukan pembayaran
                            </p>
                        @else
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> QR Code belum tersedia
                            </div>
                        @endif
                    </div>
                </div>
            @elseif($payment->payment_type == 'bank_account' && $payment->metadata)
                @php
                    $metadata = is_string($payment->metadata) ? json_decode($payment->metadata, true) : $payment->metadata;
                @endphp
                <div class="row mb-2">
                    <div class="col-5 text-muted">Bank:</div>
                    <div class="col-7"><strong>{{ $metadata['bank_name'] ?? '-' }}</strong></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Atas Nama:</div>
                    <div class="col-7"><strong>{{ $metadata['account_name'] ?? '-' }}</strong></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">No. Rekening:</div>
                    <div class="col-7"><strong class="text-primary">{{ $metadata['account_number'] ?? '-' }}</strong></div>
                </div>
            @endif
            <hr>
            <div class="alert alert-info mb-0">
                <i class="fa fa-info-circle"></i>
                <strong>Informasi:</strong> Deposit adalah setoran dana yang akan digunakan untuk membayar tagihan iuran anda dimasa depan. Silakan isi formulir di bawah untuk melakukan deposit.
            </div>
        </div>
    </div>

    <!-- Deposit Form -->
    <form id="depositForm" action="{{ route('submit.deposit') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="payment_id" value="{{ $payment->id }}">

        <div class="card border-0 shadow-sm mb-4">
            <div class="app-card-header">
                <span class="icon-chip icon-chip-success"><i class="fa fa-file-text"></i></span>
                <span class="app-card-title">Formulir Deposit</span>
            </div>
            <div class="card-body">
                @if($payment->payment_type !== 'qr_payment')
                <!-- Bank Transfer Fields - Only show for non-QRIS payments -->
                <!-- From Bank -->
                <div class="form-group">
                    <label for="from_bank">Bank Pengirim <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('from_bank') is-invalid @enderror" 
                           id="from_bank" 
                           name="from_bank" 
                           value="{{ old('from_bank') }}"
                           placeholder="Contoh: BCA, Mandiri, BNI"
                           required>
                    @error('from_bank')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- From Account Number -->
                <div class="form-group">
                    <label for="from_account_number">No. Rekening Pengirim <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('from_account_number') is-invalid @enderror" 
                           id="from_account_number" 
                           name="from_account_number" 
                           value="{{ old('from_account_number') }}"
                           placeholder="Nomor rekening Anda"
                           required>
                    @error('from_account_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- From Account Name -->
                <div class="form-group">
                    <label for="from_account_name">Nama Pemilik Rekening <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('from_account_name') is-invalid @enderror" 
                           id="from_account_name" 
                           name="from_account_name" 
                           value="{{ old('from_account_name') }}"
                           placeholder="Nama sesuai rekening"
                           required>
                    @error('from_account_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @endif

                <!-- Amount Paid -->
                <div class="form-group">
                    <label for="amount_paid">Jumlah Deposit <span class="text-danger">*</span></label>
                    <input type="number" 
                           class="form-control @error('amount_paid') is-invalid @enderror" 
                           id="amount_paid" 
                           name="amount_paid" 
                           value="{{ old('amount_paid') }}"
                           min="1000"
                           step="1"
                           required>
                    @error('amount_paid')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Masukkan jumlah deposit sesuai keinginan Anda (minimum Rp 1.000)
                    </small>
                </div>

                <!-- Payment Proof -->
                <div class="form-group">
                    <label class="form-label d-block mb-2">
                        Bukti Transfer 
                        @if($payment->payment_type === 'qr_payment')
                            <span class="text-danger">*</span>
                        @else
                            (Wajib)
                        @endif
                    </label>
                    <div class="file-upload-container">
                        <label for="payment_proof" class="custom-file-upload" id="file-upload-btn">
                            <i class="fa fa-upload"></i> Pilih File
                        </label>
                        <button type="button" class="btn-reset-file" id="reset-file-btn" style="display: none;" title="Hapus file">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <input type="file" 
                           class="form-control-file @error('payment_proof') is-invalid @enderror" 
                           id="payment_proof" 
                           name="payment_proof"
                           accept=".jpg,.jpeg,.png,.pdf" required>
                    <small class="form-text text-muted d-block mt-2">
                        Format: JPG, JPEG, PNG, PDF. Maksimal 5MB
                        @if($payment->payment_type === 'qr_payment')
                            <br><strong class="text-danger">* Wajib untuk pembayaran QRIS</strong>
                        @endif
                    </small>
                    @error('payment_proof')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label for="notes">Catatan (Opsional)</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                              id="notes" 
                              name="notes" 
                              rows="3"
                              maxlength="1000"
                              placeholder="Tambahkan catatan jika diperlukan">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Submit Buttons -->
                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fa fa-paper-plane"></i> Kirim Deposit
                    </button>
                    <a href="{{ url('/') }}" 
                       class="btn btn-secondary btn-block">
                        <i class="fa fa-arrow-left"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    /* Hide the real file input */
    input[type="file"] {
        display: none;
    }

    /* File upload container */
    .file-upload-container {
        display: flex;
        align-items: center;
        gap: 8px;
        position: relative;
        width: 100%;
    }

    /* Custom button style */
    .custom-file-upload {
        display: inline-block;
        padding: 12px 24px;
        cursor: pointer;
        background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        font-family: sans-serif;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        text-align: center;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Hover effect */
    .custom-file-upload:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
    }

    .custom-file-upload:active {
        transform: translateY(0);
    }

    .custom-file-upload i {
        margin-right: 8px;
    }

    /* Reset file button */
    .btn-reset-file {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        padding: 0;
        border: none;
        border-radius: 50%;
        background-color: #dc3545;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
        font-size: 14px;
    }

    .btn-reset-file:hover {
        background-color: #c82333;
        transform: scale(1.1);
        box-shadow: 0 3px 8px rgba(220, 53, 69, 0.4);
    }

    .btn-reset-file:active {
        transform: scale(0.95);
    }

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
        
        .custom-file-upload {
            padding: 14px 20px;
            font-size: 15px;
            border-radius: 6px;
        }
        
        .btn-reset-file {
            width: 38px;
            height: 38px;
            font-size: 16px;
        }
    }
    
    /* Form styling */
    .form-control:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    .invalid-feedback {
        display: block;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('depositForm');
    const amountInput = document.getElementById('amount_paid');
    const fileInput = document.getElementById('payment_proof');
    const isQris = {{ $payment->payment_type === 'qr_payment' ? 'true' : 'false' }};
    
    // Form validation on submit
    form.addEventListener('submit', function(e) {
        const amount = parseFloat(amountInput.value) || 0;
        
        if (amount < 1000) {
            e.preventDefault();
            alert('Jumlah deposit minimal Rp 1.000');
            amountInput.focus();
            return false;
        }
        
        // For QRIS, payment proof is required
        if (isQris && !fileInput.files.length) {
            e.preventDefault();
            alert('Bukti transfer wajib diunggah untuk pembayaran QRIS');
            fileInput.focus();
            return false;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Mengirim...';
    });
    
    // File upload validation
    const fileUploadBtn = document.getElementById('file-upload-btn');
    const resetFileBtn = document.getElementById('reset-file-btn');
    
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            
            if (file.size > maxSize) {
                alert('Ukuran file terlalu besar. Maksimal 5MB');
                this.value = '';
                resetFileSelection();
                return;
            }
            
            if (!allowedTypes.includes(file.type)) {
                alert('Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF');
                this.value = '';
                resetFileSelection();
                return;
            }
            
            // Update button text with filename (truncate if too long)
            let displayName = file.name;
            if (displayName.length > 30) {
                displayName = displayName.substring(0, 27) + '...';
            }
            fileUploadBtn.innerHTML = '<i class="fa fa-check-circle"></i> ' + displayName;
            resetFileBtn.style.display = 'inline-flex';
        } else {
            resetFileSelection();
        }
    });
    
    // Reset file selection
    resetFileBtn.addEventListener('click', function() {
        fileInput.value = '';
        resetFileSelection();
    });
    
    function resetFileSelection() {
        fileUploadBtn.innerHTML = '<i class="fa fa-upload"></i> Pilih File';
        resetFileBtn.style.display = 'none';
    }
});
</script>
@endsection
