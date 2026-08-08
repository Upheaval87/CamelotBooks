<x-auth-layout title="Forgot password" back-href="{{ route('login') }}">
    @slot('footnote')
        {{ __('Links expire after 30 minutes for your security.') }}
    @endslot

    <div class="auth-halo" aria-hidden="true">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="4" y="6" width="16" height="13" rx="2.5" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 8l8 6 8-6"/></svg>
    </div>
    <p class="auth-form-eyebrow">{{ __('Reset access') }}</p>
    <h2 class="auth-form-title">{{ __('Forgot your password?') }}</h2>
    <p class="auth-form-sub">{{ __('Enter the email linked to your account and we\'ll send a secure reset link.') }}</p>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    @if ($errors->get('email'))
        <x-input-error :messages="$errors->get('email')" class="mb-5" />
    @endif

    <form x-ref="form" method="POST" action="{{ route('password.email') }}" @submit.prevent="submit" class="auth-form-fields" x-data="forgotPassword">
        @csrf

        <div class="auth-form-field">
            <label for="email" class="input-label">{{ __('Email') }}</label>
            <input
                id="email"
                type="email"
                name="email"
                x-model="email"
                autocomplete="email"
                placeholder="you@company.com"
                required
                class="input w-full{{ $errors->has('email') ? ' is-error' : '' }}"
                :class="{ 'is-error': error }"
                :disabled="submitting"
            >
            <p x-show="error" x-cloak x-text="error" class="input-error-text" role="alert" aria-live="polite"></p>
        </div>

        <button type="submit" class="auth-login-submit" :disabled="submitting || !valid">
            <span x-show="!submitting">{{ __('Send reset link') }}</span>
            <span x-show="submitting" x-cloak>{{ __('Sending…') }}</span>
        </button>
    </form>
</x-auth-layout>
