<x-app-layout>

    <div class="sa-page py-6" style="background: #F8F9FC;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="sa-page-head">
                <div>
                    <h1 class="sa-page-title">{{ __('Platform Users') }}</h1>
                    <p class="sa-page-subtitle">{{ __('Accounts that can sign in to the platform.') }}</p>
                </div>
                <a href="{{ route('superadmin.users.create') }}" class="sa-btn sa-btn--primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('New User') }}
                </a>
            </div>

            <x-elevated-card :flush="true">
                <div class="sa-table-wrap">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th class="sa-table-center">{{ __('Status') }}</th>
                                <th class="sa-table-num">{{ __('Companies') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>
                                        <a href="{{ route('superadmin.users.show', $user) }}" class="sa-table-primary">{{ $user->name }}</a>
                                    </td>
                                    <td><span style="color: var(--sa-muted);">{{ $user->email }}</span></td>
                                    <td>
                                        @if($user->is_super_admin)
                                            <span class="sa-pill sa-pill--accent">{{ __('Super Admin') }}</span>
                                        @else
                                            <span style="color: var(--sa-muted);">{{ __('User') }}</span>
                                        @endif
                                    </td>
                                    <td class="sa-table-center">
                                        @if($user->is_active)
                                            <span class="sa-pill sa-pill--accent">{{ __('Active') }}</span>
                                        @else
                                            <span class="sa-pill sa-pill--danger">{{ __('Deactivated') }}</span>
                                        @endif
                                    </td>
                                    <td class="sa-table-num" style="font-weight: 600;">{{ $user->company_assignments_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="sa-table-empty">{{ __('No users yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-elevated-card>
        </div>
    </div>
</x-app-layout>
