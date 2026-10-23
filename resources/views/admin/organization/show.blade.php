@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Organization</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
        <li class="breadcrumb-item active">Organization</li>
    </ol>

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="app-hero d-flex align-items-center justify-content-between flex-wrap">
        <div class="d-flex align-items-center" style="gap: .85rem;">
            <div class="app-hero-avatar">{{ strtoupper(substr($organization->name, 0, 1)) }}</div>
            <div>
                <div class="app-hero-greeting">Organisasi</div>
                <div class="app-hero-name">{{ $organization->name }}</div>
            </div>
        </div>
        <a href="{{ url('/admin/organization/edit') }}" class="btn btn-light btn-sm font-weight-bold">
            <i class="fa fa-edit"></i> Edit
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="app-card-header">
                    <span class="icon-chip icon-chip-primary"><i class="fa fa-building"></i></span>
                    <span class="app-card-title">Informasi Organisasi</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-3 mb-sm-4">
                            <div class="text-muted small mb-1"><i class="fa fa-tag text-muted mr-1"></i> Nama Organisasi</div>
                            <div class="font-weight-bold">{{ $organization->name }}</div>
                        </div>
                        <div class="col-sm-6 mb-3 mb-sm-4">
                            <div class="text-muted small mb-1"><i class="fa fa-hashtag text-muted mr-1"></i> Organization ID</div>
                            <div class="font-weight-bold">#{{ $organization->id }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small mb-1"><i class="fa fa-align-left text-muted mr-1"></i> Deskripsi</div>
                            <div>{{ $organization->description ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @role('superadmin')
            <div class="card border-0 shadow-sm mb-4">
                <div class="app-card-header">
                    <span class="icon-chip icon-chip-success"><i class="fa fa-whatsapp"></i></span>
                    <span class="app-card-title">Konfigurasi WhatsApp</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-3 mb-sm-4">
                            <div class="text-muted small mb-1"><i class="fa fa-users text-muted mr-1"></i> Admin Group ID</div>
                            <div class="font-weight-bold">{{ $organization->getWhatsappAdminGroupId() ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6 mb-3 mb-sm-4">
                            <div class="text-muted small mb-1"><i class="fa fa-users text-muted mr-1"></i> Member Group ID</div>
                            <div class="font-weight-bold">{{ $organization->getWhatsappMemberGroupId() ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6 mb-3 mb-sm-4">
                            <div class="text-muted small mb-1"><i class="fa fa-phone text-muted mr-1"></i> Notif Startup Number</div>
                            <div class="font-weight-bold">{{ $organization->getWhatsappNotifStartupNumber() ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6 mb-3 mb-sm-4">
                            <div class="text-muted small mb-1"><i class="fa fa-toggle-on text-muted mr-1"></i> Registrasi via WhatsApp</div>
                            <div class="font-weight-bold">{{ $organization->getWhatsappEnableRegistration() ? 'Aktif' : 'Nonaktif' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endrole
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="app-card-header">
                    <span class="icon-chip icon-chip-info"><i class="fa fa-clock-o"></i></span>
                    <span class="app-card-title">Riwayat</span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Dibuat Pada</div>
                        <div class="font-weight-bold">{{ $organization->created_at->format('d M Y, H:i') }}</div>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Terakhir Diupdate</div>
                        <div class="font-weight-bold">{{ $organization->updated_at->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
