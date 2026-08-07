@props(['active' => null])

@php
    $routeName = request()->route()?->getName() ?? '';
    $saActive = $active ?? (explode('.', substr($routeName, strlen('superadmin.')))[0] ?? 'dashboard');
@endphp

<div class="mx-auto grid w-full max-w-[1240px] grid-cols-1 items-start gap-6 px-6 py-7 lg:grid-cols-[252px_1fr]">
    <aside aria-label="{{ __('Company sections') }}" class="relative min-w-0 lg:self-stretch">
        <div class="sticky top-5 rounded-[20px] bg-white/[.66] p-3 shadow-card backdrop-blur-[14px]">
            <x-tab-bar :active="$saActive" />
        </div>
    </aside>

    <div class="min-w-0">
        {{ $slot }}
    </div>
</div>
