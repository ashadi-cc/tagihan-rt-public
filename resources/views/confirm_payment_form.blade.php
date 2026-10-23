@extends('layouts.main')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="app-page-header mb-4">
        <h4 class="app-page-header-title">Konfirmasi Pembayaran</h4>
        <p class="app-page-header-sub">Isi formulir konfirmasi pembayaran</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> Validation Errors:</h6>
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Payment Information (Hidden for QRIS) -->
    @if($payment->payment_type !== 'qr_payment')
    <div class="card border-0 shadow-sm mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-info"><i class="fa fa-info-circle"></i></span>
            <span class="app-card-title">Informasi Pembayaran</span>
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-5 text-muted">Jenis Tagihan:</div>
                <div class="col-7"><strong>{{ $billingTypeLabel }}</strong></div>
            </div>
            <div class="row mb-2">
                <div class="col-5 text-muted">Metode Bayar:</div>
                <div class="col-7"><strong>{{ $payment->name }}</strong></div>
            </div>
            @if($payment->payment_type == 'bank_account' && $payment->metadata)
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
            <div class="app-stat">
                <div class="app-stat-label">Jumlah Tagihan</div>
                <div class="app-stat-value">Rp {{ number_format($amountToPay, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- QRIS Code Display (if QRIS payment) -->
    @if($payment->payment_type == 'qr_payment')
    <div class="card border-0 shadow-sm mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-success"><i class="fa fa-qrcode"></i></span>
            <span class="app-card-title">Scan QR Code untuk Bayar</span>
        </div>
        <div class="card-body text-center">
            <div class="alert alert-warning mb-3">
                <i class="fa fa-exclamation-circle"></i>
                <strong>Perhatian:</strong> Setelah melakukan pembayaran via QRIS, Anda <strong>WAJIB</strong> mengupload bukti pembayaran di formulir di bawah untuk verifikasi manual oleh admin.
            </div>
            <div class="qr-container mb-3">
                <img src="{{ route('qris.image', ['payment_id' => $payment->id, 'amount' => $totalAmountWithFee ?? $amountToPay, 'type' => $type, 'v' => time()]) }}"
                     alt="QR Code"
                     class="img-fluid"
                     style="max-width: 350px; border: 2px solid #ddd; padding: 15px; background: white; border-radius: 10px;">
            </div>
            <div class="app-stat">
                <div class="app-stat-label"><i class="fa fa-money"></i> Total Pembayaran</div>
                <div class="app-stat-value">Rp {{ number_format($totalAmountWithFee ?? $amountToPay, 0, ',', '.') }}</div>
                @if(isset($feeAmount) && $feeAmount > 0)
                <small class="text-muted d-block mt-1">
                    (Tagihan: Rp {{ number_format($amountToPay, 0, ',', '.') }} +
                    Biaya Admin: Rp {{ number_format($feeAmount, 0, ',', '.') }})
                </small>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Payment Confirmation Form -->
    <form id="paymentForm" action="{{ route('submit.payment.confirmation') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="payment_id" value="{{ $payment->id }}">
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="amount_to_pay" value="{{ $amountToPay }}">

        <div class="card border-0 shadow-sm mb-4">
            <div class="app-card-header">
                <span class="icon-chip icon-chip-primary"><i class="fa fa-file-text"></i></span>
                <span class="app-card-title">Formulir Konfirmasi</span>
            </div>
            <div class="card-body">
                @if($payment->payment_type == 'qr_payment')
                <!-- QRIS Payment: Only require proof upload -->
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong>Pembayaran QRIS:</strong> Silakan scan QR Code di atas dan upload bukti pembayaran untuk konfirmasi.
                </div>
                
                <!-- Payment Proof (Required for QRIS) -->
                <div class="form-group">
                    <label class="form-label d-block mb-2">Bukti Pembayaran <span class="text-danger">*</span></label>
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
                           accept=".jpg,.jpeg,.png,.pdf"
                           required>
                    <small class="form-text text-muted d-block mt-2">
                        Format: JPG, JPEG, PNG, PDF. Maksimal 5MB
                    </small>
                    @error('payment_proof')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                @else
                <!-- Bank Transfer: Full form fields -->
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

                <!-- From Account Number (Bank Transfer Only) -->
                <div class="form-group">
                    <label for="from_account_number">No. Rekening Pengirim @if($payment->payment_type !== 'qr_payment')<span class="text-danger">*</span>@endif</label>
                    <input type="text" 
                           class="form-control @error('from_account_number') is-invalid @enderror" 
                           id="from_account_number" 
                           name="from_account_number" 
                           value="{{ old('from_account_number') }}"
                           placeholder="Nomor rekening Anda"
                           {{ $payment->payment_type !== 'qr_payment' ? 'required' : '' }}>
                    @error('from_account_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- From Account Name (Bank Transfer Only) -->
                <div class="form-group">
                    <label for="from_account_name">Nama Pemilik Rekening @if($payment->payment_type !== 'qr_payment')<span class="text-danger">*</span>@endif</label>
                    <input type="text" 
                           class="form-control @error('from_account_name') is-invalid @enderror" 
                           id="from_account_name" 
                           name="from_account_name" 
                           value="{{ old('from_account_name') }}"
                           placeholder="Nama sesuai rekening"
                           {{ $payment->payment_type !== 'qr_payment' ? 'required' : '' }}>
                    @error('from_account_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Amount Paid (Bank Transfer Only) -->
                <div class="form-group">
                    <label for="amount_paid">Jumlah yang Dibayar @if($payment->payment_type !== 'qr_payment')<span class="text-danger">*</span>@endif</label>
                    <input type="number" 
                           class="form-control @error('amount_paid') is-invalid @enderror" 
                           id="amount_paid" 
                           name="amount_paid" 
                           value="{{ old('amount_paid', intval($amountToPay)) }}"
                           min="0"
                           step="1"
                           {{ $payment->payment_type !== 'qr_payment' ? 'required' : '' }}>
                    @error('amount_paid')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Tagihan: Rp {{ number_format($amountToPay, 0, ',', '.') }}
                    </small>
                    @if($payment->payment_type !== 'qr_payment')
                    @if($type != 'manual_billing')
                    <div class="alert alert-info mt-2">
                        <i class="fa fa-info-circle"></i>
                        <small>
                            <strong>Informasi:</strong> Jika jumlah yang dibayar kurang dari tagihan, selisihnya akan menjadi tunggakan yang harus dibayar di pembayaran berikutnya. Jika jumlah yang dibayar lebih dari tagihan, kelebihannya akan menjadi deposit untuk pembayaran berikutnya.
                        </small>
                    </div>
                    @endif
                    <div id="amountWarningLess" class="alert alert-warning mt-2" style="display: none;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Perhatian:</strong> Jumlah yang dibayar kurang dari tagihan. 
                        Selisih sebesar <strong id="remainingAmount">Rp 0</strong> akan menjadi tunggakan.
                    </div>
                    <div id="amountWarningExceed" class="alert alert-success mt-2" style="display: none;">
                        <i class="fa fa-info-circle"></i>
                        <strong>Informasi:</strong> Jumlah yang dibayar melebihi tagihan. 
                        Kelebihan sebesar <strong id="excessAmount">Rp 0</strong> akan menjadi deposit Anda.
                    </div>
                    @endif
                </div>

                <!-- Payment Proof (Bank Transfer Only - Required) -->
                <div class="form-group">
                    <label class="form-label d-block mb-2">Bukti Transfer (Wajib)</label>
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
                    </small>
                    @error('payment_proof')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                @endif

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
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa fa-paper-plane"></i> Kirim Konfirmasi
                    </button>
                    <a href="{{ route('confirm.payment', ['payment_id' => $payment->id]) }}" 
                       class="btn btn-secondary btn-block">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="confirmationModalLabel">
                    <i class="fa fa-exclamation-triangle"></i> Konfirmasi Pembayaran
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong>Jumlah yang dibayar kurang dari tagihan.</strong></p>
                <p class="mb-3">
                    Selisih sebesar <strong class="text-danger" id="modalRemainingAmount">Rp 0</strong> akan menjadi tunggakan.
                </p>
                <p class="mb-0 text-muted">Apakah Anda yakin ingin melanjutkan?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" id="confirmSubmit">
                    <i class="fa fa-check"></i> Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>
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
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .invalid-feedback {
        display: block;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountPaidInput = document.getElementById('amount_paid');
    const amountWarning = document.getElementById('amountWarning');
    const remainingAmount = document.getElementById('remainingAmount');
    const amountToPay = {{ $amountToPay }};
    const form = document.getElementById('paymentForm');
    
    // Only setup amount validation for bank transfer (when input exists)
    if (amountPaidInput) {
        const amountWarningLess = document.getElementById('amountWarningLess');
        const amountWarningExceed = document.getElementById('amountWarningExceed');
        const excessAmount = document.getElementById('excessAmount');
        
        // Check amount on input
        amountPaidInput.addEventListener('input', function() {
            const amountPaid = parseFloat(this.value) || 0;
            
            if (amountPaid < amountToPay && amountPaid > 0) {
                const remaining = amountToPay - amountPaid;
                remainingAmount.textContent = 'Rp ' + remaining.toLocaleString('id-ID');
                amountWarningLess.style.display = 'block';
                amountWarningExceed.style.display = 'none';
            } else if (amountPaid > amountToPay) {
                const excess = amountPaid - amountToPay;
                excessAmount.textContent = 'Rp ' + excess.toLocaleString('id-ID');
                amountWarningLess.style.display = 'none';
                amountWarningExceed.style.display = 'block';
            } else {
                amountWarningLess.style.display = 'none';
                amountWarningExceed.style.display = 'none';
            }
        });
        
        // Form validation on submit
        form.addEventListener('submit', function(e) {
            const amountPaid = parseFloat(amountPaidInput.value) || 0;
            
            // Validate amount_paid
            if (amountPaid <= 0) {
                e.preventDefault();
                alert('Jumlah yang dibayar harus lebih dari 0');
                amountPaidInput.focus();
                return false;
            }
            
            // Confirm if amount is less than total
            if (amountPaid < amountToPay) {
                e.preventDefault();
                const remaining = amountToPay - amountPaid;
                document.getElementById('modalRemainingAmount').textContent = 'Rp ' + remaining.toLocaleString('id-ID');
                $('#confirmationModal').modal('show');
                return false;
            }
            
            // Show loading state
            showLoadingState();
        });
        
        // Handle modal confirmation
        document.getElementById('confirmSubmit').addEventListener('click', function() {
            $('#confirmationModal').modal('hide');
            showLoadingState();
            form.submit();
        });
    } else {
        // For QRIS, just show loading on submit
        form.addEventListener('submit', function(e) {
            showLoadingState();
        });
    }
    
    // Function to show loading state
    function showLoadingState() {
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Mengirim...';
    }
    
    // File upload validation
    const fileInput = document.getElementById('payment_proof');
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
