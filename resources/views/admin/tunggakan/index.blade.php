@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Buat Tunggakan Iuran</h1>
    @include('admin.master._header_warga_detail', ['active' => 'tunggakan', 'userId' => $user->id])

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <strong>Catatan:</strong> Form ini digunakan untuk mencatat tunggakan iuran bulanan dari periode kepengurusan sebelumnya yang belum terbayar oleh warga <strong>{{ $user->name }} ({{ $user->blok }})</strong>.
            </div>

            <form action="{{ url('/admin/master/warga/' . $user->id . '/tunggakan') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="billing_generated_date">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" 
                           class="form-control @error('billing_generated_date') is-invalid @enderror" 
                           id="billing_generated_date" 
                           name="billing_generated_date" 
                           value="{{ old('billing_generated_date', date('Y-m-d')) }}"
                           required>
                    @error('billing_generated_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="billing_name">Nama Tunggakan <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('billing_name') is-invalid @enderror" 
                           id="billing_name" 
                           name="billing_name" 
                           placeholder="Contoh: Iuran Keamanan Januari 2024"
                           value="{{ old('billing_name') }}"
                           required>
                    @error('billing_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="billing_amount">Jumlah <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Rp</span>
                        </div>
                        <input type="number" 
                               class="form-control @error('billing_amount') is-invalid @enderror" 
                               id="billing_amount" 
                               name="billing_amount" 
                               placeholder="0"
                               value="{{ old('billing_amount') }}"
                               min="0"
                               step="1"
                               required>
                        @error('billing_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Keterangan</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" 
                              name="description" 
                              rows="4"
                              placeholder="Masukkan keterangan tambahan (opsional)">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group d-flex flex-wrap" style="gap: .5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Simpan Tunggakan
                    </button>
                    <a href="{{ url('/admin/master/warga/view/' . $user->id) }}" class="btn btn-outline-secondary">
                        <i class="fa fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-warning"><i class="fa fa-list"></i></span>
            <span class="app-card-title">Daftar Tunggakan</span>
        </div>
        <div class="card-body">
            @if($list->isEmpty())
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Belum ada data tunggakan untuk warga ini.
                </div>
            @else
                <!-- Mobile: stacked cards -->
                <div class="d-md-none list-group list-group-flush mb-3">
                    @foreach($list as $index => $item)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong>{{ $item->billing_name }}</strong>
                            @if($item->payment_amount > 0)
                                <span class="badge badge-success">Rp {{ number_format($item->payment_amount, 0, ',', '.') }}</span>
                            @else
                                <span class="badge badge-secondary">Belum Lunas</span>
                            @endif
                        </div>
                        <div class="text-muted small mb-2">{{ $item->billing_generated_date->format('d M Y') }}</div>
                        <div class="mb-2">Rp {{ number_format($item->billing_amount, 0, ',', '.') }}</div>
                        @if($item->description)
                            <div class="text-muted small mb-2">{{ $item->description }}</div>
                        @endif
                        @if($item->payment_amount == 0)
                            <form id="delete-form-m-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tunggakan/' . $item->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                            </form>
                            <button type="button" class="btn btn-danger btn-sm btn-delete-tunggakan" data-form-id="delete-form-m-{{ $item->id }}" data-tunggakan-name="{{ $item->billing_name }}">
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        @else
                            <button type="button" class="btn btn-secondary btn-sm" disabled>
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        @endif
                    </div>
                    @endforeach
                </div>

                <!-- Tablet/desktop: table -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="12%">Tanggal</th>
                                <th width="27%">Nama Tunggakan</th>
                                <th width="13%">Jumlah</th>
                                <th width="13%">Terbayar</th>
                                <th width="23%">Keterangan</th>
                                <th width="7%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($list as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->billing_generated_date->format('d M Y') }}</td>
                                    <td>{{ $item->billing_name }}</td>
                                    <td class="text-right">Rp {{ number_format($item->billing_amount, 0, ',', '.') }}</td>
                                    <td class="text-right">
                                        @if($item->payment_amount > 0)
                                            <span class="badge badge-success">Rp {{ number_format($item->payment_amount, 0, ',', '.') }}</span>
                                        @else
                                            <span class="badge badge-secondary">Belum Lunas</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->description ?: '-' }}</td>
                                    <td class="text-nowrap">
                                        @if($item->payment_amount == 0)
                                            <form id="delete-form-{{ $item->id }}" action="{{ url('/admin/master/warga/' . $user->id . '/tunggakan/' . $item->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-tunggakan" data-form-id="delete-form-{{ $item->id }}" data-tunggakan-name="{{ $item->billing_name }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-secondary btn-sm" disabled title="Tidak dapat dihapus karena sudah ada pembayaran">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fa fa-exclamation-triangle"></i> Konfirmasi Hapus
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus tunggakan ini?</p>
                    <p class="mb-0"><strong id="tunggakan-name"></strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fa fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let formToSubmit = null;

        // Handle delete button clicks
        document.querySelectorAll('.btn-delete-tunggakan').forEach(function(button) {
            button.addEventListener('click', function() {
                const formId = this.getAttribute('data-form-id');
                const tunggakanName = this.getAttribute('data-tunggakan-name');
                
                formToSubmit = document.getElementById(formId);
                document.getElementById('tunggakan-name').textContent = tunggakanName;
                
                $('#deleteModal').modal('show');
            });
        });

        // Handle confirm delete button
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });
    </script>
@endsection
