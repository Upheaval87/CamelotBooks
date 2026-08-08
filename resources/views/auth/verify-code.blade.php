<x-auth-layout title="Verify code" back-href="{{ route('login') }}" centered>
    @slot('footnote')
        {{ __('Codes expire after 5 minutes.') }}
    @endslot

    <div class="auth-halo" aria-hidden="true">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.5 12l2 2 3.5-3.5"/></svg>
    </div>
    <p class="auth-form-eyebrow">{{ __('Verification') }}</p>
    <h2 class="auth-form-title">{{ __('Enter verification code') }}</h2>
    <p class="auth-form-sub">{{ __('We sent a 6-digit code to') }} <strong>{{ $maskedEmail }}</strong>.</p>

    <div
        class="auth-form-fields"
        x-data="verifyCode({ expiresAt: '{{ $expiresAt->toIso8601String() }}', verifyUrl: '{{ route('password.verify-code.submit') }}', resendUrl: '{{ route('password.resend-code') }}', cancelUrl: '{{ route('password.verify-code.cancel') }}' })"
    >
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

        <p x-show="error" x-cloak x-text="error" class="input-error-text" role="alert" aria-live="polite"></p>

        <button type="button" class="auth-login-submit" @click="submit" :disabled="submitting || !complete || expired">
            <span x-show="!submitting">{{ __('Verify & continue') }}</span>
            <span x-show="submitting" x-cloak>{{ __('Verifying…') }}</span>
        </button>

        <div class="auth-login-resend-row">
            <span>{{ __('Didn\'t receive it?') }}</span>
            <button type="button" class="auth-login-resend" @click="resend" :disabled="resending || resendDisabled">{{ __('Resend') }}</button>
            <span x-show="resendDisabled" class="auth-login-resend-timer">in <b x-text="resendLabel">0:42</b></span>
            <span x-show="expired && !resendDisabled" x-cloak class="auth-login-resend-timer auth-login-resend-timer--expired" role="status">{{ __('Your code has expired — request a new one.') }}</span>
        </div>
    </div>
</x-auth-layout>
