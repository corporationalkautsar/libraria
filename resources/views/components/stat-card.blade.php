@props([
    'label',
    'value',
    'icon' => 'circle',
    'tone' => 'teal',
    'foot' => '',
    'delta' => '',
    'direction' => 'up',
    'series' => [],
])
@php
    $peaks = array_filter((array) $series, 'is_numeric');
    $peak = $peaks ? max(1, max($peaks)) : 1;
@endphp
<div class="stat-card-v2 tone-{{ $tone }}">
    <span class="stat-accent"></span>
    <div class="stat-top">
        <span class="stat-icon-lg">{!! icon($icon, 20) !!}</span>
        @if($delta)<span class="stat-delta {{ $direction === 'down' ? 'down' : '' }}">{{ $delta }}</span>@endif
    </div>
    <div class="stat-label-v2">{{ $label }}</div>
    <div class="stat-value-v2"{!! is_numeric($value) ? ' data-count="'.$value.'"' : '' !!}>{{ $value }}</div>
    @if($foot)<div class="stat-foot-v2">{{ $foot }}</div>@endif
    @if($peaks)
        <div class="stat-spark" aria-hidden="true">
            @foreach($peaks as $point)
                <i style="height:{{ max(12, round($point / $peak * 100)) }}%"></i>
            @endforeach
        </div>
    @endif
</div>
