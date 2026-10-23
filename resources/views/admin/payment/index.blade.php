@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Pembayaran Iuran</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Daftar Pembayaran</li>
        </ol>
    </nav>
    
    <div class="alert alert-info mb-3">
        <i class="fa fa-info-circle"></i> 
        <strong>Informasi Pembayaran:</strong> 
        Kelola dan verifikasi pembayaran iuran dari warga. Gunakan filter status dan pencarian untuk menemukan pembayaran tertentu.
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <form method="GET" action="{{ url('/admin/payment') }}">
                <div class="d-flex flex-wrap align-items-center" style="gap: .5rem;">
                    <select name="status" id="status" class="form-control" style="width: 160px;" onchange="this.form.submit()">
                        <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="verified" {{ $status == 'verified' ? 'selected' : '' }}>Terverifikasi</option>
                        <option value="rejected" {{ $status == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        <option value="all" {{ $status == 'all' ? 'selected' : '' }}>Semua</option>
                    </select>
                    <select name="payment_type" id="payment_type" class="form-control" style="width: 180px;" onchange="this.form.submit()">
                        <option value="all" {{ $paymentType == 'all' ? 'selected' : '' }}>Semua Tipe</option>
                        <option value="current_billing" {{ $paymentType == 'current_billing' ? 'selected' : '' }}>Iuran Bulanan</option>
                        <option value="previous_billing" {{ $paymentType == 'previous_billing' ? 'selected' : '' }}>Tunggakan</option>
                        <option value="user_deposit" {{ $paymentType == 'user_deposit' ? 'selected' : '' }}>Deposit</option>
                        <option value="manual_billing" {{ $paymentType == 'manual_billing' ? 'selected' : '' }}>Iuran Khusus</option>
                    </select>
                    <input type="text" name="q" class="form-control" style="flex: 1 1 180px; width: auto;" placeholder="Cari nama warga atau blok" value="{{ request('q') }}">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    @if(request('q'))
                        <a href="{{ url('/admin/payment?status=' . $status . '&payment_type=' . $paymentType) }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="card-body">
            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif

            @if($pembayarans->count() > 0)
            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($pembayarans as $index => $pembayaran)
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <strong>{{ $pembayaran->user_name }}</strong>
                            <div class="text-muted small">{{ $pembayaran->user_blok }}</div>
                        </div>
                        @if($pembayaran->status == 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @elseif($pembayaran->status == 'verified')
                            <span class="badge badge-success">Terverifikasi</span>
                        @elseif($pembayaran->status == 'rejected')
                            <span class="badge badge-danger">Ditolak</span>
                        @endif
                    </div>
                    <div class="mb-1">
                        @if($pembayaran->payment_type == 'current_billing')
                            <span class="badge badge-primary">{{ $pembayaran->formatted_payment_type }}</span>
                        @elseif($pembayaran->payment_type == 'previous_billing')
                            <span class="badge badge-warning">{{ $pembayaran->formatted_payment_type }}</span>
                        @elseif($pembayaran->payment_type == 'user_deposit')
                            <span class="badge badge-info">{{ $pembayaran->formatted_payment_type }}</span>
                        @else
                            <span class="badge badge-secondary">{{ $pembayaran->formatted_payment_type }}</span>
                        @endif
                    </div>
                    <div class="mb-1">{!! $pembayaran->getLabelPaymentAmount() !!}</div>
                    <div class="text-muted small mb-2">
                        {{ $pembayaran->created_at->format('d/m/Y H:i') }} &middot; {{ $pembayaran->created_at->diffForHumans() }}
                    </div>
                    <a href="{{ $pembayaran->payment_type == 'user_deposit' ? route('admin.warga.deposit.show', ['userID' => $pembayaran->user_id, 'depositID' => $pembayaran->id]) : route('admin.warga.pembayaran.show', ['userID' => $pembayaran->user_id, 'pembayaranID' => $pembayaran->id]) }}"
                       class="btn btn-sm btn-info">
                        <i class="fa fa-eye"></i> Lihat
                    </a>
                </div>
                @endforeach
            </div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="28%">Warga</th>
                            <th width="17%">Tipe Pembayaran</th>
                            <th width="15%">Jumlah</th>
                            <th width="15%">Waktu</th>
                            <th width="12%">Status</th>
                            <th width="8%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pembayarans as $index => $pembayaran)
                        <tr>
                            <td>{{ $pembayarans->firstItem() + $index }}</td>
                            <td>
                                <strong>{{ $pembayaran->user_name }}</strong>
                                <br>
                                <small class="text-muted">{{ $pembayaran->user_blok }}</small>
                            </td>
                            <td>
                                @if($pembayaran->payment_type == 'current_billing')
                                    <span class="badge badge-primary">{{ $pembayaran->formatted_payment_type }}</span>
                                @elseif($pembayaran->payment_type == 'previous_billing')
                                    <span class="badge badge-warning">{{ $pembayaran->formatted_payment_type }}</span>
                                @elseif($pembayaran->payment_type == 'user_deposit')
                                    <span class="badge badge-info">{{ $pembayaran->formatted_payment_type }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ $pembayaran->formatted_payment_type }}</span>
                                @endif
                            </td>
                            <td>
                                {!! $pembayaran->getLabelPaymentAmount() !!}
                            </td>
                            <td>
                                <small>{{ $pembayaran->created_at->format('d/m/Y H:i') }}</small>
                                <br>
                                <small class="text-muted">{{ $pembayaran->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                @if($pembayaran->status == 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @elseif($pembayaran->status == 'verified')
                                    <span class="badge badge-success">Terverifikasi</span>
                                @elseif($pembayaran->status == 'rejected')
                                    <span class="badge badge-danger">Ditolak</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ $pembayaran->payment_type == 'user_deposit' ? route('admin.warga.deposit.show', ['userID' => $pembayaran->user_id, 'depositID' => $pembayaran->id]) : route('admin.warga.pembayaran.show', ['userID' => $pembayaran->user_id, 'pembayaranID' => $pembayaran->id]) }}"
                                   class="btn btn-sm btn-info"
                                   title="Lihat Detail">
                                    <i class="fa fa-eye"></i> Lihat
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="text-muted small mb-2 mb-sm-0">
                        Showing {{ $pembayarans->firstItem() }} to {{ $pembayarans->lastItem() }} of {{ $pembayarans->total() }} results
                    </div>
                    <div>
                        @if ($pembayarans->onFirstPage())
                            <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                        @else
                            <a href="{{ $pembayarans->appends(['status' => $status, 'payment_type' => $paymentType, 'q' => request('q')])->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                        @endif

                        @if ($pembayarans->hasMorePages())
                            <a href="{{ $pembayarans->appends(['status' => $status, 'payment_type' => $paymentType, 'q' => request('q')])->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
                        @else
                            <span class="btn btn-sm btn-light disabled">Next »</span>
                        @endif
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                Tidak ada data pembayaran untuk status {{ $status }}.
            </div>
            @endif
        </div>
    </div>
@endsection
