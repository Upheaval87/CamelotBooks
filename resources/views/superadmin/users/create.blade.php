<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('New User') }}" description="{{ __('Create a platform account.') }}" />

        <form method="POST" action="{{ route('superadmin.users.store') }}"
            class="mx-auto flex w-full max-w-[1080px] flex-col gap-[22px]">
            @csrf

            <x-form-section icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" title="{{ __('User') }}" :columns="2">
                <div class="form-field">
                    <label class="sa-label" for="name">{{ __('Full Name') }}</label>
                    <input id="name" name="name" type="text" class="sa-input" required autofocus value="{{ old('name') }}" />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="email">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" class="sa-input" required value="{{ old('email') }}" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="password">{{ __('Password') }}</label>
                    <input id="password" name="password" type="password" class="sa-input" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <div class="form-field">
                    <label class="sa-label" for="password_confirmation">{{ __('Confirm Password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="sa-input" required autocomplete="new-password" />
                </div>

                <div class="form-field form-field--full">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_super_admin" value="1" @checked(old('is_super_admin')) class="rounded border-line text-accent focus:ring-accent">
                        {{ __('Platform super admin') }}
                    </label>
                    <p class="text-xs text-gray-500 mt-1">{{ __('Super admins can manage companies, users and modules from this panel.') }}</p>
                </div>
            </x-form-section>

            <div class="sa-form-actions">
                <a href="{{ route('superadmin.users.index') }}" class="sa-btn sa-btn--ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="sa-btn sa-btn--primary">{{ __('Create User') }}</button>
            </div>
        </form>
    </x-superadmin.layout>

</x-app-layout>
