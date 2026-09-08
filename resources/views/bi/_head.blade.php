@php($page = $activePage ?? '')
<div class="an-head">
    <div class="an-head-copy">
        <h1 class="an-title">{{ $title }}</h1>
        <p class="an-sub">{{ $subtitle }}</p>
        <p class="an-meta"><span class="an-meta-chip">{{ $period['label'] }}</span> {{ $period['from'] }} &#8594; {{ $period['to'] }}</p>
    </div>
    <div class="an-head-actions">
        @php($query = array_merge(request()->query(), ['page' => $page, 'period' => $period['key']]))
        <a href="{{ route('bi.print', $query) }}" class="an-btn an-btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print
        </a>
        <a href="{{ route('bi.export', $query) }}" class="an-btn an-btn-cta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </a>
    </div>
</div>