<x-app-layout>
    <x-slot name="header">{{ __('Edit User') }} - {{ $user->name }}</x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('superadmin.users.update', $user) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="name">{{ __('Full Name') }}</x-input-label>
                        <x-text-input id="name" name="name" class="mt-1 block w-full" required :value="old('name', $user->name)" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="email">{{ __('Email') }}</x-input-label>
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required :value="old('email', $user->email)" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="password">{{ __('New Password') }}</x-input-label>
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        <p class="text-xs text-gray-500 mt-1">{{ __('Leave blank to keep the current password.') }}</p>
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation">{{ __('Confirm New Password') }}</x-input-label>
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                    </div>

                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_super_admin" value="1" @checked(old('is_super_admin', $user->is_super_admin)) class="rounded border-line text-accent focus:ring-accent">
                            {{ __('Platform super admin') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-line text-accent focus:ring-accent">
                            {{ __('Account active') }}
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-button variant="primary" type="submit">{{ __('Save User') }}</x-button>
                        <a href="{{ route('superadmin.users.show', $user) }}" class="btn-ghost">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
