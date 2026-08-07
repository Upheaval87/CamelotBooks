<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Platform Users') }}" description="{{ __('Accounts that can sign in to the platform.') }}">
            <a href="{{ route('superadmin.users.create') }}" class="inline-flex items-center gap-2 rounded-[12px] border border-white/20 bg-gradient-to-b from-gold-500 to-gold-600 px-5 py-3 text-sm font-semibold text-white shadow-new transition hover:-translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New User') }}
            </a>
        </x-superadmin.page-head>

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
    </x-superadmin.layout>

</x-app-layout>
