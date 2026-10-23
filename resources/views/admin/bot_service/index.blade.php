@extends('layouts.main')

@php
    $statusMeta = [
        'online' => ['label' => 'Online'],
        'waiting_for_login' => ['label' => 'Menunggu Login (Scan QR)'],
        'error' => ['label' => 'Error'],
        'offline' => ['label' => 'Offline'],
        'unknown' => ['label' => 'Belum Ada Data'],
    ];
    $meta = $statusMeta[$status] ?? $statusMeta['unknown'];
    $lastLogId = collect($logs)->max('id') ?? 0;
@endphp

@section('content')
    <h1 class="mt-4">Bot Service</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
        <li class="breadcrumb-item active">Bot Service</li>
    </ol>

    <div class="app-hero d-flex align-items-center justify-content-between flex-wrap">
        <div class="d-flex align-items-center" style="gap: .85rem;">
            <div class="app-hero-avatar"><i class="fa fa-whatsapp"></i></div>
            <div>
                <div class="app-hero-greeting">Status Bot</div>
                <div class="app-hero-name" id="bot-status-badge">{{ $meta['label'] }}</div>
            </div>
        </div>
        <div class="d-flex flex-wrap" style="gap: .5rem;">
            <button type="button" class="btn btn-light btn-sm font-weight-bold" id="btn-restart"
                data-toggle="tooltip" title="Session WhatsApp tetap tersimpan, tidak perlu scan ulang">
                <i class="fa fa-refresh"></i> Restart Bot
            </button>
            <button type="button" class="btn btn-light btn-sm font-weight-bold text-danger" id="btn-delete-session"
                data-toggle="tooltip" title="Bot logout dan butuh scan QR ulang">
                <i class="fa fa-trash"></i> Delete Session
            </button>
            <button type="button" class="btn btn-light btn-sm font-weight-bold text-primary" id="btn-list-group"
                data-toggle="tooltip" title="Minta bot mengambil daftar grup WhatsApp-nya">
                <i class="fa fa-list"></i> List Groups
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="app-summary-card">
                <span class="icon-chip icon-chip-lg icon-chip-primary"><i class="fa fa-hashtag"></i></span>
                <div>
                    <div class="app-summary-label">Session ID</div>
                    <div class="app-summary-value" id="bot-session-id" style="font-size: 1rem;">{{ $state['session_id'] ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="app-summary-card">
                <span class="icon-chip icon-chip-lg icon-chip-info"><i class="fa fa-bolt"></i></span>
                <div>
                    <div class="app-summary-label">Event Terakhir</div>
                    <div class="app-summary-value" id="bot-last-event" style="font-size: 1rem;">{{ $state['last_event_type'] ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="app-summary-card">
                <span class="icon-chip icon-chip-lg icon-chip-success"><i class="fa fa-clock-o"></i></span>
                <div>
                    <div class="app-summary-label">Terakhir Terlihat (server)</div>
                    <div class="app-summary-value" id="bot-last-seen" style="font-size: 1rem;">{{ $state['last_seen_at'] ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    @php $qrVisible = $status === 'waiting_for_login'; @endphp
    <div class="row">
        <div class="col-lg-4 {{ $qrVisible ? '' : 'd-none' }}" id="qr-col">
            <div class="card border-0 shadow-sm mb-4" id="qr-card">
                <div class="app-card-header">
                    <span class="icon-chip icon-chip-warning"><i class="fa fa-qrcode"></i></span>
                    <span class="app-card-title">Scan QR Code</span>
                </div>
                <div class="card-body text-center">
                    <div id="qr-canvas" class="d-inline-block"></div>
                    <div class="text-muted small mt-2" id="qr-timestamp">{{ $state['latest_qr_at'] ?? '' }}</div>
                </div>
            </div>
        </div>

        <div class="{{ $qrVisible ? 'col-lg-8' : 'col-lg-12' }}" id="logs-col">
            <div class="card border-0 shadow-sm mb-4">
                <div class="app-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="icon-chip icon-chip-info"><i class="fa fa-file-text-o"></i></span>
                        <span class="app-card-title">Logs</span>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="auto-scroll" checked>
                        <label class="custom-control-label" for="auto-scroll">Auto-scroll</label>
                    </div>
                </div>
                <div class="card-body">
                    <div id="bot-log-container" style="height: 600px; overflow-y: auto; background: #1e1e1e; color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 0.85rem; padding: 1rem; border-radius: 4px;">
                        @foreach($logs as $entry)
                            @include('admin.bot_service._log_entry', ['entry' => $entry])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Command Confirmation Modal -->
    <div class="modal fade" id="commandModal" tabindex="-1" role="dialog" aria-labelledby="commandModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" id="commandModalHeader">
                    <h5 class="modal-title" id="commandModalLabel">
                        <i class="fa fa-exclamation-triangle"></i> <span id="commandModalTitle"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="commandModalBody"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn" id="confirmCommand">
                        <i class="fa fa-check"></i> <span id="confirmCommandLabel"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Modal -->
    <div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="infoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" id="infoModalHeader">
                    <h5 class="modal-title" id="infoModalLabel">
                        <i class="fa fa-info-circle"></i> <span id="infoModalTitle"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="infoModalBody"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
$(function () {
    var lastLogId = {{ (int) $lastLogId }};
    var pollUrl = '{{ route('admin.bot-service.logs') }}';
    var commandUrl = '{{ route('admin.bot-service.command') }}';
    var pollTimer = null;
    var qrRenderedFor = null;

    var statusMeta = {
        online: { label: 'Online' },
        waiting_for_login: { label: 'Menunggu Login (Scan QR)' },
        error: { label: 'Error' },
        offline: { label: 'Offline' },
        unknown: { label: 'Belum Ada Data' }
    };

    $('[data-toggle="tooltip"]').tooltip();

    function typeBadgeClass(type) {
        switch (type) {
            case 'error': return 'text-danger';
            case 'login': return 'text-warning';
            case 'ready': return 'text-success';
            case 'heartbeat': return 'text-muted';
            default: return 'text-info';
        }
    }

    function parseGroupList(content) {
        if (typeof content !== 'string' || content.trim().charAt(0) !== '[') {
            return null;
        }

        try {
            var parsed = JSON.parse(content);
        } catch (e) {
            return null;
        }

        if (!Array.isArray(parsed) || !parsed.every(function (g) { return g && typeof g.id === 'string' && typeof g.name === 'string'; })) {
            return null;
        }

        return parsed;
    }

    function appendLogEntry(entry) {
        var $container = $('#bot-log-container');
        var line = $('<div class="mb-1"></div>');
        line.append($('<span class="text-muted"></span>').text('[' + (entry.timestamp || entry.received_at) + '] '));
        line.append($('<span class="font-weight-bold"></span>').addClass(typeBadgeClass(entry.type)).text('[' + entry.type + '] '));

        var groups = entry.type === 'log' ? parseGroupList(entry.content) : null;

        if (entry.type === 'login') {
            var qrId = 'qr-log-' + entry.id;
            line.append($('<span></span>').text('QR code received'));
            var qrDiv = $('<div class="mt-2 mb-2 p-2 bg-white d-inline-block rounded"></div>').attr('id', qrId);
            $container.append(line);
            $container.append(qrDiv);
            new QRCode(document.getElementById(qrId), { text: entry.content, width: 180, height: 180 });
        } else if (groups) {
            line.append($('<span></span>').text('Daftar grup WhatsApp (' + groups.length + ')'));
            $container.append(line);
            $container.append(buildGroupListTable(groups));
        } else {
            line.append($('<span></span>').text(entry.content));
            $container.append(line);
        }
    }

    function buildGroupListTable(groups) {
        var $wrap = $('<div class="mb-2 mt-1 p-2 bg-white text-dark rounded d-inline-block" style="max-width: 100%;"></div>');
        var $table = $('<table class="table table-sm mb-0" style="font-size: 0.8rem;"></table>');
        var $body = $('<tbody></tbody>');

        groups.forEach(function (g) {
            var $row = $('<tr></tr>');
            $row.append($('<td></td>').text(g.name));
            $row.append($('<td class="text-muted"></td>').text(g.id));
            $body.append($row);
        });

        $table.append($body);
        $wrap.append($table);
        return $wrap;
    }

    function refreshQrCard(state, status) {
        var visible = status === 'waiting_for_login' && !!state.latest_qr;

        $('#qr-col').toggleClass('d-none', !visible);
        $('#logs-col').toggleClass('col-lg-8', visible).toggleClass('col-lg-12', !visible);

        if (visible) {
            $('#qr-timestamp').text(state.latest_qr_at || '');
            if (qrRenderedFor !== state.latest_qr) {
                $('#qr-canvas').empty();
                new QRCode(document.getElementById('qr-canvas'), { text: state.latest_qr, width: 220, height: 220 });
                qrRenderedFor = state.latest_qr;
            }
        }
    }

    function poll() {
        $.getJSON(pollUrl, { after_id: lastLogId }).done(function (res) {
            if (!res.success) {
                return;
            }

            var meta = statusMeta[res.status] || statusMeta.unknown;
            $('#bot-status-badge').text(meta.label);
            $('#bot-session-id').text(res.state.session_id || '-');
            $('#bot-last-event').text(res.state.last_event_type || '-');
            $('#bot-last-seen').text(res.state.last_seen_at || '-');

            refreshQrCard(res.state, res.status);

            if (res.logs && res.logs.length) {
                res.logs.forEach(function (entry) {
                    appendLogEntry(entry);
                    lastLogId = Math.max(lastLogId, entry.id);
                });

                if ($('#auto-scroll').is(':checked')) {
                    var $c = $('#bot-log-container');
                    $c.scrollTop($c[0].scrollHeight);
                }
            }
        }).always(function () {
            pollTimer = setTimeout(poll, 3000);
        });
    }

    function showInfoModal(title, body, headerClass) {
        $('#infoModalHeader').attr('class', 'modal-header text-white ' + (headerClass || 'bg-success'));
        $('#infoModalTitle').text(title);
        $('#infoModalBody').text(body);
        $('#infoModal').modal('show');
    }

    function sendCommand(command) {
        $.ajax({
            url: commandUrl,
            method: 'POST',
            data: { command: command, _token: $('meta[name="csrf-token"]').attr('content') }
        }).done(function () {
            showInfoModal('Berhasil', 'Command "' + command + '" dikirim ke bot.', 'bg-success');
        }).fail(function () {
            showInfoModal('Gagal', 'Gagal mengirim command.', 'bg-danger');
        });
    }

    var commandModalConfig = {
        restart: {
            title: 'Konfirmasi Restart',
            body: 'Restart bot sekarang? Session WhatsApp tetap tersimpan.',
            headerClass: 'bg-warning',
            buttonClass: 'btn-warning',
            buttonLabel: 'Restart'
        },
        delete_session: {
            title: 'Konfirmasi Delete Session',
            body: 'Hapus session bot? Bot akan logout dan butuh scan QR ulang.',
            headerClass: 'bg-danger',
            buttonClass: 'btn-danger',
            buttonLabel: 'Hapus Session'
        }
    };

    var pendingCommand = null;

    function openCommandModal(command) {
        var config = commandModalConfig[command];
        pendingCommand = command;

        $('#commandModalHeader').attr('class', 'modal-header text-white ' + config.headerClass);
        $('#commandModalTitle').text(config.title);
        $('#commandModalBody').text(config.body);
        $('#confirmCommand').attr('class', 'btn ' + config.buttonClass);
        $('#confirmCommandLabel').text(config.buttonLabel);

        $('#commandModal').modal('show');
    }

    $('#btn-restart').on('click', function () {
        openCommandModal('restart');
    });

    $('#btn-delete-session').on('click', function () {
        openCommandModal('delete_session');
    });

    $('#btn-list-group').on('click', function () {
        sendCommand('list_group');
    });

    $('#confirmCommand').on('click', function () {
        $('#commandModal').modal('hide');
        if (pendingCommand) {
            sendCommand(pendingCommand);
            pendingCommand = null;
        }
    });

    $('.qr-placeholder').each(function () {
        new QRCode(this, { text: $(this).attr('data-qr'), width: 180, height: 180 });
    });

    var $c = $('#bot-log-container');
    $c.scrollTop($c[0].scrollHeight);

    poll();
});
</script>
@endsection
