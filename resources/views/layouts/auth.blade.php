<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' — '.config('app.name', 'CamelotBooks') : config('app.name', 'CamelotBooks') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-login">
        <div class="auth-split">
            <aside class="auth-brand">
                <div class="auth-brand-top">
                    <span class="auth-logo" aria-hidden="true">CB</span>
                    <div class="auth-wordmark">
                        <span class="auth-wordmark-name">{{ __('CamelotBooks') }}</span>
                        <span class="auth-wordmark-tag">{{ __('Enterprise Accounting') }}</span>
                    </div>
                </div>

                <h1>{{ __('Financial operations, unified.') }}</h1>
                <p class="auth-brand-lede">{{ __('Close the books faster with live bank feeds, automated reconciliation, and audit-ready reports — all in one place.') }}</p>

                <div class="auth-glass">
                    <div class="auth-glass-row">
                        <span class="auth-glass-label">{{ __('Month-end close') }}</span>
                        <span class="auth-glass-value">3.2 days <span class="auth-glass-chip">{{ __('−38% faster') }}</span></span>
                    </div>
                    <div class="auth-glass-row">
                        <span class="auth-glass-label">{{ __('Auto-reconciled transactions') }}</span>
                        <span class="auth-glass-value">96.4%</span>
                    </div>
                    <div class="auth-glass-row">
                        <span class="auth-glass-label">{{ __('Audit readiness') }}</span>
                        <span class="auth-glass-value">100% <span class="auth-glass-chip">{{ __('Always-on') }}</span></span>
                    </div>
                </div>

                <p class="auth-copy">{{ __('© 2026 CamelotBooks, Inc.') }}</p>
            </aside>

            <main class="auth-form-col">
                <div class="auth-help">{{ __('Need help?') }} <a href="mailto:support@camelotbooks.com">{{ __('Contact support') }}</a></div>

                <div class="auth-form-card{{ $centered ? ' auth-form-card--center' : '' }}">
                    {{ $slot }}

                    @if ($backHref)
                        <div class="auth-backrow">
                            <a class="auth-backlink" href="{{ $backHref }}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ __('Back to sign in') }}
                            </a>
                        </div>
                    @endif
                </div>

                @isset($footnote)
                    <p class="auth-admin">{{ $footnote }}</p>
                @endisset
            </main>
        </div>
    </body>
</html>
