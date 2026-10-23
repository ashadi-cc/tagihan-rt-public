@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Detail Pembayaran {{ $user->name }} ({{ $user->blok }})</h1>
    @include('admin.master._header_warga_detail', ['active' => 'pembayaran', 'userId' => $user->id])

    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-info"><i class="fa fa-info-circle"></i></span>
            <span class="app-card-title">Detail Pembayaran #{{ $pembayaran->id }}</span>
            <a href="{{ url('/admin/master/warga/' . $user->id . '/pembayaran') }}" class="btn btn-sm btn-outline-secondary">
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

            <div class="row">
                <div class="col-md-8">
                    <!-- Mobile: stacked label/value rows -->
                    <div class="d-md-none">
                        <div class="mb-3">
                            <div class="text-muted small">Status</div>
                            <div>
                                @if($pembayaran->status == 'verified')
                                    <span class="badge badge-success badge-lg">Terverifikasi</span>
                                @elseif($pembayaran->status == 'pending')
                                    <span class="badge badge-warning badge-lg">Menunggu Verifikasi</span>
                                @elseif($pembayaran->status == 'rejected')
                                    <span class="badge badge-danger badge-lg">Ditolak</span>
                                @else
                                    <span class="badge badge-secondary badge-lg">{{ ucfirst($pembayaran->status) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Warga</div>
                            <div><strong>{{ $pembayaran->user_name }} ({{ $pembayaran->user_blok }}) {{ $user->phone_number }}</strong></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Tanggal Pembayaran</div>
                            <div>{{ $pembayaran->payment_date->format('d F Y') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Tipe Pembayaran</div>
                            <div>
                                <strong>{{ $pembayaran->formatted_payment_type }}</strong>
                                @if($pembayaran->formatted_period)
                                    <br><small class="text-muted">Periode: {{ $pembayaran->formatted_period }}</small>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Metode Pembayaran</div>
                            <div>
                                <strong>{{ $pembayaran->payment_name }}</strong>
                                @if($pembayaran->payment_metadata)
                                    @php
                                        $payment_metadata_m = $pembayaran->old_payment_id ? $pembayaran->payment->metadata : $pembayaran->payment_metadata;
                                        $metadata_m = is_string($payment_metadata_m)
                                            ? json_decode($payment_metadata_m, true)
                                            : $payment_metadata_m;
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
                            @if($pembayaran->payment_type !== 'deposit_usage' && $availablePayments->count() > 0)
                                <div class="dropdown mt-2">
                                    <button type="button" class="btn btn-sm btn-warning dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-exchange"></i> Pindah Payment
                                    </button>
                                    <div class="dropdown-menu">
                                        <h6 class="dropdown-header">Pindahkan ke:</h6>
                                        @foreach($availablePayments as $availPayment)
                                            <a class="dropdown-item btn-move-payment" href="#"
                                               data-pembayaran-id="{{ $pembayaran->id }}"
                                               data-new-payment-id="{{ $availPayment->id }}"
                                               data-new-payment-name="{{ $availPayment->name }}">
                                                {{ $availPayment->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Informasi Pengirim</div>
                            <div>
                                @if($pembayaran->payment_info_metadata)
                                    @php
                                        $info_m = is_string($pembayaran->payment_info_metadata)
                                            ? json_decode($pembayaran->payment_info_metadata, true)
                                            : $pembayaran->payment_info_metadata;
                                    @endphp
                                    @if(!empty($info_m['from_bank']) || !empty($info_m['from_account_number']) || !empty($info_m['from_account_name']))
                                        <small class="text-muted">
                                        @if(!empty($info_m['from_bank']))
                                            Bank: {{ $info_m['from_bank'] }}<br>
                                        @endif
                                        @if(!empty($info_m['from_account_number']))
                                            No. Rekening: {{ $info_m['from_account_number'] }}<br>
                                        @endif
                                        @if(!empty($info_m['from_account_name']))
                                            Atas Nama: {{ $info_m['from_account_name'] }}
                                        @endif
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Jumlah Pembayaran</div>
                            <div>
                                @if($pembayaran->status == 'verified')
                                    <h4 class="mb-0 text-primary">
                                        <strong>Rp {{ number_format($pembayaran->payment_amount, 0, ',', '.') }}</strong>
                                    </h4>
                                @else
                                    <div class="d-flex align-items-start">
                                        <h4 class="mb-0 {{ $pembayaran->excess_amount > 0 ? 'text-danger' : 'text-primary' }}">
                                            <strong>Rp {{ number_format($pembayaran->payment_amount + ($pembayaran->excess_amount ?? 0), 0, ',', '.') }}</strong>
                                        </h4>
                                        @if($pembayaran->status == 'pending' && $totalPendingAmount > 0)
                                            <a href="#"
                                               class="text-warning ml-2"
                                               data-toggle="modal"
                                               data-target="#editPaymentAmountModal"
                                               style="font-size: 1.2rem; text-decoration: none;">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endif
                                    </div>
                                    @if(isset($pembayaran->excess_amount) && $pembayaran->excess_amount > 0)
                                        <div class="alert alert-warning mt-2 mb-0" role="alert">
                                            <i class="fa fa-exclamation-triangle"></i>
                                            <strong>Kelebihan Pembayaran: Rp {{ number_format($pembayaran->excess_amount, 0, ',', '.') }}</strong>
                                            <br>
                                            <small>
                                                Pembayaran melebihi jumlah tagihan. Kelebihan pembayaran akan dijadikan deposit secara otomatis.
                                            </small>
                                            <hr class="my-2">
                                            <strong>Tagihan Iuran: Rp {{ number_format($pembayaran->payment_amount, 0, ',', '.') }}</strong>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Bukti Transfer</div>
                            <div>
                                @if($pembayaran->file_path && $pembayaran->file_driver)
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
                                @if($pembayaran->notes)
                                    {!! nl2br(e($pembayaran->notes)) !!}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Dibuat Oleh</div>
                            <div>
                                {{ $pembayaran->created_by_user_name ?? '-' }}
                                <br><small class="text-muted">{{ $pembayaran->created_at->format('d F Y H:i') }}</small>
                            </div>
                        </div>
                        @if($pembayaran->verified_at)
                        <div class="mb-3">
                            <div class="text-muted small">
                                @if($pembayaran->status == 'verified')
                                    Diverifikasi Oleh
                                @else
                                    Diproses Oleh
                                @endif
                            </div>
                            <div>
                                {{ $pembayaran->verified_by_user_name ?? '-' }}
                                <br><small class="text-muted">{{ $pembayaran->verified_at->format('d F Y H:i') }}</small>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Tablet/desktop: bordered table -->
                    <table class="table table-bordered d-none d-md-table">
                        <tr>
                            <th width="30%">Status</th>
                            <td>
                                @if($pembayaran->status == 'verified')
                                    <span class="badge badge-success badge-lg">Terverifikasi</span>
                                @elseif($pembayaran->status == 'pending')
                                    <span class="badge badge-warning badge-lg">Menunggu Verifikasi</span>
                                @elseif($pembayaran->status == 'rejected')
                                    <span class="badge badge-danger badge-lg">Ditolak</span>
                                @else
                                    <span class="badge badge-secondary badge-lg">{{ ucfirst($pembayaran->status) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Warga</th>
                            <td><strong>{{ $pembayaran->user_name }} ({{ $pembayaran->user_blok }}) {{ $user->phone_number }}</strong></td>
                        </tr>
                        <tr>
                            <th>Tanggal Pembayaran</th>
                            <td>{{ $pembayaran->payment_date->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <th>Tipe Pembayaran</th>
                            <td>
                                <strong>{{ $pembayaran->formatted_payment_type }}</strong>
                                @if($pembayaran->formatted_period)
                                    <br><small class="text-muted">Periode: {{ $pembayaran->formatted_period }}</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Metode Pembayaran</th>
                            <td>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $pembayaran->payment_name }}</strong>
                                        @if($pembayaran->payment_metadata)
                                            @php
                                                $payment_metadata = $pembayaran->old_payment_id ? $pembayaran->payment->metadata : $pembayaran->payment_metadata;
                                                $metadata = is_string($payment_metadata) 
                                                    ? json_decode($payment_metadata, true) 
                                                    : $payment_metadata;
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
                                    </div>
                                    @if($pembayaran->payment_type !== 'deposit_usage' && $availablePayments->count() > 0)
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-warning dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-exchange"></i> Pindah Payment
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <h6 class="dropdown-header">Pindahkan ke:</h6>
                                                @foreach($availablePayments as $availPayment)
                                                    <a class="dropdown-item btn-move-payment" href="#" 
                                                       data-pembayaran-id="{{ $pembayaran->id }}"
                                                       data-new-payment-id="{{ $availPayment->id }}"
                                                       data-new-payment-name="{{ $availPayment->name }}">
                                                        {{ $availPayment->name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Informasi Pengirim</th>
                            <td>
                                @if($pembayaran->payment_info_metadata)
                                    @php
                                        $info = is_string($pembayaran->payment_info_metadata) 
                                            ? json_decode($pembayaran->payment_info_metadata, true) 
                                            : $pembayaran->payment_info_metadata;
                                    @endphp
                                    @if(!empty($info['from_bank']) || !empty($info['from_account_number']) || !empty($info['from_account_name']))
                                        <small class="text-muted">
                                        @if(!empty($info['from_bank']))
                                            Bank: {{ $info['from_bank'] }}<br>
                                        @endif
                                        @if(!empty($info['from_account_number']))
                                            No. Rekening: {{ $info['from_account_number'] }}<br>
                                        @endif
                                        @if(!empty($info['from_account_name']))
                                            Atas Nama: {{ $info['from_account_name'] }}
                                        @endif
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Jumlah Pembayaran</th>
                            <td>
                                @if($pembayaran->status == 'verified')
                                    <h4 class="mb-0 text-primary">
                                        <strong>Rp {{ number_format($pembayaran->payment_amount, 0, ',', '.') }}</strong>
                                    </h4>
                                @else
                                    <div class="d-flex align-items-start">
                                        <h4 class="mb-0 {{ $pembayaran->excess_amount > 0 ? 'text-danger' : 'text-primary' }}">
                                            <strong>Rp {{ number_format($pembayaran->payment_amount + ($pembayaran->excess_amount ?? 0), 0, ',', '.') }}</strong>
                                        </h4>
                                        @if($pembayaran->status == 'pending' && $totalPendingAmount > 0)
                                            <a href="#" 
                                               class="text-warning ml-2" 
                                               data-toggle="modal" 
                                               data-target="#editPaymentAmountModal"
                                               title="Edit Jumlah Pembayaran"
                                               style="font-size: 1.2rem; text-decoration: none;">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endif
                                    </div>
                                    @if(isset($pembayaran->excess_amount) && $pembayaran->excess_amount > 0)
                                        <div class="alert alert-warning mt-2 mb-0" role="alert">
                                            <i class="fa fa-exclamation-triangle"></i> 
                                            <strong>Kelebihan Pembayaran: Rp {{ number_format($pembayaran->excess_amount, 0, ',', '.') }}</strong>
                                            <br>
                                            <small>
                                                Pembayaran melebihi jumlah tagihan. Kelebihan pembayaran akan dijadikan deposit secara otomatis.
                                            </small>
                                            <hr class="my-2">
                                            <strong>Tagihan Iuran: Rp {{ number_format($pembayaran->payment_amount, 0, ',', '.') }}</strong>
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Bukti Transfer</th>
                            <td>
                                @if($pembayaran->file_path && $pembayaran->file_driver)
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
                                @if($pembayaran->notes)
                                    {!! nl2br(e($pembayaran->notes)) !!}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Dibuat Oleh</th>
                            <td>
                                {{ $pembayaran->created_by_user_name ?? '-' }}
                                <br><small class="text-muted">{{ $pembayaran->created_at->format('d F Y H:i') }}</small>
                            </td>
                        </tr>
                        @if($pembayaran->verified_at)
                        <tr>
                            <th>
                                @if($pembayaran->status == 'verified')
                                    Diverifikasi Oleh
                                @else
                                    Diproses Oleh
                                @endif
                            </th>
                            <td>
                                {{ $pembayaran->verified_by_user_name ?? '-' }}
                                <br><small class="text-muted">{{ $pembayaran->verified_at->format('d F Y H:i') }}</small>
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
                            @if($pembayaran->status == 'pending')
                                <!-- Approve Button -->
                                <button type="button" class="btn btn-success btn-block mb-2" id="btnSetujuiPembayaran">
                                    <i class="fa fa-check"></i> Setujui Pembayaran
                                </button>

                                <!-- Reject Button -->
                                <button type="button" class="btn btn-danger btn-block mb-3" data-toggle="modal" data-target="#rejectModal">
                                    <i class="fa fa-times"></i> Tolak Pembayaran
                                </button>

                                <hr>
                            @endif

                            @if($pembayaran->status == 'verified')
                                <div class="alert alert-success mb-3">
                                    <i class="fa fa-check-circle"></i> Pembayaran ini sudah diverifikasi
                                </div>
                            @elseif($pembayaran->status == 'rejected')
                                <div class="alert alert-danger mb-3">
                                    <i class="fa fa-times-circle"></i> Pembayaran ini sudah ditolak
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Tagihan Pending Table -->
                    @if($pembayaran->status == 'pending' && count($tagihanPendingList) > 0)
                    <div class="card mb-3">
                        <div class="app-card-header">
                            <span class="icon-chip icon-chip-warning"><i class="fa fa-list"></i></span>
                            <span class="app-card-title">Pilih Tagihan yang Akan Dibayar</span>
                        </div>
                        <div class="card-body p-2">
                            <form id="tagihanPendingForm">
                                <div style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-bordered mb-0" id="tagihanPendingTable">
                                        <thead>
                                            <tr>
                                                <th style="width:30px;">
                                                    <input type="checkbox" id="selectAllTagihan" {{ in_array($pembayaran->payment_type, ['current_billing', 'manual_billing']) ? 'checked' : '' }}>
                                                </th>
                                                <th>Nama Tagihan</th>
                                                <th>Sisa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tagihanPendingList as $tagihan)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="tagihan-checkbox"
                                                        name="tagihan_ids[]"
                                                        value="{{ $tagihan->id }}"
                                                        data-amount="{{ $tagihan->billing_amount - $tagihan->payment_amount }}"
                                                        {{ in_array($pembayaran->payment_type, ['current_billing', 'manual_billing']) ? 'checked' : '' }}>
                                                </td>
                                                <td>{{ $tagihan->billing_name }} ({{ $tagihan->period }})</td>
                                                <td>Rp {{ number_format($tagihan->billing_amount - $tagihan->payment_amount, 0, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2 text-right">
                                    <strong>Total: Rp <span id="tagihanTotal">0</span></strong>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    <!-- Info Panel -->
                    <div class="card">
                        <div class="app-card-header">
                            <span class="icon-chip icon-chip-info"><i class="fa fa-info-circle"></i></span>
                            <span class="app-card-title">Informasi</span>
                        </div>
                        <div class="card-body">
                            <small class="text-muted">
                                <i class="fa fa-lightbulb-o"></i> 
                                @if($pembayaran->status == 'pending')
                                    Anda dapat menyetujui atau menolak pembayaran ini. Setelah diproses, status tidak dapat diubah kembali.
                                @elseif($pembayaran->status == 'verified')
                                    Pembayaran yang sudah diverifikasi tidak dapat diproses ulang.
                                @else
                                    Pembayaran yang sudah ditolak tidak dapat diproses ulang.
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($pembayaran->status == 'verified' && $tagihans->count() > 0)
    <!-- Tagihan yang Dibayar Section -->
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-success"><i class="fa fa-check-circle"></i></span>
            <span class="app-card-title">Tagihan yang Sudah Dibayar</span>
        </div>
        <div class="card-body">
            @php $totalTagihan = 0; $totalBayar = 0; $totalSisa = 0; @endphp
            <!-- Mobile: stacked cards -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($tagihans as $index => $tagihan)
                @php
                    $sisa = $tagihan->billing_amount - $tagihan->payment_amount;
                    $totalTagihan += $tagihan->billing_amount;
                    $totalBayar += $tagihan->payment_amount;
                    $totalSisa += $sisa;
                @endphp
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <strong>{{ $tagihan->billing_name }}</strong>
                        @if($sisa > 0)
                            <span class="text-danger small">Rp {{ number_format($sisa, 0, ',', '.') }}</span>
                        @else
                            <span class="badge badge-success">Lunas</span>
                        @endif
                    </div>
                    <div class="mb-1">
                        @if($tagihan->billing_type == 'rutin_perbulan')
                            <span class="badge badge-info">Rutin Per Bulan</span>
                        @elseif($tagihan->billing_type == 'manual')
                            <span class="badge badge-warning">Manual</span>
                        @elseif($tagihan->billing_type == 'tunggakan_warga')
                            <span class="badge badge-danger">Tunggakan</span>
                        @endif
                        @if($tagihan->billing_generated_date)
                            <span class="text-muted small">{{ $tagihan->billing_generated_date->format('M Y') }}</span>
                        @endif
                    </div>
                    <div class="small">Total: Rp {{ number_format($tagihan->billing_amount, 0, ',', '.') }}</div>
                    <div class="small text-success">Dibayar: Rp {{ number_format($tagihan->payment_amount, 0, ',', '.') }}</div>
                </div>
                @endforeach
                <div class="list-group-item px-0 bg-light">
                    <div>Total Tagihan: Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                    <div class="text-success">Total Dibayar: Rp {{ number_format($totalBayar, 0, ',', '.') }}</div>
                    <div>
                        @if($totalSisa > 0)
                            <span class="text-danger">Total Sisa: Rp {{ number_format($totalSisa, 0, ',', '.') }}</span>
                        @else
                            <span class="badge badge-success">Semua Lunas</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="35%">Nama Tagihan</th>
                            <th width="15%" class="text-center">Periode</th>
                            <th width="15%" class="text-right">Total Tagihan</th>
                            <th width="15%" class="text-right">Sudah Dibayar</th>
                            <th width="15%" class="text-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tagihans as $index => $tagihan)
                        @php
                            $sisa = $tagihan->billing_amount - $tagihan->payment_amount;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $tagihan->billing_name }}</strong>
                                @if($tagihan->billing_type == 'rutin_perbulan')
                                    <br><small class="text-muted badge badge-info">Rutin Per Bulan</small>
                                @elseif($tagihan->billing_type == 'manual')
                                    <br><small class="text-muted badge badge-warning">Manual</small>
                                @elseif($tagihan->billing_type == 'tunggakan_warga')
                                    <br><small class="text-muted badge badge-danger">Tunggakan</small>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($tagihan->billing_generated_date)
                                    {{ $tagihan->billing_generated_date->format('M Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right">Rp {{ number_format($tagihan->billing_amount, 0, ',', '.') }}</td>
                            <td class="text-right">
                                <span class="text-success">
                                    <strong>Rp {{ number_format($tagihan->payment_amount, 0, ',', '.') }}</strong>
                                </span>
                            </td>
                            <td class="text-right">
                                @if($sisa > 0)
                                    <span class="text-danger">Rp {{ number_format($sisa, 0, ',', '.') }}</span>
                                @else
                                    <span class="badge badge-success">Lunas</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="3" class="text-right">Total:</th>
                            <th class="text-right">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</th>
                            <th class="text-right">
                                <span class="text-success">
                                    <strong>Rp {{ number_format($totalBayar, 0, ',', '.') }}</strong>
                                </span>
                            </th>
                            <th class="text-right">
                                @if($totalSisa > 0)
                                    <span class="text-danger">Rp {{ number_format($totalSisa, 0, ',', '.') }}</span>
                                @else
                                    <span class="badge badge-success">Semua Lunas</span>
                                @endif
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="alert alert-info mb-0 mt-3">
                <i class="fa fa-info-circle"></i> 
                <strong>Informasi:</strong> Tabel ini menampilkan tagihan yang telah dibayar menggunakan pembayaran ini.
            </div>
        </div>
    </div>
    @endif

    @if($pembayaran->logs()->count() > 0)
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
                    @foreach($pembayaran->logs()->orderBy('created_at', 'desc')->get() as $log)
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
                        @foreach($pembayaran->logs()->orderBy('created_at', 'desc')->get() as $log)
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
                <form action="{{ route('admin.warga.pembayaran.approve', ['userID' => $user->id, 'pembayaranID' => $pembayaran->id]) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="approveModalLabel">Konfirmasi Pembayaran</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> 
                            <strong>Informasi!</strong> Pembayaran yang sudah diverifikasi tidak dapat dibatalkan.
                        </div>
                        <p class="mb-0">Apakah Anda yakin ingin menyetujui pembayaran ini?</p>
                        <div class="mt-3 p-3 bg-light rounded">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td width="40%"><strong>Warga:</strong></td>
                                    <td>{{ $user->name }} ({{ $user->blok }})</td>
                                </tr>
                                <tr>
                                    <td><strong>Jumlah:</strong></td>
                                    <td class="text-success"><strong>Rp {{ number_format($pembayaran->payment_amount, 0, ',', '.') }}</strong></td>
                                </tr>
                                @if(isset($pembayaran->excess_amount) && $pembayaran->excess_amount > 0)
                                <tr>
                                    <td><strong>Kelebihan:</strong></td>
                                    <td class="text-danger">
                                        <strong>Rp {{ number_format($pembayaran->excess_amount, 0, ',', '.') }}</strong>
                                        <br><small class="text-muted">Kelebihan pembayaran akan dijadikan deposit.</small>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>Tanggal:</strong></td>
                                    <td>{{ $pembayaran->payment_date->format('d F Y') }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        @if($pembayaran->status == 'pending' && count($tagihanPendingList) > 0)
                        <div id="selectedTagihanInfo" class="mt-3">
                            <h6>Tagihan yang Dipilih:</h6>
                            <div id="selectedTagihanList" class="small text-muted"></div>
                            <hr>
                            <div id="paymentComparisonInfo"></div>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fa fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check"></i> Ya, Setujui Pembayaran
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
                <form action="{{ route('admin.warga.pembayaran.reject', ['userID' => $user->id, 'pembayaranID' => $pembayaran->id]) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="rejectModalLabel">Tolak Pembayaran</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i> 
                            <strong>Perhatian!</strong> Pembayaran yang ditolak tidak dapat diverifikasi kembali.
                        </div>
                        <div class="form-group">
                            <label for="reject_reason">Alasan Penolakan (Opsional)</label>
                            <textarea class="form-control" 
                                      id="reject_reason" 
                                      name="reject_reason" 
                                      rows="3"
                                      maxlength="1000"
                                      placeholder="Jelaskan alasan penolakan pembayaran ini..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-times"></i> Tolak Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Payment Amount Modal -->
    <div class="modal fade" id="editPaymentAmountModal" tabindex="-1" role="dialog" aria-labelledby="editPaymentAmountModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.warga.pembayaran.update-amount', ['userID' => $user->id, 'pembayaranID' => $pembayaran->id]) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title" id="editPaymentAmountModalLabel">Edit Jumlah Pembayaran</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> 
                            <strong>Informasi!</strong> Perubahan jumlah pembayaran hanya dapat dilakukan pada status pending.
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_amount"><strong>Jumlah Pembayaran Saat Ini:</strong></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="text" 
                                       class="form-control" 
                                       value="{{ number_format($pembayaran->payment_amount + ($pembayaran->excess_amount ?? 0), 0, ',', '.') }}" 
                                       readonly
                                       style="background-color: #e9ecef;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_payment_amount"><strong>Jumlah Pembayaran Baru:</strong></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="number" 
                                       class="form-control" 
                                       id="new_payment_amount"
                                       name="new_payment_amount" 
                                       min="1"
                                       required
                                       placeholder="Masukkan jumlah pembayaran baru">
                            </div>
                            <small class="form-text text-muted">
                                Total tagihan pending: Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="edit_notes"><strong>Catatan Perubahan: <span class="text-danger">*</span></strong></label>
                            <textarea class="form-control" 
                                      id="edit_notes" 
                                      name="notes" 
                                      rows="3"
                                      maxlength="1000"
                                      required
                                      placeholder="Jelaskan alasan perubahan jumlah pembayaran..."></textarea>
                            <small class="form-text text-muted">Wajib diisi. Maksimal 1000 karakter.</small>
                        </div>

                        <input type="hidden" name="total_pending_amount" value="{{ $totalPendingAmount }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fa fa-times"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($pembayaran->file_path && $pembayaran->file_driver)
    <!-- Proof Modal -->
    <div class="modal fade" id="proofModal" tabindex="-1" role="dialog" aria-labelledby="proofModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="proofModalLabel">Bukti Transfer - Pembayaran #{{ $pembayaran->id }}</h5>
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
                        <a href="{{ route('file.show', ['driver' => $pembayaran->file_driver]) }}?path={{ urlencode($pembayaran->file_path) }}" 
                           target="_blank" 
                           class="btn btn-primary">
                            <i class="fa fa-download"></i> Download PDF
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <a href="{{ route('file.show', ['driver' => $pembayaran->file_driver]) }}?path={{ urlencode($pembayaran->file_path) }}" 
                       target="_blank" 
                       class="btn btn-primary">
                        <i class="fa fa-external-link"></i> Buka di Tab Baru
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Warning Modal -->
    <div class="modal fade" id="warningModal" tabindex="-1" role="dialog" aria-labelledby="warningModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="warningModalLabel">
                        <i class="fa fa-exclamation-triangle"></i> Peringatan
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="warningMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Move Payment Confirmation Modal -->
    <div class="modal fade" id="movePaymentModal" tabindex="-1" role="dialog" aria-labelledby="movePaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title" id="movePaymentModalLabel">
                        <i class="fa fa-exchange"></i> Konfirmasi Pindah Payment
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin memindahkan metode pembayaran ini?</p>
                    <p><strong>Warga:</strong> {{ $pembayaran->user_name }}</p>
                    <p><strong>Pindah ke Payment:</strong> <span id="move-payment-name"></span></p>
                    <p class="text-muted small mb-0">Catatan: Pembayaran ini akan dicatat sebagai pembayaran untuk metode payment yang baru dipilih.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-warning" id="confirmMovePayment">
                        <i class="fa fa-exchange"></i> Pindahkan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let pembayaranIdToMove = null;
        let newPaymentId = null;

        $(document).ready(function() {
            // Handle move payment dropdown click
            $('.btn-move-payment').on('click', function(e) {
                e.preventDefault();
                
                pembayaranIdToMove = $(this).data('pembayaran-id');
                newPaymentId = $(this).data('new-payment-id');
                const newPaymentName = $(this).data('new-payment-name');
                
                $('#move-payment-name').text(newPaymentName);
                $('#movePaymentModal').modal('show');
            });

            // Handle confirm move payment
            $('#confirmMovePayment').on('click', function() {
                if (pembayaranIdToMove && newPaymentId) {
                    const url = '/admin/report/payment/' + pembayaranIdToMove + '/move';
                    
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            new_payment_id: newPaymentId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            $('#movePaymentModal').modal('hide');
                            console.error('Failed to move payment');
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        location.reload();
                    });
                }
            });
        });
    </script>

    @if($pembayaran->file_path && $pembayaran->file_driver)
    <script>
        $(document).ready(function() {
            $('#proofModal').on('show.bs.modal', function (e) {
                $('#loading-spinner').show();
                $('#proof-image').hide();
                $('#pdf-container').hide();
                
                var fileUrl = "{{ route('file.show', ['driver' => $pembayaran->file_driver]) }}?path={{ urlencode($pembayaran->file_path) }}";
                var fileExtension = "{{ strtolower(pathinfo($pembayaran->file_path, PATHINFO_EXTENSION)) }}";
                
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

    @if($pembayaran->status == 'pending' && count($tagihanPendingList) > 0)
    <script>
        const PAYMENT_AMOUNT = {{ $pembayaran->payment_amount }};

        function updateTagihanTotal() {
            let total = 0;
            document.querySelectorAll('.tagihan-checkbox:checked').forEach(function(cb) {
                total += parseInt(cb.getAttribute('data-amount'));
            });
            document.getElementById('tagihanTotal').innerText = total.toLocaleString('id-ID');
            return total;
        }

        function showWarningModal(message) {
            $('#warningMessage').html(message);
            $('#warningModal').modal('show');
        }

        function formatRupiah(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID');
        }

        function updateApprovalModalInfo() {
            let selectedTotal = 0;
            let selectedNames = [];
            
            $('.tagihan-checkbox:checked').each(function() {
                selectedTotal += parseInt($(this).attr('data-amount'));
                let row = $(this).closest('tr');
                let name = row.find('td:eq(1)').text();
                let amount = row.find('td:eq(2)').text();
                selectedNames.push(name + ' (' + amount + ')');
            });

            $('#selectedTagihanList').html(selectedNames.join('<br>'));
            
            // Show comparison info
            let comparisonHtml = '<table class="table table-sm table-borderless mb-0">';
            comparisonHtml += '<tr><td><strong>Jumlah Pembayaran:</strong></td><td class="text-right">' + formatRupiah(PAYMENT_AMOUNT) + '</td></tr>';
            comparisonHtml += '<tr><td><strong>Total Tagihan Dipilih:</strong></td><td class="text-right">' + formatRupiah(selectedTotal) + '</td></tr>';
            
            if (PAYMENT_AMOUNT < selectedTotal) {
                let difference = selectedTotal - PAYMENT_AMOUNT;
                comparisonHtml += '<tr class="table-warning"><td colspan="2"><small><i class="fa fa-exclamation-triangle"></i> <strong>Pembayaran kurang Rp ' + difference.toLocaleString('id-ID') + '</strong><br>Jumlah pembayaran tidak mencukupi untuk melunasi semua tagihan yang dipilih. Pembayaran akan dialokasikan secara proporsional.</small></td></tr>';
            } else if (PAYMENT_AMOUNT > selectedTotal) {
                let excess = PAYMENT_AMOUNT - selectedTotal;
                comparisonHtml += '<tr class="table-info"><td colspan="2"><small><i class="fa fa-info-circle"></i> Kelebihan pembayaran Rp ' + excess.toLocaleString('id-ID') + ' akan menjadi deposit.</small></td></tr>';
            } else {
                comparisonHtml += '<tr class="table-success"><td colspan="2"><small><i class="fa fa-check-circle"></i> Jumlah pembayaran pas dengan total tagihan.</small></td></tr>';
            }
            comparisonHtml += '</table>';
            
            $('#paymentComparisonInfo').html(comparisonHtml);
        }

        $(document).ready(function() {
            // Initialize total calculation
            updateTagihanTotal();
            updateApprovalModalInfo();
            
            // Update total when individual checkboxes change
            $('.tagihan-checkbox').on('change', function() {
                updateTagihanTotal();
                updateApprovalModalInfo();
                
                // Update select all checkbox state
                let totalCheckboxes = $('.tagihan-checkbox').length;
                let checkedCheckboxes = $('.tagihan-checkbox:checked').length;
                $('#selectAllTagihan').prop('checked', totalCheckboxes === checkedCheckboxes);
            });
            
            // Handle select all checkbox
            $('#selectAllTagihan').on('change', function() {
                let checked = $(this).is(':checked');
                $('.tagihan-checkbox').prop('checked', checked);
                updateTagihanTotal();
                updateApprovalModalInfo();
            });

            // Handle Setujui Pembayaran button click
            $('#btnSetujuiPembayaran').on('click', function() {
                // Get selected tagihan
                let selectedTagihan = [];
                let selectedTotal = 0;
                
                $('.tagihan-checkbox:checked').each(function() {
                    selectedTagihan.push($(this).val());
                    selectedTotal += parseInt($(this).attr('data-amount'));
                });

                // Validation 1: Check if at least one tagihan is selected
                if (selectedTagihan.length === 0) {
                    showWarningModal('<i class="fa fa-exclamation-triangle"></i> <strong>Silakan pilih minimal satu tagihan yang akan dibayar!</strong><br><br>Anda harus memilih minimal satu tagihan dari daftar di bawah sebelum menyetujui pembayaran.');
                    return false;
                }

                // Validation 2: Check if payment amount exceeds selected tagihan total
                if (PAYMENT_AMOUNT > selectedTotal) {
                    let excess = PAYMENT_AMOUNT - selectedTotal;
                    showWarningModal(
                        '<i class="fa fa-exclamation-triangle"></i> <strong>Jumlah pembayaran melebihi total tagihan yang dipilih!</strong><br><br>' +
                        '<table class="table table-sm table-bordered mt-2 mb-2">' +
                        '<tr><td><strong>Jumlah Pembayaran:</strong></td><td class="text-right">' + formatRupiah(PAYMENT_AMOUNT) + '</td></tr>' +
                        '<tr><td><strong>Total Tagihan Dipilih:</strong></td><td class="text-right">' + formatRupiah(selectedTotal) + '</td></tr>' +
                        '<tr class="table-warning"><td><strong>Kelebihan:</strong></td><td class="text-right">' + formatRupiah(excess) + '</td></tr>' +
                        '</table>' +
                        '<small class="text-muted">Silakan pilih lebih banyak tagihan atau edit jumlah pembayaran agar sesuai dengan tagihan yang dipilih.</small>'
                    );
                    return false;
                }

                // All validations passed, show approve modal
                updateApprovalModalInfo();
                $('#approveModal').modal('show');
            });

            // Handle approve form submission
            $('#approveModal form').on('submit', function(e) {
                // Get selected tagihan IDs
                let selectedTagihan = [];
                $('.tagihan-checkbox:checked').each(function() {
                    selectedTagihan.push($(this).val());
                });

                // Remove existing hidden inputs
                $(this).find('input[name="tagihan_ids[]"]').remove();

                // Add selected tagihan IDs as hidden inputs
                selectedTagihan.forEach(function(id) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'tagihan_ids[]',
                        value: id
                    }).appendTo('#approveModal form');
                });
            });
        });
    </script>
    @endif
@endsection
