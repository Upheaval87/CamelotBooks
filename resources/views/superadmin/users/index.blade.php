<x-app-layout>
    <x-slot name="header">{{ __('Users') }}</x-slot>

    @include('superadmin._nav', ['active' => 'users'])

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="list-header">
                <h1 class="font-sans italic font-semibold tracking-tight text-ink text-[1.125rem] lg:text-[1.375rem]">{{ __('Platform Users') }}</h1>
                <a href="{{ route('superadmin.users.create') }}" class="list-header-create">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('New User') }}
                </a>
            </div>

            <div class="card p-6">
                <div class="list-table-wrap">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-right">{{ __('Companies') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>
                                        <a href="{{ route('superadmin.users.show', $user) }}" class="font-medium text-ink">{{ $user->name }}</a>
                                    </td>
                                    <td class="text-gray-600">{{ $user->email }}</td>
                                    <td>
                                        @if($user->is_super_admin)
                                            <x-status-badge variant="accent">Super Admin</x-status-badge>
                                        @else
                                            <span class="text-gray-500">User</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->is_active)
                                            <x-status-badge variant="success">Active</x-status-badge>
                                        @else
                                            <x-status-badge variant="danger">Deactivated</x-status-badge>
                                        @endif
                                    </td>
                                    <td class="text-right font-semibold text-ink">{{ $user->company_assignments_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500">{{ __('No users yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
