@php
    $bag = $errorBag ? $errors->{$errorBag} : $errors;
    $passwordErrors = $bag->get('password');
    $confirmationErrors = $bag->get('password_confirmation');
    $checklistId = $prefix . 'password-checklist';
    $policyJson = json_encode($policy, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
@endphp

<div
    x-data='newPassword({ policy: {!! $policyJson !!}, prefix: "{{ $prefix }}" })'
    class="auth-login-form-fields"
>
    <div class="auth-login-field">
        <label for="{{ $prefix }}password" class="input-label">{{ __('New password') }}</label>
        <div class="password-input-wrap">
            <input
                id="{{ $prefix }}password"
                type="password"
                name="password"
                x-model="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="new-password"
                placeholder="{{ __('Enter a new password') }}"
                required
                {{ $autofocus ? 'autofocus' : '' }}
                class="input password-input-boxed w-full"
                aria-describedby="{{ $checklistId }}"
            >
            @include('auth.partials.password-toggle', ['xVar' => 'showPassword'])
        </div>
        <x-input-error :messages="$passwordErrors" class="mt-2" />
    </div>

    <ul id="{{ $checklistId }}" class="auth-login-password-checklist" aria-label="{{ __('Password requirements') }}">
        <template x-for="item in policy" :key="item.key">
            <li class="auth-login-checklist-item" :class="{ 'is-met': met(item) }">
                <span class="auth-login-checklist-indicator" aria-hidden="true">
                    <svg x-show="met(item)" x-cloak class="auth-login-checklist-check" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span class="auth-login-checklist-label" x-text="item.label"></span>
            </li>
        </template>
    </ul>

    <div class="auth-login-field">
        <label for="{{ $prefix }}password_confirmation" class="input-label">{{ __('Confirm password') }}</label>
        <div class="password-input-wrap">
            <input
                id="{{ $prefix }}password_confirmation"
                type="password"
                name="password_confirmation"
                x-model="confirmation"
                :type="showConfirmation ? 'text' : 'password'"
                autocomplete="new-password"
                placeholder="{{ __('Re-enter the password') }}"
                required
                class="input password-input-boxed w-full"
            >
            @include('auth.partials.password-toggle', ['xVar' => 'showConfirmation'])
        </div>
        <x-input-error :messages="$confirmationErrors" class="mt-2" />
    </div>

    <p x-show="error" x-cloak x-text="error" class="input-error-text mb-4" role="alert" aria-live="polite"></p>
</div>
