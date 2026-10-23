@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Channel Pembayaran</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/master/payment') }}">Daftar Channel Pembayaran</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah Pembayaran</li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>Tambah Channel Pembayaran</h5>
                    <form action="{{ url('admin/master/payment') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="nama">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama" name="nama" 
                                   placeholder="Contoh: Rekening Utama Bendahara RT" 
                                   value="{{ old('nama') }}" required>
                            @error('nama')
                                <div class="text-danger">
                                    {{ $errors->first('nama') }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_type">Jenis Pembayaran <span class="text-danger">*</span></label>
                            <select class="form-control" id="payment_type" name="payment_type" required>
                                <option value="">-- Pilih Jenis Pembayaran --</option>
                                <option value="bank_account" {{ old('payment_type') == 'bank_account' ? 'selected' : '' }}>Rekening Bank</option>
                                <option value="qr_payment" {{ old('payment_type') == 'qr_payment' ? 'selected' : '' }}>Qris Code</option>
                            </select>
                            @error('payment_type')
                                <div class="text-danger">
                                    {{ $errors->first('payment_type') }}
                                </div>
                            @enderror
                        </div>

                        <div id="qr_payment_fields" style="display: none;">
                            <div class="form-group">
                                <label for="qr_image">Upload QRIS Image <span class="text-danger">*</span></label>
                                <input type="file" class="form-control-file" id="qr_image" name="qr_image" accept="image/*">
                                <small class="form-text text-muted">Upload gambar QR Code (JPG, PNG, atau format gambar lainnya)</small>
                                @error('qr_image')
                                    <div class="text-danger">
                                        {{ $errors->first('qr_image') }}
                                    </div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" id="is_dynamic_qris" name="is_dynamic_qris" value="1" {{ old('is_dynamic_qris') == '1' ? 'checked' : '' }}>
                                        Dynamic QRIS (dengan fee)
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fa fa-info-circle"></i> Dynamic QRIS memungkinkan penambahan fee/biaya admin otomatis
                                </small>
                            </div>
                            
                            <div id="dynamic_qris_fields" style="display: none;">
                                <div class="form-group">
                                    <label for="fee_type">Tipe Fee <span class="text-danger">*</span></label>
                                    <select class="form-control" id="fee_type" name="fee_type">
                                        <option value="">-- Pilih Tipe Fee --</option>
                                        <option value="rupiah" {{ old('fee_type') == 'rupiah' ? 'selected' : '' }}>Rupiah</option>
                                        <option value="percent" {{ old('fee_type') == 'percent' ? 'selected' : '' }}>Persen (%)</option>
                                    </select>
                                    @error('fee_type')
                                        <div class="text-danger">
                                            {{ $errors->first('fee_type') }}
                                        </div>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <label for="fee_value">Nilai Fee <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="fee_value" name="fee_value" 
                                           placeholder="Contoh: 1000 atau 2.5" 
                                           step="0.01"
                                           value="{{ old('fee_value') }}">
                                    <small class="form-text text-muted">Masukkan nilai dalam rupiah atau persen sesuai tipe fee yang dipilih</small>
                                    @error('fee_value')
                                        <div class="text-danger">
                                            {{ $errors->first('fee_value') }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div id="bank_account_fields" style="display: none;">
                            <div class="form-group">
                                <label for="bank_name">Nama Bank <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                       placeholder="Contoh: BCA" 
                                       value="{{ old('bank_name') }}">
                                @error('bank_name')
                                    <div class="text-danger">
                                        {{ $errors->first('bank_name') }}
                                    </div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label for="account_name">Nama Pemilik Rekening <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_name" name="account_name" 
                                       placeholder="Contoh: Budi Santoso" 
                                       value="{{ old('account_name') }}">
                                @error('account_name')
                                    <div class="text-danger">
                                        {{ $errors->first('account_name') }}
                                    </div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label for="account_number">Nomor Rekening <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_number" name="account_number" 
                                       placeholder="Contoh: 1234567890" 
                                       value="{{ old('account_number') }}">
                                @error('account_number')
                                    <div class="text-danger">
                                        {{ $errors->first('account_number') }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Keterangan tambahan">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="text-danger">
                                    {{ $errors->first('description') }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" value="1" name="aktif" {{ old('aktif', '1') == '1' ? 'checked' : '' }}>
                                    Aktif?
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fa fa-info-circle"></i> Channel pembayaran yang aktif akan ditampilkan dan dapat digunakan oleh warga untuk melakukan pembayaran.
                            </small>
                        </div>
                        
                        <hr>
                        <div class="form-group d-flex flex-wrap" style="gap: .5rem;">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Simpan
                            </button>
                            <a href="{{ url('admin/master/payment') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Function to toggle bank account fields
    function toggleBankAccountFields() {
        var paymentType = $('#payment_type').val();
        
        if (paymentType === 'bank_account') {
            $('#bank_account_fields').show();
            $('#bank_name').prop('required', true);
            $('#account_name').prop('required', true);
            $('#account_number').prop('required', true);
        } else {
            $('#bank_account_fields').hide();
            $('#bank_name').prop('required', false);
            $('#account_name').prop('required', false);
            $('#account_number').prop('required', false);
        }
    }
    
    // Function to toggle QR payment fields
    function toggleQrPaymentFields() {
        var paymentType = $('#payment_type').val();
        
        if (paymentType === 'qr_payment') {
            $('#qr_payment_fields').show();
            $('#qr_image').prop('required', true);
            toggleDynamicQrisFields();
        } else {
            $('#qr_payment_fields').hide();
            $('#qr_image').prop('required', false);
            $('#dynamic_qris_fields').hide();
            $('#fee_type').prop('required', false);
            $('#fee_value').prop('required', false);
        }
    }
    
    // Function to toggle dynamic QRIS fields
    function toggleDynamicQrisFields() {
        if ($('#is_dynamic_qris').is(':checked')) {
            $('#dynamic_qris_fields').show();
            $('#fee_type').prop('required', true);
            $('#fee_value').prop('required', true);
        } else {
            $('#dynamic_qris_fields').hide();
            $('#fee_type').prop('required', false);
            $('#fee_value').prop('required', false);
        }
    }
    
    // Check on page load (for old input after validation error)
    toggleBankAccountFields();
    toggleQrPaymentFields();
    
    // Check on change
    $('#payment_type').on('change', function() {
        toggleBankAccountFields();
        toggleQrPaymentFields();
    });
    
    // Check dynamic QRIS checkbox change
    $('#is_dynamic_qris').on('change', function() {
        toggleDynamicQrisFields();
    });
});
</script>
@endsection
