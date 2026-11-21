@props(['title', 'subtitle' => '', 'action' => ''])
<div class="page-head">
    <div>
        <div class="breadcrumb mb-1">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="mx-1">/</span><span class="text-muted">{{ $title }}</span>
        </div>
        <h1>{{ $title }}</h1>
        @if($subtitle)<p>{{ $subtitle }}</p>@endif
    </div>
    @if($action)<div>{!! $action !!}</div>@endif
</div>
