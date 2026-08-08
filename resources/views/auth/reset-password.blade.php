<x-auth-layout title="Set a new password" back-href="{{ route('login') }}">
    @slot('footnote')
        {{ __('You\'ll be signed out of all other devices after reset.') }}
    @endslot

    <div class="auth-halo" aria-hidden="true">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="5" y="10" width="14" height="10" rx="2.5" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg>
    </div>
    <p class="auth-form-eyebrow">{{ __('Secure reset') }}</p>
    <h2 class="auth-form-title">{{ __('Set a new password') }}</h2>
    <p class="auth-form-sub">{{ __('Choose a strong password you haven\'t used before on this workspace.') }}</p>

    <form method="POST" action="{{ route('password.store') }}" class="auth-form-fields">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <input type="hidden" name="email" value="{{ old('email', $request->email) }}">

        @include('auth.partials.new-password-fields', [
            'policy' => $policy,
            'prefix' => '',
            'errorBag' => '',
            'autofocus' => true,
        ])

        <button type="submit" class="auth-login-submit">{{ __('Set new password') }}</button>
    </form>
</x-auth-layout>
