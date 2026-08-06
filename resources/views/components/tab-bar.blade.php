@props(['active' => 'dashboard', 'links' => null])

@php
    $tabs = $links ?? [
        ['key' => 'dashboard', 'label' => __('Overview'), 'href' => route('superadmin.dashboard')],
        ['key' => 'companies', 'label' => __('Companies'), 'href' => route('superadmin.companies.index')],
        ['key' => 'users', 'label' => __('Users'), 'href' => route('superadmin.users.index')],
        ['key' => 'assignments', 'label' => __('Assignments'), 'href' => route('superadmin.assignments.index')],
        ['key' => 'branch-requests', 'label' => __('Branch Requests'), 'href' => route('superadmin.branch-requests.index')],
        ['key' => 'currencies', 'label' => __('Currencies'), 'href' => route('superadmin.currencies.index')],
        ['key' => 'audit', 'label' => __('Audit Log'), 'href' => route('superadmin.audit.index')],
    ];
@endphp

<x-glass-bar variant="top" {{ $attributes->merge(['class' => 'sa-tabbar', 'aria-label' => __('Super Admin sections')]) }}>
    @foreach($tabs as $tab)
        @php $isActive = ($tab['key'] ?? null) === $active || ($tab['active'] ?? false); @endphp
        <a href="{{ $tab['href'] }}" class="sa-tab {{ $isActive ? 'is-active' : '' }}" @if($isActive) aria-current="page" @endif>
            {{ $tab['label'] }}
        </a>
    @endforeach
</x-glass-bar>
