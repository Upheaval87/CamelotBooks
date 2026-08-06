@props(['icon' => null, 'title' => null, 'columns' => null, 'titleBind' => null])

<section {{ $attributes->merge(['class' => 'elevated-card elevated-card--padded form-section']) }}>
    @if($titleBind || $title || $icon)
        <div class="form-section-head">
            @if($icon)
                <span class="form-section-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                    </svg>
                </span>
            @endif
            @if($titleBind)
                <h3 class="form-section-title" x-text="{{ $titleBind }}"></h3>
            @else
                <h3 class="form-section-title">{{ $title }}</h3>
            @endif
        </div>
    @endif

    @if($columns)
        <div class="form-section-grid" style="--sa-cols: {{ $columns }}">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</section>
