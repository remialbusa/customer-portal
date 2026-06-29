@php
    $total = $pending + $errored + $synced;

    if ($total === 0) {
        $color = 'secondary';
        $label = '—';
        $icon  = '·';
        $hidden = 'style="display:none"';
    } elseif ($errored > 0) {
        $color = 'danger';
        $label = "{$errored} failed";
        $icon  = '⚠';
        $hidden = '';
    } elseif ($pending > 0) {
        $color = 'warning';
        $label = "{$pending} queued";
        $icon  = '◌';
        $hidden = '';
    } else {
        $color = 'success';
        $label = "{$synced} synced";
        $icon  = '✓';
        $hidden = '';
    }
@endphp

<span
    class="badge bg-{{ $color }} tsr-sync-badge"
    {!! $hidden !!}
    wire:poll.30s="refresh"
    title="TSR sync status: {{ $label }}"
    data-total="{{ $total }}"
    data-pending="{{ $pending }}"
    data-errored="{{ $errored }}"
    data-synced="{{ $synced }}"
>
    {{ $icon }} {{ $label }}
</span>
