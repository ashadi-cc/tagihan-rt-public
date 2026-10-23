@php
    $typeClass = [
        'error' => 'text-danger',
        'login' => 'text-warning',
        'ready' => 'text-success',
        'heartbeat' => 'text-muted',
    ][$entry['type']] ?? 'text-info';

    $groups = null;
    if ($entry['type'] === 'log' && str_starts_with(trim($entry['content']), '[')) {
        $decoded = json_decode($entry['content'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            && collect($decoded)->every(fn ($g) => isset($g['id'], $g['name']))) {
            $groups = $decoded;
        }
    }
@endphp
<div class="mb-1">
    <span class="text-muted">[{{ $entry['timestamp'] ?? $entry['received_at'] }}] </span>
    <span class="font-weight-bold {{ $typeClass }}">[{{ $entry['type'] }}] </span>
    @if($entry['type'] === 'login')
        <span>QR code received</span>
    @elseif($groups !== null)
        <span>Daftar grup WhatsApp ({{ count($groups) }})</span>
    @else
        <span>{{ $entry['content'] }}</span>
    @endif
</div>
@if($entry['type'] === 'login')
<div class="mt-2 mb-2 p-2 bg-white d-inline-block rounded qr-placeholder" data-qr="{{ $entry['content'] }}" id="qr-log-{{ $entry['id'] }}"></div>
@elseif($groups !== null)
<div class="mb-2 mt-1 p-2 bg-white text-dark rounded d-inline-block" style="max-width: 100%;">
    <table class="table table-sm mb-0" style="font-size: 0.8rem;">
        <tbody>
            @foreach($groups as $group)
            <tr>
                <td>{{ $group['name'] }}</td>
                <td class="text-muted">{{ $group['id'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
