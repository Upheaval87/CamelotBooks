<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <div class="password-input-wrap" x-data="{ shown: false }">
                <input
                    id="update_password_current_password"
                    name="current_password"
                    type="password"
                    :type="shown ? 'text' : 'password'"
                    class="input password-input-boxed mt-1 w-full"
                    autocomplete="current-password"
                >
                @include('auth.partials.password-toggle', ['xVar' => 'shown'])
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        @include('auth.partials.new-password-fields', [
            'policy' => $policy,
            'prefix' => 'update_password',
            'errorBag' => 'updatePassword',
            'autofocus' => false,
        ])

        <div class="flex items-center gap-4">
            <button type="submit" class="password-form-submit">{{ __('Update password') }}</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
