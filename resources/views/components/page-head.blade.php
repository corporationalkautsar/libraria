@props(['title', 'subtitle' => '', 'action' => '', 'icon' => ''])
<div class="page-head">
    <div>
        <div class="breadcrumb mb-1">
            <a href="{{ route('dashboard') }}">{!! icon('house', 12) !!} Dashboard</a>
            <span class="mx-1">/</span><span class="text-muted">{{ $title }}</span>
        </div>
        <h1>@if($icon)<span class="page-head-icon">{!! icon($icon, 18) !!}</span>@endif{{ $title }}</h1>
        @if($subtitle)<p>{{ $subtitle }}</p>@endif
    </div>
    @if($action)<div>{!! $action !!}</div>@endif
</div>
