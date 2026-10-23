@extends('layouts.main')

@section('content')
    <h1 class="mt-4">{{ $billing->name }}</h1>
    @include('admin.master._header_billing', ['billing_list' => 'active', 'billing_upload' => ''])
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-primary"><i class="fa fa-money"></i></span>
            <span class="app-card-title">Detail Iuran</span>
            <a href="{{ url('admin/master/tagihan/edit/' . $billing->id) }}" class="btn btn-sm btn-warning">
                <i class="fa fa-edit"></i> Edit
            </a>
            <a href="{{ url('admin/master/tagihan') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <!-- Mobile: stacked label/value rows -->
                    <div class="d-md-none">
                        <div class="mb-3">
                            <div class="text-muted small">Nama</div>
                            <div class="font-weight-bold">{{ $billing->name }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Deskripsi</div>
                            <div>{{ $billing->description ?: '-' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Nominal</div>
                            <div><span class="badge badge-success">Rp {{ number_format($billing->amount, 0, ',', '.') }}</span></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Prioritas</div>
                            <div><span class="badge badge-info">{{ $billing->priority }}</span></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Tipe Tagihan</div>
                            <div>
                                @if($billing->billing_type == 'rutin_perbulan')
                                    <span class="badge badge-info">Rutin Per Bulan</span>
                                @else
                                    <span class="badge badge-secondary">Manual</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Status</div>
                            <div>
                                @if(!$billing->disabled)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Tidak Aktif</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Dibuat pada</div>
                            <div>{{ $billing->created_at->format('d M Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Terakhir diupdate</div>
                            <div>{{ $billing->updated_at->format('d M Y H:i') }}</div>
                        </div>
                        @if($billing->billing_type == 'rutin_perbulan' && $billing->users && $billing->users->count() > 0)
                        <div class="mb-3">
                            <div class="text-muted small">Warga yang Ditagih</div>
                            <div><span class="badge badge-primary">{{ $billing->users->count() }} Warga</span></div>
                        </div>
                        @endif
                    </div>

                    <!-- Tablet/desktop: bordered table -->
                    <table class="table table-bordered d-none d-md-table">
                        <tr>
                            <th width="30%">Nama</th>
                            <td>{{ $billing->name }}</td>
                        </tr>
                        <tr>
                            <th>Deskripsi</th>
                            <td>{{ $billing->description ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th>Nominal</th>
                            <td><span class="badge badge-success">Rp {{ number_format($billing->amount, 0, ',', '.') }}</span></td>
                        </tr>
                        <tr>
                            <th>Prioritas</th>
                            <td><span class="badge badge-info">{{ $billing->priority }}</span></td>
                        </tr>
                        <tr>
                            <th>Tipe Tagihan</th>
                            <td>
                                @if($billing->billing_type == 'rutin_perbulan')
                                    <span class="badge badge-info">Rutin Per Bulan</span>
                                @else
                                    <span class="badge badge-secondary">Manual</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if(!$billing->disabled)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Tidak Aktif</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Dibuat pada</th>
                            <td>{{ $billing->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Terakhir diupdate</th>
                            <td>{{ $billing->updated_at->format('d M Y H:i') }}</td>
                        </tr>
                        @if($billing->billing_type == 'rutin_perbulan' && $billing->users && $billing->users->count() > 0)
                        <tr>
                            <th>Warga yang Ditagih</th>
                            <td><span class="badge badge-primary">{{ $billing->users->count() }} Warga</span></td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($billing->billing_type == 'rutin_perbulan' && $billing->users && $billing->users->count() > 0)
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-info"><i class="fa fa-users"></i></span>
            <span class="app-card-title">Daftar Warga yang Ditagih</span>
        </div>
        <div class="card-body">
            <div class="form-group">
                <input type="text" class="form-control" id="user_search" placeholder="Cari warga (nama atau blok)...">
            </div>

            <!-- Mobile: stacked cards -->
            <div class="d-md-none border rounded" style="max-height: 400px; overflow-y: auto;">
                <div class="list-group list-group-flush" id="warga-mobile-list">
                    @foreach($billing->users->sortBy('blok') as $index => $user)
                    <div class="list-group-item user-row">
                        <strong>{{ $user->blok }}</strong> &middot; {{ $user->name }}
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Tablet/desktop: table -->
            <div class="d-none d-md-block" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                <table class="table table-sm table-striped mb-0">
                    <thead style="position: sticky; top: 0; background-color: #fff; z-index: 1;">
                        <tr>
                            <th style="padding: 10px; width: 50px;">No</th>
                            <th style="padding: 10px; width: 80px;">Blok</th>
                            <th style="padding: 10px;">Nama</th>
                        </tr>
                    </thead>
                    <tbody id="warga-desktop-tbody">
                        @foreach($billing->users->sortBy('blok') as $index => $user)
                        <tr class="user-row">
                            <td style="padding: 10px;">{{ $index + 1 }}</td>
                            <td style="padding: 10px;"><strong>{{ $user->blok }}</strong></td>
                            <td style="padding: 10px;">{{ $user->name }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($billing->logs && $billing->logs->count() > 0)
    <div class="card">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-info"><i class="fa fa-history"></i></span>
            <span class="app-card-title">Riwayat Log (10 Terakhir)</span>
        </div>
        <div class="card-body">
            <!-- Mobile: stacked cards -->
            <div class="d-md-none border rounded">
                <div class="list-group list-group-flush">
                    @foreach($billing->logs->sortByDesc('id')->take(10) as $log)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong class="small">{{ $log->username }}</strong>
                            <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <div class="small" style="white-space: pre-line;">{{ $log->description }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Admin</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($billing->logs->sortByDesc('id')->take(10) as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $log->username }}</td>
                            <td style="white-space: pre-line;">{{ $log->description }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Filter users by search input (mobile + desktop rows kept in sync)
        $('#user_search').on('keyup', function() {
            var searchValue = $(this).val().toLowerCase();
            $('.user-row').each(function() {
                var rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.indexOf(searchValue) > -1);
            });
        });
    });
</script>
@endsection
