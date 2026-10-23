@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Organization</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/organization') }}">Organization</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Organisasi</li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5>Edit Data Organisasi</h5>
                    <form action="{{ url('/admin/organization') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">Nama Organisasi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" placeholder="Masukkan nama organisasi"
                                   value="{{ old('name', $organization->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="4"
                                      placeholder="Masukkan deskripsi organisasi">{{ old('description', $organization->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        @role('superadmin')
                        <hr>
                        <h5>Konfigurasi WhatsApp</h5>

                        <div class="form-group">
                            <label for="whatsapp_admin_group_id">Admin Group ID</label>
                            <input type="text" class="form-control @error('whatsapp_admin_group_id') is-invalid @enderror"
                                   id="whatsapp_admin_group_id" name="whatsapp_admin_group_id"
                                   placeholder="Masukkan WhatsApp admin group ID"
                                   value="{{ old('whatsapp_admin_group_id', $organization->whatsapp_admin_group_id) }}">
                            @error('whatsapp_admin_group_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="whatsapp_member_group_id">Member Group ID</label>
                            <input type="text" class="form-control @error('whatsapp_member_group_id') is-invalid @enderror"
                                   id="whatsapp_member_group_id" name="whatsapp_member_group_id"
                                   placeholder="Masukkan WhatsApp member group ID"
                                   value="{{ old('whatsapp_member_group_id', $organization->whatsapp_member_group_id) }}">
                            @error('whatsapp_member_group_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="whatsapp_notif_startup_number">Notif Startup Number</label>
                            <input type="text" class="form-control @error('whatsapp_notif_startup_number') is-invalid @enderror"
                                   id="whatsapp_notif_startup_number" name="whatsapp_notif_startup_number"
                                   placeholder="Masukkan nomor notif startup"
                                   value="{{ old('whatsapp_notif_startup_number', $organization->whatsapp_notif_startup_number) }}">
                            @error('whatsapp_notif_startup_number')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group form-check">
                            <input type="hidden" name="whatsapp_enable_registration" value="0">
                            <input type="checkbox" class="form-check-input" id="whatsapp_enable_registration"
                                   name="whatsapp_enable_registration" value="1"
                                   {{ old('whatsapp_enable_registration', $organization->whatsapp_enable_registration) ? 'checked' : '' }}>
                            <label class="form-check-label" for="whatsapp_enable_registration">Aktifkan Registrasi via WhatsApp</label>
                        </div>
                        @endrole

                        <hr>
                        <div class="form-group d-flex flex-wrap" style="gap: .5rem;">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="{{ url('/admin/organization') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
