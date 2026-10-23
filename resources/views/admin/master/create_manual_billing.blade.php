@extends('layouts.main')

@section('content')
    <h1 class="mt-4">Buat Tagihan Manual</h1>
    @include('admin.master._header_billing', ['billing_list' => '', 'billing_create' => '', 'billing_upload' => ''])
    
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif
    
    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif
    
    <div class="card mb-4">
        <div class="card-body" id="app">
            <form action="{{ url('admin/master/tagihan/manual/create') }}" method="POST">
                @csrf
            <div class="row">
                <div class="col-md-6">
                        <h5>Tambah Tagihan Manual</h5>
                        <div class="form-group">
                            <label for="billing_id">Pilih Iuran <span class="text-danger">*</span></label>
                            <select class="form-control" id="billing_id" name="billing_id" required>
                                <option value="">-- Pilih Iuran --</option>
                                @foreach($billingList as $billing)
                                    <option value="{{ $billing->id }}" 
                                        data-amount="{{ intval($billing->amount) }}"
                                        {{ old('billing_id') == $billing->id ? 'selected' : '' }}>
                                        {{ $billing->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('billing_id')
                                <div class="text-danger">
                                    {{ $errors->first('billing_id') }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="amount">Nominal <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="amount" name="amount" placeholder="Nominal tagihan" value="{{ old('amount') }}" step="1" min="0" required>
                            @error('amount')
                                <div class="text-danger">
                                    {{ $errors->first('amount') }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="generated_at_type">Dibuat Pada <span class="text-danger">*</span></label>
                            <select class="form-control" id="generated_at_type" name="generated_at_type" required>
                                <option value="">-- Pilih Waktu Pembuatan --</option>
                                <option value="now" {{ old('generated_at_type') == 'now' ? 'selected' : '' }}>Sekarang - {{ now()->format('d M Y') }}</option>
                                <option value="specific_date" {{ old('generated_at_type') == 'specific_date' ? 'selected' : '' }}>Tanggal Tertentu</option>
                            </select>
                            @error('generated_at_type')
                                <div class="text-danger">
                                    {{ $errors->first('generated_at_type') }}
                                </div>
                            @enderror
                            <small class="form-text text-muted">
                                <strong>Sekarang:</strong> Tagihan akan langsung dibuat saat ini<br>
                                <strong>Tanggal Tertentu:</strong> Tagihan akan dijadwalkan dan dibuat pada tanggal yang ditentukan
                            </small>
                        </div>
                        <div class="form-group" id="specific_date_section" style="display: none;">
                            <label for="generated_at_date">Pilih Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="generated_at_date" name="generated_at_date" value="{{ old('generated_at_date') }}">
                            @error('generated_at_date')
                                <div class="text-danger">
                                    {{ $errors->first('generated_at_date') }}
                                </div>
                            @enderror
                        </div>
                        <hr>
                        <div class="form-group d-flex flex-wrap" style="gap: .5rem;">
                            <input type="submit" class="btn btn-success" value="Simpan">
                            <a href="{{ url('admin/master/tagihan') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                </div>
                <div class="col-md-6">
                    <h5>Pilih Warga yang Ditagih</h5>
                    <p class="text-muted">Centang warga yang akan dikenakan tagihan ini.</p>
                    @error('user_ids')
                        <div class="alert alert-danger">
                            {{ $errors->first('user_ids') }}
                        </div>
                    @enderror
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
                    <div id="user_list_section" style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
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
                                        </td>
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

    @if($manualBillingSchedules->count() > 0)
    <div class="card mb-4">
        <div class="app-card-header">
            <span class="icon-chip icon-chip-warning"><i class="fa fa-clock-o"></i></span>
            <span class="app-card-title">Jadwal Tagihan Manual</span>
        </div>
        <div class="card-body">
            <p class="text-muted">Daftar tagihan manual yang telah dijadwalkan dan belum dibuat</p>

            @foreach($manualBillingSchedules as $schedule)
                <div style="display:none;" id="user-data-{{ $schedule->id }}" data-user-ids="{{ json_encode($schedule->user_ids) }}"></div>
                @if(!$schedule->is_generated)
                <form id="delete-form-schedule-{{ $schedule->id }}" action="{{ url('admin/master/tagihan/manual/schedule/' . $schedule->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                </form>
                @endif
            @endforeach

            <!-- Mobile: stacked cards -->
            <div class="d-md-none list-group list-group-flush mb-3">
                @foreach($manualBillingSchedules as $schedule)
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <strong>{{ $schedule->billing->name }}</strong>
                        @if($schedule->is_generated)
                            <span class="badge badge-success">Sudah Dibuat</span>
                        @else
                            <span class="badge badge-warning">Menunggu</span>
                        @endif
                    </div>
                    <div class="mb-1 font-weight-bold text-success">Rp {{ number_format($schedule->amount, 0, ',', '.') }}</div>
                    <div class="text-muted small mb-2">
                        Dijadwalkan {{ $schedule->scheduled_at->format('d M Y') }} &middot; oleh {{ $schedule->createdBy->name }}
                    </div>
                    <div class="d-flex flex-wrap align-items-center" style="gap: .4rem;">
                        <span class="badge badge-info">{{ count($schedule->user_ids) }} Warga</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="showUserList({{ $schedule->id }})">
                            <i class="fa fa-eye"></i> Lihat Warga
                        </button>
                        @if(!$schedule->is_generated)
                            <button type="button" class="btn btn-sm btn-danger btn-delete-schedule" data-form-id="delete-form-schedule-{{ $schedule->id }}" data-schedule-name="{{ $schedule->billing->name }}" data-schedule-date="{{ $schedule->scheduled_at->format('d M Y') }}">
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-secondary" disabled>
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Tablet/desktop: table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Nama Iuran</th>
                            <th>Nominal</th>
                            <th>Tanggal Dijadwalkan</th>
                            <th>Jumlah Warga</th>
                            <th>Dibuat Oleh</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($manualBillingSchedules as $schedule)
                            <tr>
                                <td>{{ $schedule->billing->name }}</td>
                                <td>Rp {{ number_format($schedule->amount, 0, ',', '.') }}</td>
                                <td>{{ $schedule->scheduled_at->format('d M Y') }}</td>
                                <td>
                                    <span class="badge badge-info">{{ count($schedule->user_ids) }} Warga</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="showUserList({{ $schedule->id }})">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </td>
                                <td>{{ $schedule->createdBy->name }}</td>
                                <td>
                                    @if($schedule->is_generated)
                                        <span class="badge badge-success">Sudah Dibuat</span>
                                    @else
                                        <span class="badge badge-warning">Menunggu</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$schedule->is_generated)
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-schedule" data-form-id="delete-form-schedule-{{ $schedule->id }}" data-schedule-name="{{ $schedule->billing->name }}" data-schedule-date="{{ $schedule->scheduled_at->format('d M Y') }}">
                                            <i class="fa fa-trash"></i> Hapus
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-secondary" disabled>
                                            <i class="fa fa-trash"></i> Hapus
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal for User List -->
    <div class="modal fade" id="userListModal" tabindex="-1" role="dialog" aria-labelledby="userListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userListModalLabel">Daftar Warga yang Ditagih</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="userListContent" style="max-height: 400px; overflow-y: auto;">
                        <p class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fa fa-exclamation-triangle"></i> Konfirmasi Hapus
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus jadwal tagihan ini?</p>
                    <p class="mb-0">
                        <strong>Tagihan:</strong> <span id="schedule-name"></span><br>
                        <strong>Tanggal:</strong> <span id="schedule-date"></span>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fa fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Update amount field when billing is selected
        $('#billing_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var amount = selectedOption.data('amount');
            if (amount) {
                $('#amount').val(amount);
            } else {
                $('#amount').val('');
            }
        });

        // Show/hide specific date section based on generated_at_type
        function toggleSpecificDateSection() {
            var generatedAtType = $('#generated_at_type').val();
            if (generatedAtType === 'specific_date') {
                $('#specific_date_section').show();
                $('#generated_at_date').prop('required', true);
            } else {
                $('#specific_date_section').hide();
                $('#generated_at_date').prop('required', false);
            }
        }

        // Check on page load (for old input values)
        toggleSpecificDateSection();

        // Check on generated_at_type change
        $('#generated_at_type').on('change', function() {
            toggleSpecificDateSection();
        });

        // Select all users checkbox
        $('#select_all_users').on('change', function() {
            $('.user_checkbox:visible').prop('checked', $(this).prop('checked'));
        });

        // Update select all checkbox if individual checkboxes change
        $('.user_checkbox').on('change', function() {
            var visibleCheckboxes = $('.user_checkbox:visible');
            // var allChecked = visibleCheckboxes.filter(':checked').length === visibleCheckboxes.length;
            // $('#select_all_users').prop('checked', allChecked);
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
            $('table tbody tr').show();
        });

        // Handle delete button clicks
        let formToSubmit = null;

        $('.btn-delete-schedule').on('click', function() {
            const formId = $(this).data('form-id');
            const scheduleName = $(this).data('schedule-name');
            const scheduleDate = $(this).data('schedule-date');
            
            formToSubmit = document.getElementById(formId);
            $('#schedule-name').text(scheduleName);
            $('#schedule-date').text(scheduleDate);
            
            $('#deleteModal').modal('show');
        });

        // Handle confirm delete button
        $('#confirmDelete').on('click', function() {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });
    });

    // Function to show user list in modal
    function showUserList(scheduleId) {
        var userIdsData = $('#user-data-' + scheduleId).data('user-ids');
        
        // Show modal
        $('#userListModal').modal('show');
        
        // Show loading
        $('#userListContent').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat data...</p>');
        
        // Fetch user details
        $.ajax({
            url: '{{ url("admin/master/tagihan/manual/schedule/users") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                user_ids: userIdsData
            },
            success: function(response) {
                var html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
                html += '<thead class="thead-light"><tr><th>No</th><th>Blok</th><th>Nama</th></tr></thead>';
                html += '<tbody>';
                
                if (response.users && response.users.length > 0) {
                    response.users.forEach(function(user, index) {
                        html += '<tr>';
                        html += '<td>' + (index + 1) + '</td>';
                        html += '<td>' + user.blok + '</td>';
                        html += '<td>' + user.name + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="3" class="text-center">Tidak ada data warga</td></tr>';
                }
                
                html += '</tbody></table></div>';
                $('#userListContent').html(html);
            },
            error: function() {
                $('#userListContent').html('<div class="alert alert-danger">Gagal memuat data warga</div>');
            }
        });
    }
</script>
@endsection
