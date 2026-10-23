@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Master Iuran</h1>
    @include('admin.master._header_billing', ['billing_list' => '', 'billing_create' => 'active', 'billing_upload' => ''])
    <div class="card mb-4">
        <div class="card-body" id="app">
            <form action="{{ url('admin/master/tagihan/create') }}" method="POST">
                @csrf
            <div class="row">
                <div class="col-md-6">
                        <h5>Tambah Iuran Warga</h5>
                        <div class="form-group">
                            <label for="nama">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama" name="nama" placeholder="Nama iuran" value="{{ old('nama') }}" required>
                            @error('nama')
                                <div class="text-danger">
                                    {{ $errors->first('nama') }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Deskripsi iuran (opsional)">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="text-danger">
                                    {{ $errors->first('description') }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="nominal">Nominal <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="nominal" name="nominal" placeholder="Nominal iuran" value="{{ old('nominal') }}" step="1" min="0" required>
                            @error('nominal')
                                <div class="text-danger">
                                    {{ $errors->first('nominal') }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="priority">Prioritas <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="priority" name="priority" placeholder="Prioritas iuran" value="{{ old('priority', 0) }}" step="1" min="0" required>
                            @error('priority')
                                <div class="text-danger">
                                    {{ $errors->first('priority') }}
                                </div>
                            @enderror
                            <small class="form-text text-muted">Urutan prioritas untuk pembayaran iuran (nilai lebih rendah = prioritas lebih tinggi)</small>
                        </div>
                        <div class="form-group">
                            <label for="billing_type">Tipe Tagihan <span class="text-danger">*</span></label>
                            <select class="form-control" id="billing_type" name="billing_type" required>
                                <option value="">-- Pilih Tipe Tagihan --</option>
                                <option value="rutin_perbulan" {{ old('billing_type') == 'rutin_perbulan' ? 'selected' : '' }}>Rutin Per Bulan</option>
                                <option value="manual" {{ old('billing_type') == 'manual' ? 'selected' : '' }}>Manual</option>
                            </select>
                            @error('billing_type')
                                <div class="text-danger">
                                    {{ $errors->first('billing_type') }}
                                </div>
                            @enderror
                            <small class="form-text text-muted">
                                <strong>Rutin Per Bulan:</strong> Iuran yang ditagihkan secara otomatis setiap bulan<br>
                                <strong>Manual:</strong> Iuran yang ditagihkan secara manual
                            </small>
                        </div>
                        <div class="form-check">
                            <label class="form-check-label">
                                <input type="checkbox" class="form-check-input" value="1" name="disabled" {{ old('disabled', '1') ? 'checked' : '' }}>
                                Aktif
                            </label>
                            <small class="form-text text-muted">Centang untuk mengaktifkan iuran ini</small>
                        </div>
                        <hr>
                        <div class="form-group d-flex flex-wrap" style="gap: .5rem;">
                            <input type="submit" class="btn btn-success" value="Simpan">
                            <a href="{{ url('admin/master/tagihan') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                </div>
                <div class="col-md-6" id="user_list_section" style="display: none;">
                    <h5>Pilih Warga yang Ditagih</h5>
                    <p class="text-muted">Centang warga yang akan dikenakan iuran ini. Warga yang dipilih akan otomatis ditagihkan setiap bulan.</p>
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" class="form-control" id="user_search" placeholder="Cari warga (nama atau blok)...">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="clear_search">
                                    <i class="fa fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                    <div style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                        <table class="table table-sm mb-0">
                            <thead style="position: sticky; top: 0; background-color: #fff; z-index: 1;">
                                <tr>
                                    <th style="padding: 10px;"><input type="checkbox" id="select_all_users" checked></th>
                                    <th style="padding: 10px;">Nama</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($userList as $user)
                                    <tr>
                                        <td style="padding: 10px;">
                                            <input type="checkbox" class="user_checkbox" name="user_ids[]" value="{{ $user->id }}" checked>
                                        </td >
                                        <td style="padding: 10px;"><strong>{{ $user->blok }}: </strong>{{ $user->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Show/hide user list section based on billing type
        function toggleUserListSection() {
            var billingType = $('#billing_type').val();
            if (billingType === 'rutin_perbulan') {
                $('#user_list_section').show();
            } else {
                $('#user_list_section').hide();
            }
        }

        // Check on page load (for old input values)
        toggleUserListSection();

        // Check on billing type change
        $('#billing_type').on('change', function() {
            toggleUserListSection();
        });

        // Select all users checkbox
        $('#select_all_users').on('change', function() {
            $('.user_checkbox:visible').prop('checked', $(this).prop('checked'));
        });

        // Update select all checkbox if individual checkboxes change
        $('.user_checkbox').on('change', function() {
            var visibleCheckboxes = $('.user_checkbox:visible');
            var allChecked = visibleCheckboxes.filter(':checked').length === visibleCheckboxes.length;
            $('#select_all_users').prop('checked', allChecked);
        });

        // Filter users by search input
        $('#user_search').on('keyup', function() {
            var searchValue = $(this).val().toLowerCase();
            $('#user_list_section tbody tr').each(function() {
                var userName = $(this).find('td:last').text().toLowerCase();
                if (userName.indexOf(searchValue) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Clear search filter
        $('#clear_search').on('click', function() {
            $('#user_search').val('');
            $('#user_list_section tbody tr').show();
        });
    });
</script>
@endsection
