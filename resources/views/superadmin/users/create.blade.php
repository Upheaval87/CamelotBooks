<x-app-layout>
    <x-slot name="header">{{ __('New User') }}</x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('superadmin.users.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name">{{ __('Full Name') }}</x-input-label>
                        <x-text-input id="name" name="name" class="mt-1 block w-full" required autofocus :value="old('name')" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="email">{{ __('Email') }}</x-input-label>
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required :value="old('email')" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="password">{{ __('Password') }}</x-input-label>
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation">{{ __('Confirm Password') }}</x-input-label>
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_super_admin" value="1" @checked(old('is_super_admin')) class="rounded border-line text-accent focus:ring-accent">
                            {{ __('Platform super admin') }}
                        </label>
                        <p class="text-xs text-gray-500 mt-1">{{ __('Super admins can manage companies, users and modules from this panel.') }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-button variant="primary" type="submit">{{ __('Create User') }}</x-button>
                        <a href="{{ route('superadmin.users.index') }}" class="btn-ghost">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
