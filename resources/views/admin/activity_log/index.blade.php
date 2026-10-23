@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Activity Log</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
        <li class="breadcrumb-item active">Activity Log</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-body">
            @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            @endif
            
            <div class="alert alert-info mb-3">
                <i class="fa fa-info-circle"></i> 
                <strong>Informasi Activity Log:</strong> 
                Halaman ini menampilkan riwayat aktivitas yang dilakukan oleh pengguna dalam sistem. 
                Log mencatat siapa yang melakukan aktivitas, deskripsi aktivitas, dan link terkait jika ada.
            </div>

            @if($logs->isEmpty())
            <div class="text-center text-muted py-4">Tidak ada data activity log</div>
            @else
            <!-- Mobile: stacked cards (stays informative without horizontal scroll) -->
            <div class="d-md-none list-group list-group-flush mb-3" style="font-size: 0.85rem;">
                @foreach($logs as $index => $log)
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <strong>{{ $log->username }}</strong>
                        <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                    <p class="mb-2">{{ $log->description }}</p>
                    @if($log->url_link)
                    <a href="{{ $log->url_link }}" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.2rem 0.5rem;">
                        <i class="fa fa-link"></i> View
                    </a>
                    @endif
                </div>
                @endforeach
            </div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block" style="font-size: 0.9rem;">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 15%">Tanggal</th>
                            <th style="width: 15%">Username</th>
                            <th style="width: 50%">Description</th>
                            <th style="width: 15%">Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $index => $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->username }}</td>
                            <td>{{ $log->description }}</td>
                            <td>
                                @if($log->url_link)
                                <a href="{{ $log->url_link }}" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.2rem 0.5rem;">
                                    <i class="fa fa-link"></i> View
                                </a>
                                @else
                                <span class="text-muted">-</span>
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
                        Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} results
                    </div>
                    <div>
                        @if ($logs->onFirstPage())
                            <span class="btn btn-sm btn-light disabled mr-2">« Previous</span>
                        @else
                            <a href="{{ $logs->previousPageUrl() }}" class="btn btn-sm btn-outline-primary mr-2">« Previous</a>
                        @endif

                        @if ($logs->hasMorePages())
                            <a href="{{ $logs->nextPageUrl() }}" class="btn btn-sm btn-outline-primary">Next »</a>
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
