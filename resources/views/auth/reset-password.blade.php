<x-auth-layout title="Set a new password">
    <p class="auth-login-form-eyebrow auth-login-mono">{{ __('Security') }}</p>
    <h2 class="auth-login-form-title auth-login-serif">{{ __('Set a new password') }}</h2>
    <p class="auth-login-form-sub">{{ __('Choose a strong password you haven\'t used before on CamelotBooks.') }}</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <input type="hidden" name="email" value="{{ old('email', $request->email) }}">

        @include('auth.partials.new-password-fields', [
            'policy' => $policy,
            'prefix' => '',
            'errorBag' => '',
            'autofocus' => true,
        ])

        <button type="submit" class="auth-login-submit">{{ __('Update password') }}</button>
    </form>

    <p class="auth-login-footnote">{{ __('Contact your administrator if you can\'t access your account.') }}</p>
</x-auth-layout>
