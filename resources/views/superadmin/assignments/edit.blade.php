<x-app-layout>
    <x-slot name="header">{{ __('Edit Assignment') }} - {{ $assignment->user->name }}</x-slot>

    <x-superadmin.layout>
        <div class="card p-6">
                <form method="POST" action="{{ route('superadmin.assignments.update', $assignment) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label>{{ __('User') }}</x-input-label>
                            <p class="mt-1 text-sm font-medium text-ink">{{ $assignment->user->name }}</p>
                        </div>
                        <div>
                            <x-input-label>{{ __('Company') }}</x-input-label>
                            <p class="mt-1 text-sm font-medium text-ink">{{ $assignment->company->name }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="role">{{ __('Role') }}</x-input-label>
                            <select id="role" name="role" class="input mt-1 block w-full" required>
                                @foreach($roles as $code => $label)
                                    <option value="{{ $code }}" @selected(old('role', $assignment->role) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="is_active">{{ __('Status') }}</x-input-label>
                            <select id="is_active" name="is_active" class="input mt-1 block w-full">
                                <option value="1" @selected(old('is_active', $assignment->is_active))>{{ __('Active') }}</option>
                                <option value="0" @selected(!old('is_active', $assignment->is_active))>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <p class="input-label">{{ __('Branch Access') }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ __('Leave none selected for access to all branches.') }}</p>

                        @if(count($branches))
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 mt-3">
                                @foreach($branches as $branch)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="branch_ids[]" value="{{ $branch['id'] }}"
                                            @checked(in_array($branch['id'], old('branch_ids', $assignment->branch_ids ?? [])))
                                            class="rounded border-line text-accent focus:ring-accent">
                                        {{ $branch['name'] }}
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 mt-2">{{ __('No branches found for this company (or it is not provisioned).') }}</p>
                        @endif
                        <x-input-error :messages="$errors->get('branch_ids')" class="mt-1" />
                    </div>

                    <div class="flex items-center gap-3">
                        <x-button variant="primary" type="submit">{{ __('Save Assignment') }}</x-button>
                        <a href="{{ route('superadmin.users.show', $assignment->user) }}" class="btn-ghost">{{ __('Cancel') }}</a>
                    </div>
                </form>
        </div>
    </x-superadmin.layout>
</x-app-layout>
