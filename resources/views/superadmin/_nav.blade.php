@props(['active' => 'dashboard'])

<div class="max-w-8xl mx-auto sm:px-6 lg:px-8 mb-6">
    <nav class="flex flex-wrap items-center gap-x-1 gap-y-1 border-b border-line pb-2">
        <x-nav-link :href="route('superadmin.dashboard')" :active="$active === 'dashboard'">{{ __('Overview') }}</x-nav-link>
        <x-nav-link :href="route('superadmin.companies.index')" :active="$active === 'companies'">{{ __('Companies') }}</x-nav-link>
        <x-nav-link :href="route('superadmin.users.index')" :active="$active === 'users'">{{ __('Users') }}</x-nav-link>
        <x-nav-link :href="route('superadmin.assignments.index')" :active="$active === 'assignments'">{{ __('Assignments') }}</x-nav-link>
        <x-nav-link :href="route('superadmin.currencies.index')" :active="$active === 'currencies'">{{ __('Currencies') }}</x-nav-link>
        <x-nav-link :href="route('superadmin.audit.index')" :active="$active === 'audit'">{{ __('Audit Log') }}</x-nav-link>
    </nav>
</div>
