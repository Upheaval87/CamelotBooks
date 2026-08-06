<x-auth-layout title="Forgot password" back-href="{{ route('login') }}">
    <p class="auth-login-form-eyebrow auth-login-mono">{{ __('Account recovery') }}</p>
    <h2 class="auth-login-form-title auth-login-serif">{{ __('Reset your password') }}</h2>
    <p class="auth-login-form-sub">{{ __('Enter the email associated with your account. If it matches our records, we\'ll email you a 6-digit verification code.') }}</p>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    @if ($errors->get('email'))
        <x-input-error :messages="$errors->get('email')" class="mb-5" />
    @endif

    <div x-data="forgotPassword">
        <form x-ref="form" method="POST" action="{{ route('password.email') }}" @submit.prevent="submit" class="auth-login-form-fields" x-show="!sent">
            @csrf

            <div class="auth-login-field">
                <label for="email" class="input-label">{{ __('Email') }}</label>
                <input id="email" type="email" name="email" x-model="email" autocomplete="email" placeholder="you@company.com" required class="input w-full" :disabled="submitting">
                <p x-show="error" x-cloak x-text="error" class="input-error-text mt-1.5" role="alert" aria-live="polite"></p>
            </div>

            <button type="submit" class="auth-login-submit" :disabled="submitting || !valid">
                <span x-show="!submitting">{{ __('Send verification code') }}</span>
                <span x-show="submitting" x-cloak>{{ __('Sending…') }}</span>
            </button>
        </form>

        <div x-show="sent" x-cloak class="auth-login-recovery-confirm" role="status" aria-live="polite">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <div class="auth-login-recovery-confirm-body">
                <p>{{ __('If that email matches an account, we\'ve sent a verification code to it.') }}</p>
                <a href="{{ route('password.verify-code') }}" class="auth-login-recovery-action">{{ __('Enter verification code') }}</a>
            </div>
        </div>
    </div>

    <p class="auth-login-footnote">{{ __('Remembered your password?') }} <a href="{{ route('login') }}" class="auth-login-footnote-link">{{ __('Sign in') }}</a></p>
</x-auth-layout>
