<x-app-layout>

    <x-superadmin.layout>
        <div class="space-y-6">
            <x-superadmin.card>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-[26px] font-extrabold tracking-[-0.02em] text-gray-900">{{ $user->name }}</span>
                        @if($user->is_super_admin)
                            <x-superadmin.badge variant="accent">{{ __('Super Admin') }}</x-superadmin.badge>
                        @endif
                        @if($user->is_active)
                            <x-superadmin.badge variant="active">{{ __('Active') }}</x-superadmin.badge>
                        @else
                            <x-superadmin.badge variant="danger">{{ __('Deactivated') }}</x-superadmin.badge>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-superadmin.btn variant="edit" href="{{ route('superadmin.users.edit', $user) }}">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            {{ __('Edit') }}
                        </x-superadmin.btn>
                        @if($user->id !== auth()->id())
                            @if($user->is_active)
                                <form method="POST" action="{{ route('superadmin.users.deactivate', $user) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Deactivate this user? They will no longer be able to log in.') }}')">
                                    @csrf
                                    <x-superadmin.btn variant="danger" size="md" type="submit">{{ __('Deactivate') }}</x-superadmin.btn>
                                </form>
                            @else
                                <form method="POST" action="{{ route('superadmin.users.reactivate', $user) }}">
                                    @csrf
                                    <x-superadmin.btn type="submit">{{ __('Reactivate') }}</x-superadmin.btn>
                                </form>
                            @endif
                        @endif
                        <x-superadmin.btn variant="ghost" size="md" type="button" x-data x-on:click="$dispatch('open-modal', 'reset-password')">{{ __('Reset Password') }}</x-superadmin.btn>
                    </div>
                </div>

                <div class="detail-grid mt-6">
                    <x-detail-field label="Email">{{ $user->email }}</x-detail-field>
                    <x-detail-field label="Member Since">{{ $user->created_at?->format('M j, Y') ?? '—' }}</x-detail-field>
                    <x-detail-field label="Password Changed">{{ $user->password_changed_at?->format('M j, Y') ?? '—' }}</x-detail-field>
                </div>
            </x-superadmin.card>

            <x-superadmin.card title="{{ __('Company Assignments') }}">
                <x-slot name="action">
                    <a href="{{ route('superadmin.assignments.create') }}?user={{ $user->id }}" class="inline-flex items-center gap-1.5 rounded-[10px] border border-gold-600/35 bg-gradient-to-b from-[#fffdf8] to-[#f7f0df] px-4 py-2 text-[13px] font-bold text-gold-700 shadow-edit transition hover:-translate-y-px hover:border-gold-600/55 hover:text-gold-800 hover:shadow-edit-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('Assign Company') }}
                    </a>
                </x-slot>

                <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                    <table class="w-full min-w-[960px] border-collapse text-sm">
                        <thead>
                            <tr>
                                <x-superadmin.th>{{ __('Company') }}</x-superadmin.th>
                                <x-superadmin.th>{{ __('Role') }}</x-superadmin.th>
                                <x-superadmin.th>{{ __('Branches') }}</x-superadmin.th>
                                <x-superadmin.th align="center">{{ __('Status') }}</x-superadmin.th>
                                <x-superadmin.th align="center">{{ __('Actions') }}</x-superadmin.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td class="px-5 py-[18px] align-middle">
                                        <a href="{{ route('superadmin.companies.show', $assignment->company) }}" class="font-bold text-gray-900">{{ $assignment->company->name }}</a>
                                    </td>
                                    <td class="px-5 py-[18px] align-middle text-gray-600">{{ $assignment->role }}</td>
                                    <td class="px-5 py-[18px] align-middle text-gray-600">
                                        @if(count($assignment->branch_ids ?? []))
                                            {{ count($assignment->branch_ids ?? []) }} {{ __('branches') }}
                                        @else
                                            <span class="text-gray-400">{{ __('All branches') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-[18px] text-center align-middle">
                                        @if($assignment->is_active)
                                            <x-superadmin.badge variant="active">{{ __('Active') }}</x-superadmin.badge>
                                        @else
                                            <x-superadmin.badge variant="muted">{{ __('Inactive') }}</x-superadmin.badge>
                                        @endif
                                    </td>
                                    <td class="px-5 py-[18px] text-center align-middle">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('superadmin.assignments.edit', $assignment) }}" class="inline-flex items-center gap-1.5 rounded-[10px] border border-gold-600/35 bg-gradient-to-b from-[#fffdf8] to-[#f7f0df] px-4 py-2 text-[13px] font-bold text-gold-700 shadow-edit transition hover:-translate-y-px hover:border-gold-600/55 hover:text-gold-800 hover:shadow-edit-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                {{ __('Edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('superadmin.assignments.destroy', $assignment) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Remove this assignment? The user will lose access to this company.') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-[10px] border border-red-300 bg-white px-4 py-2 text-[13px] font-bold text-red-700 transition hover:border-red-400 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500">
                                                    {{ __('Remove') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-[18px] text-center align-middle text-gray-400">{{ __('No company assignments yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-superadmin.card>
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
