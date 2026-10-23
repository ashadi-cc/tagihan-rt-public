@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Catat Pembayaran untuk {{ $user->name }} ({{ $user->blok }})</h1>
    @include('admin.master._header_warga_detail', ['active' => 'pembayaran', 'userId' => $user->id])

    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-success"><i class="fa fa-plus"></i></span>
            <span class="app-card-title">Form Catat Pembayaran</span>
        </div>
        <div class="card-body">
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-circle"></i> 
                    <strong>Terdapat kesalahan:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="alert alert-info mb-4">
                <i class="fa fa-info-circle"></i>
                <strong>Informasi:</strong> Gunakan form ini untuk mencatat pembayaran dari warga. 
                Pilih tagihan yang akan dibayar dan masukkan jumlah pembayaran.
            </div>

            <form action="{{ route('admin.warga.pembayaran.store', ['userID' => $user->id]) }}" method="POST" enctype="multipart/form-data" id="pembayaranForm">
                @csrf

                <div class="row">
                    <div class="col-md-7">
                        <!-- User Information -->
                        <div class="card mb-3 bg-light">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Informasi Warga</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><strong>Nama:</strong></label>
                                            <p class="form-control-plaintext">{{ $user->name }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><strong>Blok:</strong></label>
                                            <p class="form-control-plaintext">{{ $user->blok }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Type -->
                        <div class="form-group">
                            <label for="payment_type">Tipe Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_type" id="payment_type" class="form-control @error('payment_type') is-invalid @enderror" required>
                                <option value="">-- Pilih Tipe Pembayaran --</option>
                                <option value="current_billing" {{ old('payment_type') == 'current_billing' ? 'selected' : '' }}>
                                    Tagihan Bulan Ini ({{ now()->format('F Y') }})
                                </option>
                                <option value="previous_billing" {{ old('payment_type') == 'previous_billing' ? 'selected' : '' }}>
                                    Tagihan Sebelumnya & Tunggakan
                                </option>
                                <option value="manual_billing" {{ old('payment_type') == 'manual_billing' ? 'selected' : '' }}>
                                    Tagihan Iuran Khusus / Manual
                                </option>
                            </select>
                            @error('payment_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Payment Method -->
                        <div class="form-group">
                            <label for="payment_id">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_id" id="payment_id" class="form-control @error('payment_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Metode Pembayaran --</option>
                                @foreach($payments as $payment)
                                    <option value="{{ $payment->id }}" {{ old('payment_id') == $payment->id ? 'selected' : '' }}>
                                        {{ $payment->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <!-- Sender Bank Information -->
                        <h5 class="mb-3">Informasi Pengirim (Opsional)</h5>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="from_bank">Bank Pengirim</label>
                                    <input type="text" 
                                           class="form-control @error('from_bank') is-invalid @enderror" 
                                           id="from_bank" 
                                           name="from_bank" 
                                           value="{{ old('from_bank') }}"
                                           placeholder="Contoh: BCA, Mandiri, BNI">
                                    @error('from_bank')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="from_account_number">No. Rekening Pengirim</label>
                                    <input type="text" 
                                           class="form-control @error('from_account_number') is-invalid @enderror" 
                                           id="from_account_number" 
                                           name="from_account_number" 
                                           value="{{ old('from_account_number') }}"
                                           placeholder="Nomor rekening">
                                    @error('from_account_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="from_account_name">Nama Pemilik Rekening</label>
                                    <input type="text" 
                                           class="form-control @error('from_account_name') is-invalid @enderror" 
                                           id="from_account_name" 
                                           name="from_account_name" 
                                           value="{{ old('from_account_name', $user->name) }}"
                                           placeholder="Nama sesuai rekening">
                                    @error('from_account_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Amount -->
                        <div class="form-group">
                            <label for="amount_paid">Jumlah Pembayaran <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('amount_paid') is-invalid @enderror" 
                                   id="amount_paid" name="amount_paid" 
                                   placeholder="Masukkan jumlah pembayaran" 
                                   value="{{ old('amount_paid') }}" 
                                   step="1" min="1" required>
                            @error('amount_paid')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Maksimal: Rp <span id="maxAmount">0</span>
                            </small>
                        </div>

                        <!-- Payment Proof -->
                        <div class="form-group">
                            <label for="payment_proof">Bukti Pembayaran (Opsional)</label>
                            <input type="file" 
                                   class="form-control-file @error('payment_proof') is-invalid @enderror" 
                                   id="payment_proof" 
                                   name="payment_proof"
                                   accept=".jpg,.jpeg,.png,.pdf">
                            <small class="form-text text-muted">
                                Format: JPG, JPEG, PNG, PDF. Maksimal 5MB
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

                        <input type="hidden" name="auto_verify" id="auto_verify" value="1">

                        <!-- Submit Buttons -->
                        <div class="form-group d-flex flex-wrap" style="gap: .5rem;">
                            <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                                <i class="fa fa-save"></i> Simpan Pembayaran
                            </button>
                            <a href="{{ route('admin.warga.pembayaran', ['userID' => $user->id]) }}" class="btn btn-outline-secondary">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <!-- Tagihan List -->
                        <div class="card">
                            <div class="app-card-header">
                                <span class="icon-chip icon-chip-info"><i class="fa fa-list"></i></span>
                                <span class="app-card-title">Daftar Tagihan</span>
                            </div>
                            <div class="card-body p-2">
                                <div id="tagihanContainer" style="display: none;">
                                    <div class="mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="selectAllTagihan">
                                            <label class="custom-control-label" for="selectAllTagihan">
                                                <strong>Pilih Semua</strong>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                                        <div id="tagihanList"></div>
                                    </div>
                                    
                                    <div class="mt-2 text-right">
                                        <strong>Total Dipilih: Rp <span id="selectedTotal">0</span></strong>
                                    </div>
                                </div>
                                
                                <div id="noTagihanMessage" class="text-center text-muted py-4">
                                    <i class="fa fa-info-circle fa-2x mb-2"></i>
                                    <p>Pilih tipe pembayaran untuk melihat daftar tagihan</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentTagihan = [];
    let previousTagihan = [];
    let manualTagihan = [];

    // Load tagihan data
    function loadTagihan(type) {
        if (type === 'current_billing' && currentTagihan.length === 0) {
            $.get(`{{ route('admin.warga.pembayaran.tagihan', ['userID' => $user->id]) }}?type=current_billing`)
                .done(function(data) {
                    currentTagihan = data;
                    displayTagihan(currentTagihan);
                });
        } else if (type === 'previous_billing' && previousTagihan.length === 0) {
            $.get(`{{ route('admin.warga.pembayaran.tagihan', ['userID' => $user->id]) }}?type=previous_billing`)
                .done(function(data) {
                    previousTagihan = data;
                    displayTagihan(previousTagihan);
                });
        } else if (type === 'manual_billing' && manualTagihan.length === 0) {
            $.get(`{{ route('admin.warga.pembayaran.tagihan', ['userID' => $user->id]) }}?type=manual`)
                .done(function(data) {
                    manualTagihan = data;
                    displayTagihan(manualTagihan);
                });
        } else if (type === 'current_billing') {
            displayTagihan(currentTagihan);
        } else if (type === 'previous_billing') {
            displayTagihan(previousTagihan);
        } else if (type === 'manual_billing') {
            displayTagihan(manualTagihan);
        }
    }
    
    function displayTagihan(tagihan) {
        let html = '';
        if (tagihan.length === 0) {
            $('#tagihanContainer').hide();
            $('#noTagihanMessage').html('<i class="fa fa-check-circle fa-2x mb-2 text-success"></i><p>Tidak ada tagihan yang perlu dibayar</p>').show();
            return;
        }
        
        tagihan.forEach(function(item) {
            let outstanding = item.billing_amount - item.payment_amount;
            html += `
                <div class="p-2 border-bottom">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tagihan-checkbox" 
                               id="tagihan_${item.id}" 
                               name="tagihan_ids[]" 
                               value="${item.id}"
                               data-amount="${outstanding}">
                        <label class="custom-control-label" for="tagihan_${item.id}">
                            <strong>${item.billing_name}</strong><br>
                            <small class="text-muted">
                                Total: Rp ${outstanding.toLocaleString('id-ID')}<br>
                                Tanggal: ${new Date(item.billing_generated_date).toLocaleDateString('id-ID')}
                            </small>
                        </label>
                    </div>
                </div>
            `;
        });
        
        $('#tagihanList').html(html);
        $('#tagihanContainer').show();
        $('#noTagihanMessage').hide();
        updateTotal();
    }
    
    function updateTotal() {
        let total = 0;
        $('.tagihan-checkbox:checked').each(function() {
            total += parseInt($(this).data('amount'));
        });
        $('#selectedTotal').text(total.toLocaleString('id-ID'));
        $('#maxAmount').text(total.toLocaleString('id-ID'));
        
        // Enable/disable submit button
        $('#submitBtn').prop('disabled', total === 0);
        
        // Update amount input max attribute
        $('#amount_paid').attr('max', total);
        // update amount input value with total
        $('#amount_paid').val(total);
    }
    
    // Payment type change event
    $('#payment_type').on('change', function() {
        let type = $(this).val();
        if (type) {
            loadTagihan(type);
        } else {
            $('#tagihanContainer').hide();
            $('#noTagihanMessage').html('<i class="fa fa-info-circle fa-2x mb-2"></i><p>Pilih tipe pembayaran untuk melihat daftar tagihan</p>').show();
            $('#submitBtn').prop('disabled', true);
        }
    });
    
    // Select all checkbox
    $(document).on('change', '#selectAllTagihan', function() {
        $('.tagihan-checkbox').prop('checked', $(this).is(':checked'));
        updateTotal();
    });
    
    // Individual tagihan checkbox
    $(document).on('change', '.tagihan-checkbox', function() {
        updateTotal();
        
        // Update select all checkbox state
        let total = $('.tagihan-checkbox').length;
        let checked = $('.tagihan-checkbox:checked').length;
        $('#selectAllTagihan').prop('checked', total === checked);
    });
    
    // Form validation
    $('#pembayaranForm').on('submit', function(e) {
        let selectedTagihan = $('.tagihan-checkbox:checked').length;
        let amount = parseInt($('#amount_paid').val()) || 0;
        let maxAmount = parseInt($('#amount_paid').attr('max')) || 0;
        
        if (selectedTagihan === 0) {
            e.preventDefault();
            alert('Silakan pilih minimal satu tagihan yang akan dibayar!');
            return false;
        }
        
        if (amount > maxAmount) {
            e.preventDefault();
            alert(`Jumlah pembayaran tidak boleh lebih dari Rp ${maxAmount.toLocaleString('id-ID')}!`);
            return false;
        }
        
        if (amount <= 0) {
            e.preventDefault();
            alert('Jumlah pembayaran harus lebih dari 0!');
            return false;
        }
    });
});
</script>
@endsection