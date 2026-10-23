@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Pemasukan & Pengeluaran RT</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/rt-transaction') }}">Daftar Transaksi</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detail Transaksi</li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="app-card-header">
                    <span class="icon-chip {{ $transaction->isIncome() ? 'icon-chip-success' : 'icon-chip-danger' }}">
                        <i class="fa {{ $transaction->isIncome() ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                    </span>
                    <span class="app-card-title">Detail Transaksi</span>
                </div>
                <div class="card-body">
                    <!-- Mobile: stacked label/value rows -->
                    <div class="d-md-none">
                        <div class="mb-3">
                            <div class="text-muted small">Jenis Transaksi</div>
                            <div>
                                @if($transaction->isIncome())
                                    <span class="badge badge-success badge-lg">Pemasukan</span>
                                @else
                                    <span class="badge badge-danger badge-lg">Pengeluaran</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Tanggal Transaksi</div>
                            <div>{{ $transaction->payment_date->format('d F Y') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Nama Transaksi</div>
                            <div class="font-weight-bold">{{ $transaction->name }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Jumlah</div>
                            <div class="h4 mb-0">
                                @if($transaction->isIncome())
                                    <span class="text-success">+ Rp {{ number_format($transaction->credit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-danger">- Rp {{ number_format($transaction->debit, 0, ',', '.') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Deskripsi</div>
                            <div>{{ $transaction->description ?? '-' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Status</div>
                            <div><span class="badge badge-success">{{ ucfirst($transaction->status) }}</span></div>
                        </div>
                        @if($transaction->file_path)
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Bukti Transaksi</div>
                            @if(in_array(pathinfo($transaction->file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#proofModal">
                                    <i class="fa fa-image"></i> Lihat Bukti
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#proofModal">
                                    <i class="fa fa-file"></i> Lihat Bukti
                                </button>
                            @endif
                        </div>
                        @endif
                        <div class="mb-3">
                            <div class="text-muted small">Dibuat Oleh</div>
                            <div>{{ $transaction->created_by ?? 'Sistem' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Tanggal Dibuat</div>
                            <div>{{ $transaction->created_at->format('d F Y H:i') }}</div>
                        </div>
                        @if($transaction->updated_at != $transaction->created_at)
                        <div class="mb-3">
                            <div class="text-muted small">Terakhir Diupdate</div>
                            <div>{{ $transaction->updated_at->format('d F Y H:i') }}</div>
                        </div>
                        @endif
                    </div>

                    <!-- Tablet/desktop: bordered table -->
                    <table class="table table-bordered d-none d-md-table">
                        <tr>
                            <th width="30%">Jenis Transaksi</th>
                            <td>
                                @if($transaction->isIncome())
                                    <span class="badge badge-success badge-lg">Pemasukan</span>
                                @else
                                    <span class="badge badge-danger badge-lg">Pengeluaran</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Tanggal Transaksi</th>
                            <td>{{ $transaction->payment_date->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <th>Nama Transaksi</th>
                            <td><strong>{{ $transaction->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>Jumlah</th>
                            <td>
                                <h4 class="mb-0">
                                    @if($transaction->isIncome())
                                        <span class="text-success">+ Rp {{ number_format($transaction->credit, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-danger">- Rp {{ number_format($transaction->debit, 0, ',', '.') }}</span>
                                    @endif
                                </h4>
                            </td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td>{{ $transaction->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span class="badge badge-success">{{ ucfirst($transaction->status) }}</span></td>
                        </tr>
                        @if($transaction->file_path)
                        <tr>
                            <th>Bukti Transaksi</th>
                            <td>
                                @if(in_array(pathinfo($transaction->file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#proofModal">
                                        <i class="fa fa-image"></i> Lihat Bukti
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#proofModal">
                                        <i class="fa fa-file"></i> Lihat Bukti
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Dibuat Oleh</th>
                            <td>{{ $transaction->created_by ?? 'Sistem' }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Dibuat</th>
                            <td>{{ $transaction->created_at->format('d F Y H:i') }}</td>
                        </tr>
                        @if($transaction->updated_at != $transaction->created_at)
                        <tr>
                            <th>Terakhir Diupdate</th>
                            <td>{{ $transaction->updated_at->format('d F Y H:i') }}</td>
                        </tr>
                        @endif
                    </table>

                    <hr>
                    <div class="d-flex flex-wrap" style="gap: .5rem;">
                        @if(strtolower($transaction->created_by ?? '') !== 'system')
                            <a href="{{ url('/admin/rt-transaction/' . $transaction->id . '/edit') }}" class="btn btn-warning">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        @else
                            <button type="button" class="btn btn-secondary" disabled>
                                <i class="fa fa-lock"></i> Transaksi Sistem
                            </button>
                        @endif
                        <a href="{{ url('/admin/rt-transaction') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6><i class="fa fa-info-circle"></i> Informasi</h6>
                    <p class="small mb-0">
                        Transaksi ini telah terverifikasi dan tercatat dalam sistem keuangan RT. 
                        Semua perubahan akan tercatat dalam log sistem.
                    </p>
                </div>
            </div>

            @if($transaction->logs && $transaction->logs->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Riwayat Log</h6>
                </div>
                <div class="card-body">
                    @foreach($transaction->logs->take(5) as $log)
                    <div class="mb-3">
                        <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                        <p class="mb-0 small">{{ $log->description }}</p>
                        <small class="text-muted">oleh: {{ $log->username }}</small>
                    </div>
                    @if(!$loop->last)
                    <hr>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($transaction->file_path && $transaction->file_driver)
    <!-- Proof Modal -->
    <div class="modal fade" id="proofModal" tabindex="-1" role="dialog" aria-labelledby="proofModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="proofModalLabel">Bukti Transaksi - {{ $transaction->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    @if(in_array(pathinfo($transaction->file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
                        <div id="loading-spinner" class="mb-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2">Memuat bukti...</p>
                        </div>
                        <img id="proof-image" src="" alt="Bukti Transaksi" class="img-fluid" style="display: none; max-height: 70vh;">
                    @else
                        <iframe id="proof-pdf" src="" style="width: 100%; height: 70vh; border: none;"></iframe>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-exclamation-triangle"></i> Konfirmasi Hapus
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus transaksi ini?</p>
                    <p class="mb-0"><strong>{{ $transaction->name }}</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" onclick="deleteTransaction()">
                        <i class="fa fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function confirmDelete() {
        $('#deleteModal').modal('show');
    }

    function deleteTransaction() {
        fetch('/admin/rt-transaction/{{ $transaction->id }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            $('#deleteModal').modal('hide');
            if (data.success) {
                window.location.href = '/admin/rt-transaction';
            }
        })
        .catch(error => {
            $('#deleteModal').modal('hide');
            console.error('Error:', error);
        });
    }

    @if($transaction->file_path && $transaction->file_driver)
    $(document).ready(function() {
        $('#proofModal').on('show.bs.modal', function (e) {
            var fileExtension = '{{ pathinfo($transaction->file_path, PATHINFO_EXTENSION) }}';
            var fileUrl = "{{ route('file.show', ['driver' => $transaction->file_driver]) }}?path={{ urlencode($transaction->file_path) }}";
            
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension.toLowerCase())) {
                $('#loading-spinner').show();
                $('#proof-image').hide();
                
                $('#proof-image').attr('src', fileUrl);
                $('#proof-image').on('load', function() {
                    $('#loading-spinner').hide();
                    $('#proof-image').show();
                });
            } else {
                $('#proof-pdf').attr('src', fileUrl);
            }
        });
    });
    @endif
</script>
@endsection
