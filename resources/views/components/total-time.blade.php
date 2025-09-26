@php
    $totalSeconds = $times->sum('duration');
    $h = str_pad(floor($totalSeconds / 3600), 2, '0', STR_PAD_LEFT);
    $m = str_pad(floor(($totalSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
    $s = str_pad($totalSeconds % 60, 2, '0', STR_PAD_LEFT);
@endphp
<div>
    <b>Загальний час:</b> {{ $h }}:{{ $m }}:{{ $s }}
</div>
