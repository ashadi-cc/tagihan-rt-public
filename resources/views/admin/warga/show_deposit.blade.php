@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Detail Deposit {{ $user->name }} ({{ $user->blok }})</h1>
    @include('admin.master._header_warga_detail', ['active' => 'deposit', 'userId' => $user->id])

    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-info"><i class="fa fa-info-circle"></i></span>
            <span class="app-card-title">Detail Deposit #{{ $deposit->id }}</span>
            <a href="{{ route('admin.warga.deposit', ['userID' => $user->id]) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
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

            <div class="row">
                <div class="col-md-8">
                    <!-- Mobile: stacked label/value rows -->
                    <div class="d-md-none">
                        <div class="mb-3">
                            <div class="text-muted small">Status</div>
                            <div>
                                @if($deposit->status == 'verified')
                                    <span class="badge badge-success badge-lg">Terverifikasi</span>
                                @elseif($deposit->status == 'pending')
                                    <span class="badge badge-warning badge-lg">Menunggu Verifikasi</span>
                                @elseif($deposit->status == 'rejected')
                                    <span class="badge badge-danger badge-lg">Ditolak</span>
                                @else
                                    <span class="badge badge-secondary badge-lg">{{ ucfirst($deposit->status) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Warga</div>
                            <div><strong>{{ $deposit->user_name }} ({{ $deposit->user_blok }}) {{ $user->phone_number }}</strong></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Tanggal Deposit</div>
                            <div>{{ $deposit->payment_date->format('d F Y') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Metode Pembayaran</div>
                            <div>
                                <strong>{{ $deposit->payment_name }}</strong>
                                @if($deposit->payment_metadata)
                                    @php
                                        $metadata_m = is_string($deposit->payment_metadata)
                                            ? json_decode($deposit->payment_metadata, true)
                                            : $deposit->payment_metadata;
                                    @endphp
                                    @if(!empty($metadata_m['bank_name']))
                                        <br><small class="text-muted">
                                            Bank: {{ $metadata_m['bank_name'] }}
                                            @if(!empty($metadata_m['account_number']))
                                                <br>No. Rekening: {{ $metadata_m['account_number'] }}
                                            @endif
                                            @if(!empty($metadata_m['account_name']))
                                                <br>Atas Nama: {{ $metadata_m['account_name'] }}
                                            @endif
                                        </small>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Informasi Pengirim</div>
                            <div>
                                @if($deposit->payment_info_metadata)
                                    @php
                                        $info_m = is_string($deposit->payment_info_metadata)
                                            ? json_decode($deposit->payment_info_metadata, true)
                                            : $deposit->payment_info_metadata;
                                    @endphp
                                    @if(!empty($info_m['from_bank']) || !empty($info_m['from_account_number']) || !empty($info_m['from_account_name']))
                                        @if(!empty($info_m['from_bank']))
                                            Bank: {{ $info_m['from_bank'] }}<br>
                                        @endif
                                        @if(!empty($info_m['from_account_number']))
                                            No. Rekening: {{ $info_m['from_account_number'] }}<br>
                                        @endif
                                        @if(!empty($info_m['from_account_name']))
                                            Atas Nama: {{ $info_m['from_account_name'] }}
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Jumlah Deposit</div>
                            <div>
                                <h4 class="mb-0 text-primary">
                                    <strong>Rp {{ number_format($deposit->payment_amount, 0, ',', '.') }}</strong>
                                </h4>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Bukti Transfer</div>
                            <div>
                                @if($deposit->file_path && $deposit->file_driver)
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#proofModal">
                                        <i class="fa fa-image"></i> Lihat Bukti Transfer
                                    </button>
                                @else
                                    <span class="text-muted">Tidak ada bukti transfer</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Catatan</div>
                            <div>
                                @if($deposit->notes)
                                    {!! nl2br(e($deposit->notes)) !!}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Dibuat Oleh</div>
                            <div>
                                {{ $deposit->created_by_user_name ?? '-' }}
                                <br><small class="text-muted">{{ $deposit->created_at->format('d F Y H:i') }}</small>
                            </div>
                        </div>
                        @if($deposit->verified_at)
                        <div class="mb-3">
                            <div class="text-muted small">
                                @if($deposit->status == 'verified')
                                    Diverifikasi Oleh
                                @else
                                    Diproses Oleh
                                @endif
                            </div>
                            <div>
                                {{ $deposit->verified_by_user_name ?? '-' }}
                                <br><small class="text-muted">{{ $deposit->verified_at->format('d F Y H:i') }}</small>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Tablet/desktop: bordered table -->
                    <table class="table table-bordered d-none d-md-table">
                        <tr>
                            <th width="30%">Status</th>
                            <td>
                                @if($deposit->status == 'verified')
                                    <span class="badge badge-success badge-lg">Terverifikasi</span>
                                @elseif($deposit->status == 'pending')
                                    <span class="badge badge-warning badge-lg">Menunggu Verifikasi</span>
                                @elseif($deposit->status == 'rejected')
                                    <span class="badge badge-danger badge-lg">Ditolak</span>
                                @else
                                    <span class="badge badge-secondary badge-lg">{{ ucfirst($deposit->status) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Warga</th>
                            <td><strong>{{ $deposit->user_name }} ({{ $deposit->user_blok }}) {{ $user->phone_number }}</strong></td>
                        </tr>
                        <tr>
                            <th>Tanggal Deposit</th>
                            <td>{{ $deposit->payment_date->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <th>Metode Pembayaran</th>
                            <td>
                                <strong>{{ $deposit->payment_name }}</strong>
                                @if($deposit->payment_metadata)
                                    @php
                                        $metadata = is_string($deposit->payment_metadata) 
                                            ? json_decode($deposit->payment_metadata, true) 
                                            : $deposit->payment_metadata;
                                    @endphp
                                    @if(!empty($metadata['bank_name']))
                                        <br><small class="text-muted">
                                            Bank: {{ $metadata['bank_name'] }}
                                            @if(!empty($metadata['account_number']))
                                                <br>No. Rekening: {{ $metadata['account_number'] }}
                                            @endif
                                            @if(!empty($metadata['account_name']))
                                                <br>Atas Nama: {{ $metadata['account_name'] }}
                                            @endif
                                        </small>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Informasi Pengirim</th>
                            <td>
                                @if($deposit->payment_info_metadata)
                                    @php
                                        $info = is_string($deposit->payment_info_metadata) 
                                            ? json_decode($deposit->payment_info_metadata, true) 
                                            : $deposit->payment_info_metadata;
                                    @endphp
                                    @if(!empty($info['from_bank']) || !empty($info['from_account_number']) || !empty($info['from_account_name']))
                                        @if(!empty($info['from_bank']))
                                            Bank: {{ $info['from_bank'] }}<br>
                                        @endif
                                        @if(!empty($info['from_account_number']))
                                            No. Rekening: {{ $info['from_account_number'] }}<br>
                                        @endif
                                        @if(!empty($info['from_account_name']))
                                            Atas Nama: {{ $info['from_account_name'] }}
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Jumlah Deposit</th>
                            <td>
                                <h4 class="mb-0 text-primary">
                                    <strong>Rp {{ number_format($deposit->payment_amount, 0, ',', '.') }}</strong>
                                </h4>
                            </td>
                        </tr>
                        <tr>
                            <th>Bukti Transfer</th>
                            <td>
                                @if($deposit->file_path && $deposit->file_driver)
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#proofModal">
                                        <i class="fa fa-image"></i> Lihat Bukti Transfer
                                    </button>
                                @else
                                    <span class="text-muted">Tidak ada bukti transfer</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Catatan</th>
                            <td>
                                @if($deposit->notes)
                                    {!! nl2br(e($deposit->notes)) !!}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Dibuat Oleh</th>
                            <td>
                                {{ $deposit->created_by_user_name ?? '-' }}
                                <br><small class="text-muted">{{ $deposit->created_at->format('d F Y H:i') }}</small>
                            </td>
                        </tr>
                        @if($deposit->verified_at)
                        <tr>
                            <th>
                                @if($deposit->status == 'verified')
                                    Diverifikasi Oleh
                                @else
                                    Diproses Oleh
                                @endif
                            </th>
                            <td>
                                {{ $deposit->verified_by_user_name ?? '-' }}
                                <br><small class="text-muted">{{ $deposit->verified_at->format('d F Y H:i') }}</small>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>

                <div class="col-md-4">
                    <!-- Action Panel -->
                    <div class="card mb-3">
                        <div class="app-card-header">
                            <span class="icon-chip icon-chip-primary"><i class="fa fa-cog"></i></span>
                            <span class="app-card-title">Aksi</span>
                        </div>
                        <div class="card-body">
                            @if($deposit->status == 'pending')
                                <!-- Approve Button -->
                                <button type="button" class="btn btn-success btn-block mb-2" data-toggle="modal" data-target="#approveModal">
                                    <i class="fa fa-check"></i> Setujui Deposit
                                </button>

                                <!-- Reject Button -->
                                <button type="button" class="btn btn-danger btn-block mb-3" data-toggle="modal" data-target="#rejectModal">
                                    <i class="fa fa-times"></i> Tolak Deposit
                                </button>

                                <hr>
                            @endif

                            @if($deposit->status == 'verified')
                                <div class="alert alert-success mb-3">
                                    <i class="fa fa-check-circle"></i> Deposit ini sudah diverifikasi
                                </div>
                            @elseif($deposit->status == 'rejected')
                                <div class="alert alert-danger mb-3">
                                    <i class="fa fa-times-circle"></i> Deposit ini sudah ditolak
                                </div>
                            @endif

                            <!-- Edit Button - Only show if not verified -->
                            @if($deposit->status !== 'verified')
                                <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#editModal">
                                    <i class="fa fa-edit"></i> Edit Jumlah & Catatan
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Info Panel -->
                    <div class="card">
                        <div class="app-card-header">
                            <span class="icon-chip icon-chip-info"><i class="fa fa-info-circle"></i></span>
                            <span class="app-card-title">Informasi</span>
                        </div>
                        <div class="card-body">
                            <small class="text-muted">
                                <i class="fa fa-lightbulb-o"></i> 
                                @if($deposit->status == 'pending')
                                    Anda dapat menyetujui atau menolak deposit ini. Setelah diproses, status tidak dapat diubah kembali.
                                @elseif($deposit->status == 'verified')
                                    Deposit yang sudah diverifikasi tidak dapat diproses ulang atau diedit.
                                @else
                                    Deposit yang sudah ditolak tidak dapat diproses ulang.
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($deposit->logs()->count() > 0)
    <!-- Activity Log Section -->
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-info"><i class="fa fa-history"></i></span>
            <span class="app-card-title">Riwayat Aktivitas</span>
        </div>
        <div class="card-body">
            <!-- Mobile: stacked cards -->
            <div class="d-md-none border rounded">
                <div class="list-group list-group-flush">
                    @foreach($deposit->logs()->orderBy('created_at', 'desc')->get() as $log)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong class="small">{{ $log->username }}</strong>
                            <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <div class="small">{!! nl2br(e($log->description)) !!}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th width="20%">Tanggal</th>
                            <th width="20%">User</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deposit->logs()->orderBy('created_at', 'desc')->get() as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->username }}</td>
                            <td>{!! nl2br(e($log->description)) !!}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.warga.deposit.approve', ['userID' => $user->id, 'depositID' => $deposit->id]) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="approveModalLabel">Konfirmasi Verifikasi Deposit</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> 
                            <strong>Informasi!</strong> Deposit yang sudah diverifikasi tidak dapat dibatalkan.
                        </div>
                        <p class="mb-0">Apakah Anda yakin ingin menyetujui deposit ini?</p>
                        <div class="mt-3 p-3 bg-light rounded">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td width="40%"><strong>Warga:</strong></td>
                                    <td>{{ $user->name }} ({{ $user->blok }})</td>
                                </tr>
                                <tr>
                                    <td><strong>Jumlah:</strong></td>
                                    <td class="text-success"><strong>Rp {{ number_format($deposit->payment_amount, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal:</strong></td>
                                    <td>{{ $deposit->payment_date->format('d F Y') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fa fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check"></i> Ya, Setujui Deposit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.warga.deposit.update', ['userID' => $user->id, 'depositID' => $deposit->id]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Deposit</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="amount_paid">Jumlah Deposit <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   id="amount_paid" 
                                   name="amount_paid" 
                                   value="{{ old('amount_paid', $deposit->payment_amount) }}"
                                   min="1"
                                   step="1"
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="notes">Catatan</label>
                            <textarea class="form-control" 
                                      id="notes" 
                                      name="notes" 
                                      rows="4"
                                      maxlength="1000">{{ old('notes', $deposit->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.warga.deposit.reject', ['userID' => $user->id, 'depositID' => $deposit->id]) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="rejectModalLabel">Tolak Deposit</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i> 
                            <strong>Perhatian!</strong> Deposit yang ditolak tidak dapat diverifikasi kembali.
                        </div>
                        <div class="form-group">
                            <label for="reject_reason">Alasan Penolakan (Opsional)</label>
                            <textarea class="form-control" 
                                      id="reject_reason" 
                                      name="reject_reason" 
                                      rows="3"
                                      maxlength="1000"
                                      placeholder="Jelaskan alasan penolakan deposit ini..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-times"></i> Tolak Deposit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($deposit->file_path && $deposit->file_driver)
    <!-- Proof Modal -->
    <div class="modal fade" id="proofModal" tabindex="-1" role="dialog" aria-labelledby="proofModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="proofModalLabel">Bukti Transfer - Deposit #{{ $deposit->id }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div id="loading-spinner" class="mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat bukti transfer...</p>
                    </div>
                    <img id="proof-image" src="" alt="Bukti Transfer" class="img-fluid" style="display: none; max-height: 70vh;">
                    <div id="pdf-container" style="display: none;">
                        <p class="text-muted">File PDF tidak dapat ditampilkan di sini.</p>
                        <a href="{{ route('file.show', ['driver' => $deposit->file_driver]) }}?path={{ urlencode($deposit->file_path) }}" 
                           target="_blank" 
                           class="btn btn-primary">
                            <i class="fa fa-download"></i> Download PDF
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <a href="{{ route('file.show', ['driver' => $deposit->file_driver]) }}?path={{ urlencode($deposit->file_path) }}" 
                       target="_blank" 
                       class="btn btn-primary">
                        <i class="fa fa-external-link"></i> Buka di Tab Baru
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('scripts')
    @if($deposit->file_path && $deposit->file_driver)
    <script>
        $(document).ready(function() {
            $('#proofModal').on('show.bs.modal', function (e) {
                $('#loading-spinner').show();
                $('#proof-image').hide();
                $('#pdf-container').hide();
                
                var fileUrl = "{{ route('file.show', ['driver' => $deposit->file_driver]) }}?path={{ urlencode($deposit->file_path) }}";
                var fileExtension = "{{ strtolower(pathinfo($deposit->file_path, PATHINFO_EXTENSION)) }}";
                
                if (fileExtension === 'pdf') {
                    // Show PDF download option
                    $('#loading-spinner').hide();
                    $('#pdf-container').show();
                } else {
                    // Load image
                    var img = new Image();
                    img.onload = function() {
                        $('#proof-image').attr('src', fileUrl);
                        $('#loading-spinner').hide();
                        $('#proof-image').fadeIn();
                    };
                    img.onerror = function() {
                        $('#loading-spinner').hide();
                        $('#proof-image').parent().append('<div class="alert alert-danger">Gagal memuat gambar</div>');
                    };
                    img.src = fileUrl;
                }
            });

            $('#proofModal').on('hidden.bs.modal', function (e) {
                $('#proof-image').attr('src', '');
            });
        });
    </script>
    @endif
@endsection
