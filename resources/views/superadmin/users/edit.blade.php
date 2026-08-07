<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Edit User') }} - {{ $user->name }}" description="{{ __('Update account details and access flags.') }}">
            <x-slot name="badge">
                <x-superadmin.badge :variant="$user->is_active ? 'active' : 'danger'">{{ $user->is_active ? __('Active') : __('Deactivated') }}</x-superadmin.badge>
            </x-slot>
        </x-superadmin.page-head>

        <form method="POST" action="{{ route('superadmin.users.update', $user) }}"
            class="mx-auto flex w-full max-w-[1080px] flex-col gap-[22px]">
            @csrf
            @method('PATCH')

            <x-form-section icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" title="{{ __('User') }}" :columns="2">
                <div class="form-field">
                    <label class="sa-label" for="name">{{ __('Full Name') }}</label>
                    <input id="name" name="name" type="text" class="sa-input" required autofocus value="{{ old('name', $user->name) }}" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="email">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" class="sa-input" required value="{{ old('email', $user->email) }}" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="password">{{ __('New Password') }}</label>
                    <input id="password" name="password" type="password" class="sa-input" autocomplete="new-password" />
                    <p class="text-xs text-gray-500 mt-1">{{ __('Leave blank to keep the current password.') }}</p>
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="password_confirmation">{{ __('Confirm New Password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="sa-input" autocomplete="new-password" />
                </div>

                <div class="form-field form-field--full space-y-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_super_admin" value="1" @checked(old('is_super_admin', $user->is_super_admin)) class="rounded border-line text-accent focus:ring-accent">
                        {{ __('Platform super admin') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-line text-accent focus:ring-accent">
                        {{ __('Account active') }}
                    </label>
                </div>
            </x-form-section>

            <div class="sa-form-actions">
                <a href="{{ route('superadmin.users.show', $user) }}" class="sa-btn sa-btn--ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="sa-btn sa-btn--primary">{{ __('Save User') }}</button>
            </div>
        </form>
    </x-superadmin.layout>

</x-app-layout>
