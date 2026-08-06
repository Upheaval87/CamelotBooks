<x-auth-layout title="Sign in">
    <p class="auth-login-form-eyebrow auth-login-mono">{{ __('Sign in') }}</p>
    <h2 class="auth-login-form-title auth-login-serif">{{ __('Welcome back') }}</h2>
    <p class="auth-login-form-sub">{{ __('Enter your credentials to access your workspace.') }}</p>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="auth-login-form-fields">
        @csrf

        <div class="auth-login-field">
            <label for="email" class="input-label">{{ __('Email') }}</label>
            <x-text-input id="email" class="input w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@company.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div class="auth-login-field">
            <label for="password" class="input-label">{{ __('Password') }}</label>
            <x-text-input id="password" class="input w-full" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password" />
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

    <p class="auth-login-footnote">{{ __('Don\'t have access? Contact your administrator.') }}</p>
</x-auth-layout>
