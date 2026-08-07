<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Platform Users') }}" description="{{ __('Accounts that can sign in to the platform.') }}">
            <x-superadmin.btn href="{{ route('superadmin.users.create') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New User') }}
            </x-superadmin.btn>
        </x-superadmin.page-head>

        <x-superadmin.card>
            <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                <table class="w-full min-w-[960px] border-collapse text-sm">
                    <thead>
                        <tr>
                            <x-superadmin.th>{{ __('Name') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Email') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Role') }}</x-superadmin.th>
                            <x-superadmin.th align="center">{{ __('Status') }}</x-superadmin.th>
                            <x-superadmin.th align="right">{{ __('Companies') }}</x-superadmin.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($users as $user)
                            <tr>
                                <td class="px-5 py-[18px] align-middle">
                                    <a href="{{ route('superadmin.users.show', $user) }}" class="font-bold text-gray-900">{{ $user->name }}</a>
                                </td>
                                <td class="px-5 py-[18px] align-middle text-gray-500">{{ $user->email }}</td>
                                <td class="px-5 py-[18px] align-middle">
                                    @if($user->is_super_admin)
                                        <x-superadmin.badge variant="accent">{{ __('Super Admin') }}</x-superadmin.badge>
                                    @else
                                        <span class="text-gray-600">{{ __('User') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] text-center align-middle">
                                    @if($user->is_active)
                                        <x-superadmin.badge variant="active">{{ __('Active') }}</x-superadmin.badge>
                                    @else
                                        <x-superadmin.badge variant="danger">{{ __('Deactivated') }}</x-superadmin.badge>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] text-right align-middle font-semibold tabular-nums text-gray-900">{{ $user->company_assignments_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-[18px] text-center align-middle text-gray-400">{{ __('No users yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-superadmin.card>
    </x-superadmin.layout>

</x-app-layout>
