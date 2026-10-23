@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Pemasukan & Pengeluaran RT</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/rt-transaction') }}">Daftar Transaksi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Transaksi</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>Edit Transaksi</h5>
                    <form action="{{ url('/admin/rt-transaction/' . $transaction->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="payment_date">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                   id="payment_date" name="payment_date" 
                                   value="{{ old('payment_date', $transaction->payment_date->format('Y-m-d')) }}" required>
                            @error('payment_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="type">Jenis Transaksi <span class="text-danger">*</span></label>
                            <select class="form-control @error('type') is-invalid @enderror" 
                                    id="type" name="type" required>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="income" {{ old('type', $transaction->isIncome() ? 'income' : 'expense') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                                <option value="expense" {{ old('type', $transaction->isIncome() ? 'income' : 'expense') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Nama Transaksi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" placeholder="Contoh: Pembelian Perlengkapan" 
                                   value="{{ old('name', $transaction->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="amount">Jumlah <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                       id="amount" name="amount" placeholder="0" 
                                       value="{{ old('amount', $transaction->isIncome() ? $transaction->credit : $transaction->debit) }}" 
                                       min="0" step="0.01" required>
                                @error('amount')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4" 
                                      placeholder="Masukkan detail transaksi">{{ old('description', $transaction->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="proof_file">Bukti Transaksi</label>
                            @if($transaction->file_path)
                                <div class="text-muted mb-2">
                                    <small><i class="fa fa-check-circle text-success"></i> Bukti transaksi sudah diupload</small>
                                </div>
                            @endif
                            <input type="file" class="form-control-file @error('proof_file') is-invalid @enderror" 
                                   id="proof_file" name="proof_file" accept="image/*,.pdf">
                            <small class="form-text text-muted">
                                Format: JPEG, PNG, JPG, PDF. Maksimal 5MB. Kosongkan jika tidak ingin mengubah bukti.
                            </small>
                            @error('proof_file')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <hr>
                        <div class="form-group d-flex flex-wrap" style="gap: .5rem;">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="{{ url('/admin/rt-transaction') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-info">
                        <h6><i class="fa fa-info-circle"></i> Informasi</h6>
                        <ul class="pl-3 mb-0">
                            <li>Edit data transaksi dengan benar</li>
                            <li>Upload bukti baru jika perlu mengganti</li>
                            <li>Semua perubahan akan tercatat dalam log sistem</li>
                        </ul>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h6>Informasi Transaksi</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td>Dibuat Oleh</td>
                                    <td>{{ $transaction->created_by ?? 'Sistem' }}</td>
                                </tr>
                                <tr>
                                    <td>Dibuat Pada</td>
                                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td>Status</td>
                                    <td><span class="badge badge-success">{{ ucfirst($transaction->status) }}</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
