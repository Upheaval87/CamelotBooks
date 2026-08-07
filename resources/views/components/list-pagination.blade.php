@props(['paginator' => null, 'label' => ''])

@if($paginator && $paginator->hasPages())
    @php
        $paginator = $paginator->appends(request()->query());
        $last = $paginator->lastPage();
        $current = $paginator->currentPage();
        $window = 2;
        $start = max(1, $current - $window);
        $end = min($last, $current + $window);
        $firstItem = $paginator->firstItem() ?: 0;
        $lastItem = $paginator->lastItem() ?: 0;
    @endphp

    <div class="list-pagination">
        <span class="list-pagination-info">
            {{ __('Showing') }} {{ $firstItem }}–{{ $lastItem }} {{ __('of') }} {{ $paginator->total() }} {{ $label }}
        </span>
        <div class="list-pagination-nav">
            @if($paginator->onFirstPage())
                <button type="button" class="list-pagination-btn is-disabled" aria-disabled="true" aria-label="Previous">‹</button>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="list-pagination-btn" aria-label="Previous">‹</a>
            @endif

            @if($start > 1)
                <a href="{{ $paginator->url(1) }}" class="list-pagination-btn">1</a>
                @if($start > 2)<span class="list-pagination-ellipsis" aria-hidden="true">…</span>@endif
            @endif

            @for($page = $start; $page <= $end; $page++)
                @if($page === $current)
                    <span class="list-pagination-btn is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="list-pagination-btn">{{ $page }}</a>
                @endif
            @endfor

            @if($end < $last)
                @if($end < $last - 1)<span class="list-pagination-ellipsis" aria-hidden="true">…</span>@endif
                <a href="{{ $paginator->url($last) }}" class="list-pagination-btn">{{ $last }}</a>
            @endif

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="list-pagination-btn" aria-label="Next">›</a>
            @else
                <button type="button" class="list-pagination-btn is-disabled" aria-disabled="true" aria-label="Next">›</button>
            @endif
        </div>
    </div>
@endif
