@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Riwayat Pembayaran {{ $user->name }} ({{ $user->blok }})</h1>
    @include('admin.master._header_warga_detail', ['active' => 'pembayaran', 'userId' => $user->id])

    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-money"></i></span>
            <span class="app-card-title">Daftar Pembayaran</span>
            <a href="{{ route('admin.warga.pembayaran.create', ['userID' => $user->id]) }}" class="btn btn-sm btn-primary">
                <i class="fa fa-plus"></i> Catat Pembayaran
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

            @if($pembayarans->isEmpty())
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Belum ada riwayat pembayaran untuk warga ini.
                </div>
            @else
                <!-- Mobile: stacked cards -->
                <div class="d-md-none list-group list-group-flush mb-3">
                    @foreach($pembayarans as $index => $pembayaran)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong>{{ $pembayaran->payment_name }}</strong>
                            @if($pembayaran->status == 'verified')
                                <span class="badge badge-success">Terverifikasi</span>
                            @elseif($pembayaran->status == 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @elseif($pembayaran->status == 'rejected')
                                <span class="badge badge-danger">Ditolak</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($pembayaran->status) }}</span>
                            @endif
                        </div>
                        <div class="mb-1">
                            @if($pembayaran->payment_type == 'current_billing')
                                <span class="badge badge-info">Iuran Rutin bulanan</span>
                                <span class="text-muted small">{{ $pembayaran->formatted_period }}</span>
                            @elseif($pembayaran->payment_type == 'previous_billing')
                                <span class="badge badge-warning">Tunggakan</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($pembayaran->formatted_payment_type) }}</span>
                            @endif
                        </div>
                        <div class="text-muted small mb-2">{{ $pembayaran->payment_date->format('d M Y') }}</div>
                        <div class="mb-2">{!! $pembayaran->getLabelPaymentAmount() !!}</div>
                        <div class="text-muted small mb-2">
                            Dibuat oleh {{ $pembayaran->created_by_user_name ?? '-' }} &middot; {{ $pembayaran->created_at->format('d M Y H:i') }}
                        </div>
                        <div class="d-flex flex-wrap" style="gap: .4rem;">
                            <a href="{{ url('/admin/master/warga/' . $user->id . '/pembayaran/' . $pembayaran->id) }}" class="btn btn-info btn-sm">
                                <i class="fa fa-eye"></i> Lihat
                            </a>
                            @if($pembayaran->status == 'pending')
                                <form action="{{ route('admin.warga.pembayaran.destroy', ['userID' => $user->id, 'pembayaranID' => $pembayaran->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pembayaran ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fa fa-trash"></i> Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
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
                                <th width="18%">Tipe Pembayaran</th>
                                <th width="20%">Metode Pembayaran</th>
                                <th width="12%">Jumlah</th>
                                <th width="12%">Status</th>
                                <th width="12%">Dibuat Oleh</th>
                                <th width="9%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pembayarans as $index => $pembayaran)
                                <tr>
                                    <td>{{ $pembayarans->firstItem() + $index }}</td>
                                    <td>{{ $pembayaran->payment_date->format('d M Y') }}</td>
                                    <td>
                                        @if($pembayaran->payment_type == 'current_billing')
                                            <span class="badge badge-info">Iuran Rutin bulanan</span>
                                            <br><small class="text-muted">{{ $pembayaran->formatted_period }}</small>
                                        @elseif($pembayaran->payment_type == 'previous_billing')
                                            <span class="badge badge-warning">Tunggakan</span>
                                        @else
                                            <span class="badge badge-secondary">{{ ucfirst($pembayaran->formatted_payment_type) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $pembayaran->payment_name }}</strong>
                                        @if($pembayaran->payment_info_metadata)
                                            @php
                                                $info = is_string($pembayaran->payment_info_metadata)
                                                    ? json_decode($pembayaran->payment_info_metadata, true)
                                                    : $pembayaran->payment_info_metadata;
                                            @endphp
                                            @if(!empty($info['from_bank']))
                                                <br><small class="text-muted">
                                                    {{ $info['from_bank'] }}
                                                    @if(!empty($info['from_account_number']))
                                                        - {{ $info['from_account_number'] }}
                                                    @endif
                                                </small>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        {!! $pembayaran->getLabelPaymentAmount() !!}
                                    </td>
                                    <td class="text-center">
                                        @if($pembayaran->status == 'verified')
                                            <span class="badge badge-success">Terverifikasi</span>
                                            @if($pembayaran->verified_at)
                                                <br><small class="text-muted">{{ $pembayaran->verified_at->format('d M Y H:i') }}</small>
                                            @endif
                                        @elseif($pembayaran->status == 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif($pembayaran->status == 'rejected')
                                            <span class="badge badge-danger">Ditolak</span>
                                        @else
                                            <span class="badge badge-secondary">{{ ucfirst($pembayaran->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $pembayaran->created_by_user_name ?? '-' }}</small>
                                        <br><small class="text-muted">{{ $pembayaran->created_at->format('d M Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ url('/admin/master/warga/' . $user->id . '/pembayaran/' . $pembayaran->id) }}" class="btn btn-info btn-sm" title="Lihat Detail">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @if($pembayaran->status == 'pending')
                                            <form action="{{ route('admin.warga.pembayaran.destroy', ['userID' => $user->id, 'pembayaranID' => $pembayaran->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pembayaran ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="text-muted small mb-2 mb-sm-0">
                            Menampilkan {{ $pembayarans->firstItem() }} - {{ $pembayarans->lastItem() }} dari {{ $pembayarans->total() }} data
                        </div>
                        <div>
                            @if ($pembayarans->onFirstPage())
                                <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                            @else
                                <a href="{{ $pembayarans->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                            @endif

                            @if ($pembayarans->hasMorePages())
                                <a href="{{ $pembayarans->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
                            @else
                                <span class="btn btn-sm btn-light disabled">Next »</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>


@endsection
