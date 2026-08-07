<x-app-layout>
    <x-slot name="header">{{ __('User Detail') }} - {{ $user->name }}</x-slot>

    <x-superadmin.layout>
        <div class="space-y-6">
            <div class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-semibold text-ink">{{ $user->name }}</span>
                        @if($user->is_super_admin)
                            <x-status-badge variant="accent">Super Admin</x-status-badge>
                        @endif
                        @if($user->is_active)
                            <x-status-badge variant="success">Active</x-status-badge>
                        @else
                            <x-status-badge variant="danger">Deactivated</x-status-badge>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('superadmin.users.edit', $user) }}" class="btn-ghost">{{ __('Edit') }}</a>
                        @if($user->id !== auth()->id())
                            @if($user->is_active)
                                <form method="POST" action="{{ route('superadmin.users.deactivate', $user) }}" onsubmit="return confirm('{{ __('Deactivate this user? They will no longer be able to log in.') }}')">
                                    @csrf
                                    <x-button variant="danger" type="submit">{{ __('Deactivate') }}</x-button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('superadmin.users.reactivate', $user) }}">
                                    @csrf
                                    <x-button variant="primary" type="submit">{{ __('Reactivate') }}</x-button>
                                </form>
                            @endif
                        @endif
                        <x-button variant="ghost" type="button" x-data x-on:click="$dispatch('open-modal', 'reset-password')">{{ __('Reset Password') }}</x-button>
                    </div>
                </div>

                <div class="detail-grid mt-6">
                    <x-detail-field label="Email">{{ $user->email }}</x-detail-field>
                    <x-detail-field label="Member Since">{{ $user->created_at?->format('M j, Y') ?? '—' }}</x-detail-field>
                    <x-detail-field label="Password Changed">{{ $user->password_changed_at?->format('M j, Y') ?? '—' }}</x-detail-field>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-ink">{{ __('Company Assignments') }}</h3>
                    <a href="{{ route('superadmin.assignments.create') }}?user={{ $user->id }}" class="btn-ghost btn-sm">{{ __('Assign Company') }}</a>
                </div>

                <div class="list-table-wrap">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>{{ __('Company') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Branches') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                                <th class="text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td>
                                        <a href="{{ route('superadmin.companies.show', $assignment->company) }}" class="font-medium text-ink">{{ $assignment->company->name }}</a>
                                    </td>
                                    <td>{{ $assignment->role }}</td>
                                    <td>
                                        @if(count($assignment->branch_ids ?? []))
                                            <span class="text-gray-600">{{ count($assignment->branch_ids ?? []) }} {{ __('branches') }}</span>
                                        @else
                                            <span class="text-gray-400">{{ __('All branches') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($assignment->is_active)
                                            <x-status-badge variant="success">Active</x-status-badge>
                                        @else
                                            <x-status-badge variant="default">Inactive</x-status-badge>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('superadmin.assignments.edit', $assignment) }}" class="text-sm text-accent hover:underline">{{ __('Edit') }}</a>
                                            <form method="POST" action="{{ route('superadmin.assignments.destroy', $assignment) }}" onsubmit="return confirm('{{ __('Remove this assignment? The user will lose access to this company.') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-brick hover:underline">{{ __('Remove') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500">{{ __('No company assignments yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-superadmin.layout>

    <x-modal name="reset-password" maxWidth="sm">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-ink">{{ __('Reset Password') }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ __('Set a new password for') }} {{ $user->name }}.</p>

            <form method="POST" action="{{ route('superadmin.users.reset-password', $user) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <x-input-label for="new_password">{{ __('New Password') }}</x-input-label>
                    <x-text-input id="new_password" name="new_password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('new_password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="new_password_confirmation">{{ __('Confirm New Password') }}</x-input-label>
                    <x-text-input id="new_password_confirmation" name="new_password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-button variant="ghost" type="button" x-data x-on:click="$dispatch('close-modal', 'reset-password')">{{ __('Cancel') }}</x-button>
                    <x-button variant="primary" type="submit">{{ __('Save Password') }}</x-button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>
