@props(['label', 'value', 'icon' => 'circle', 'tone' => 'teal', 'foot' => ''])
<div class="stat-card">
    <div class="stat-icon {{ $tone }}">{!! icon($icon, 21) !!}</div>
    <div>
        <div class="stat-label">{{ $label }}</div>
        <div class="stat-value">{{ $value }}</div>
        @if($foot)<div class="stat-foot">{{ $foot }}</div>@endif
    </div>
</div>
