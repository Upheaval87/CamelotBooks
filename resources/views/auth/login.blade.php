<x-auth-layout title="Sign in">
    @slot('footnote')
        {{ __('Don\'t have access?') }} <a href="mailto:support@camelotbooks.com">{{ __('Contact your administrator.') }}</a>
    @endslot

    <p class="auth-form-eyebrow">{{ __('Sign in') }}</p>
    <h2 class="auth-form-title">{{ __('Welcome back') }}</h2>
    <p class="auth-form-sub">{{ __('Enter your credentials to access your workspace.') }}</p>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="auth-form-fields">
        @csrf

        <div class="auth-form-field">
            <label for="email" class="input-label">{{ __('Email') }}</label>
            <x-text-input id="email" class="input w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@company.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div class="auth-form-field">
            <label for="password" class="input-label">{{ __('Password') }}</label>
            <div class="password-input-wrap" x-data="{ showLoginPassword: false }">
                <input
                    id="password"
                    class="input password-input-boxed w-full"
                    type="password"
                    name="password"
                    :type="showLoginPassword ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                >
                @include('auth.partials.password-toggle', ['xVar' => 'showLoginPassword'])
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="auth-login-options">
            <label for="remember_me" class="auth-login-checkbox-label">
                <input id="remember_me" type="checkbox" name="remember" class="rounded auth-login-checkbox" {{ old('remember') ? 'checked' : '' }}>
                <span>{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="auth-login-forgot" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="auth-login-submit">
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    <p class="auth-secure">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        {{ __('Protected by 256-bit encryption') }}
    </p>
</x-auth-layout>
