<x-auth-layout title="Verify code" back-href="{{ route('login') }}" wide>
    <p class="auth-login-form-eyebrow auth-login-mono">{{ __('Identity verification') }}</p>
    <h2 class="auth-login-form-title auth-login-serif">{{ __('Enter verification code') }}</h2>
    <p class="auth-login-form-sub">{{ __('We\'ve sent a 6-digit code to') }} <strong>{{ $maskedEmail }}</strong>.</p>

    <div x-data="verifyCode({ expiresAt: '{{ $expiresAt->toIso8601String() }}', verifyUrl: '{{ route('password.verify-code.submit') }}', resendUrl: '{{ route('password.resend-code') }}', cancelUrl: '{{ route('password.verify-code.cancel') }}' })">
        <div class="auth-login-expiry">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-show="!expired" class="auth-login-expiry-label">
                {{ __('Code expires in') }} <span x-text="countdown" class="auth-login-expiry-count" aria-hidden="true"></span>
            </span>
            <span x-show="expired" x-cloak class="auth-login-expiry-label auth-login-expiry-label--expired" role="status">
                {{ __('Your code has expired. Request a new one below.') }}
            </span>
        </div>

        <div class="auth-login-otp-row">
            @for ($i = 0; $i < 6; $i++)
                <input
                    type="text"
                    inputmode="numeric"
                    maxlength="1"
                    autocomplete="one-time-code"
                    aria-label="Digit {{ $i + 1 }} of 6"
                    x-ref="box{{ $i }}"
                    :value="code[{{ $i }}]"
                    @input="onInput({{ $i }}, $event)"
                    @keydown="onKeydown({{ $i }}, $event)"
                    @paste="onPaste({{ $i }}, $event)"
                    :disabled="expired || submitting"
                    class="auth-login-otp-box"
                >
            @endfor
        </div>

        <p x-show="error" x-cloak x-text="error" class="input-error-text mb-5" role="alert" aria-live="polite"></p>

        <button type="button" class="auth-login-submit" @click="submit" :disabled="submitting || !complete || expired">
            <span x-show="!submitting">{{ __('Verify and continue') }}</span>
            <span x-show="submitting" x-cloak>{{ __('Verifying…') }}</span>
        </button>
    </div>

    <p class="auth-login-footnote">{{ __('Didn\'t receive a code?') }}
        <button type="button" class="auth-login-resend" @click="resend" :disabled="resending || resendDisabled">{{ __('Resend it') }}</button>
    </p>
</x-auth-layout>
