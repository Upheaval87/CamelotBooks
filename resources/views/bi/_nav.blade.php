<div class="an-chips" role="tablist" aria-label="Business Intelligence sections">
    @foreach ($pages as $key => $label)
        <a href="{{ route('bi.' . $key) }}"
           class="an-chip{{ $key === $activePage ? ' is-active' : '' }}"
           role="tab"
           aria-selected="{{ $key === $activePage ? 'true' : 'false' }}"
           @if ($key === $activePage) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</div>