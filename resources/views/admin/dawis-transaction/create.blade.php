@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Laporan Keuangan Dawis</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/dawis-transaction') }}">Daftar Transaksi Dawis</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah Transaksi</li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>Tambah Transaksi Baru</h5>
                    <form action="{{ url('/admin/dawis-transaction/create') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label for="payment_date">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror"
                                   id="payment_date" name="payment_date"
                                   value="{{ old('payment_date', date('Y-m-d')) }}" required>
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
                                <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                                <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
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
                                   id="name" name="name" placeholder="Contoh: Iuran Kas Dawis"
                                   value="{{ old('name') }}" required>
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
                                       value="{{ old('amount') }}" min="0" step="0.01" required>
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
                                      placeholder="Masukkan detail transaksi">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="proof_file">Bukti Transaksi</label>
                            <input type="file" class="form-control-file @error('proof_file') is-invalid @enderror"
                                   id="proof_file" name="proof_file" accept="image/*,.pdf">
                            <small class="form-text text-muted">
                                Format: JPEG, PNG, JPG, PDF. Maksimal 5MB.
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
                                <i class="fa fa-save"></i> Simpan
                            </button>
                            <a href="{{ url('/admin/dawis-transaction') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-info">
                        <h6><i class="fa fa-info-circle"></i> Informasi</h6>
                        <ul class="pl-3 mb-0">
                            <li>Pilih jenis transaksi (Pemasukan atau Pengeluaran)</li>
                            <li>Isi nama dan jumlah transaksi dengan benar</li>
                            <li>Upload bukti transaksi jika ada</li>
                            <li>Semua transaksi akan tersimpan dengan status "Terverifikasi"</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
