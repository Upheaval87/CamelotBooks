@props(['active' => 'dashboard', 'links' => null])

@php
    $tabs = $links ?? [
        ['key' => 'dashboard', 'label' => __('Overview'), 'href' => route('superadmin.dashboard'), 'icon' => 'M4 4h5v5H4V4zm11 0h5v5h-5V4zM4 15h5v5H4v-5zm11 0h5v5h-5v-5z'],
        ['key' => 'companies', 'label' => __('Companies'), 'href' => route('superadmin.companies.index'), 'icon' => 'M3 21h18M6 21V7a2 2 0 012-2h8a2 2 0 012 2v14M14 21v-4h-4v4'],
        ['key' => 'users', 'label' => __('Users'), 'href' => route('superadmin.users.index'), 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['key' => 'assignments', 'label' => __('Assignments'), 'href' => route('superadmin.assignments.index'), 'icon' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z'],
        ['key' => 'branch-requests', 'label' => __('Branch Requests'), 'href' => route('superadmin.branch-requests.index'), 'icon' => 'M7 17.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zm10-10a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM7 15V9m0 0a6 6 0 006 6h3'],
        ['key' => 'currencies', 'label' => __('Currencies'), 'href' => route('superadmin.currencies.index'), 'icon' => 'M4 6h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V7a1 1 0 011-1zm8 9a3 3 0 100-6 3 3 0 000 6zM18 12h.01'],
        ['key' => 'audit', 'label' => __('Audit Log'), 'href' => route('superadmin.audit.index'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ];
@endphp

<p class="px-2 pb-1.5 text-[0.786rem] font-bold uppercase tracking-[0.08em] text-gray-400">{{ __('Company Admin') }}</p>

<nav class="flex gap-1 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible lg:pb-0" aria-label="{{ __('Company sections') }}">
    @foreach($tabs as $tab)
        @php $isActive = ($tab['key'] ?? null) === $active || ($tab['active'] ?? false); @endphp
        @if($isActive)
            <a href="{{ $tab['href'] }}"
               aria-current="page"
               class="relative flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-[0.964rem] font-semibold text-[#f2f6fa] bg-gradient-to-b from-[#17565D] to-[#0C3539] shadow-[inset_0_1px_0_rgba(255,255,255,.10),0_4px_10px_-4px_rgba(0,0,0,.35)] before:absolute before:left-0 before:top-2 before:bottom-2 before:w-[3px] before:rounded-full before:bg-gold-500 before:content-[''] before:hidden lg:before:block focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500 [&_svg]:text-[#DFF7F6]">
        @else
            <a href="{{ $tab['href'] }}"
               class="relative flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-[0.964rem] font-semibold text-gray-600 transition-all duration-150 hover:bg-[rgba(17,69,75,.06)] hover:text-[#0B2A2D] active:bg-[rgba(17,69,75,.12)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500 lg:whitespace-normal">
        @endif
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $tab['icon'] }}" />
            </svg>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
