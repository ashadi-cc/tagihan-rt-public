@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Tambah Deposit untuk {{ $user->name }} ({{ $user->blok }})</h1>
    @include('admin.master._header_warga_detail', ['active' => 'deposit', 'userId' => $user->id])

    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-plus"></i></span>
            <span class="app-card-title">Form Tambah Deposit</span>
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

            <div class="alert alert-info mb-4">
                <i class="fa fa-info-circle"></i>
                <strong>Informasi:</strong> Gunakan form ini untuk mencatat deposit/setoran dari warga. 
                Anda dapat memilih untuk langsung memverifikasi deposit ini atau menyimpannya sebagai pending.
            </div>

            <form action="{{ route('admin.warga.deposit.store', ['userID' => $user->id]) }}" method="POST" enctype="multipart/form-data">
                @csrf

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

                <!-- Payment Method -->
                <div class="form-group">
                    <label for="payment_id">Metode Pembayaran <span class="text-danger">*</span></label>
                    <select name="payment_id" id="payment_id" class="form-control @error('payment_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Metode Pembayaran --</option>
                        @foreach($payments as $payment)
                            <option value="{{ $payment->id }}" {{ old('payment_id') == $payment->id ? 'selected' : '' }}>
                                {{ $payment->name }}
                                @if($payment->payment_type == 'bank_account' && $payment->metadata)
                                    @php
                                        $metadata = is_string($payment->metadata) ? json_decode($payment->metadata, true) : $payment->metadata;
                                    @endphp
                                    - {{ $metadata['bank_name'] ?? '' }} {{ $metadata['account_number'] ?? '' }}
                                @endif
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
                    <label for="amount_paid">Jumlah Deposit <span class="text-danger">*</span></label>
                    <input type="number" 
                           class="form-control @error('amount_paid') is-invalid @enderror" 
                           id="amount_paid" 
                           name="amount_paid" 
                           value="{{ old('amount_paid') }}"
                           min="1"
                           step="1"
                           required>
                    @error('amount_paid')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Masukkan jumlah deposit dalam Rupiah
                    </small>
                </div>

                <!-- Payment Proof -->
                <div class="form-group">
                    <label for="payment_proof">Bukti Transfer (Opsional)</label>
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

                <!-- Auto Verify Option -->
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" 
                               class="custom-control-input" 
                               id="auto_verify" 
                               name="auto_verify" 
                               value="1"
                               {{ old('auto_verify') ? 'checked' : 'checked' }}>
                        <label class="custom-control-label" for="auto_verify">
                            <strong>Verifikasi Otomatis</strong>
                            <br>
                            <small class="text-muted">Jika dicentang, deposit akan langsung terverifikasi. Jika tidak, deposit akan berstatus "Menunggu Verifikasi".</small>
                        </label>
                    </div>
                </div>

                <hr>

                <!-- Submit Buttons -->
                <div class="form-group mb-0 d-flex flex-wrap" style="gap: .5rem;">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Simpan Deposit
                    </button>
                    <a href="{{ route('admin.warga.deposit', ['userID' => $user->id]) }}"
                       class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
