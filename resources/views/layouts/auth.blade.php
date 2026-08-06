<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' — '.config('app.name', 'CamelotBooks') : config('app.name', 'CamelotBooks') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-login">
        <aside class="auth-login-hero">
            <div class="auth-login-hero-top">
                <div class="auth-login-monogram" aria-hidden="true">CB</div>
                <div class="auth-login-wordmark">
                    <span class="auth-login-wordmark-name">{{ __('CamelotBooks') }}</span>
                    <span class="auth-login-wordmark-tag">{{ __('Enterprise Accounting') }}</span>
                </div>
            </div>

            <div class="auth-login-hero-copy">
                <p class="auth-login-eyebrow auth-login-mono">{{ __('ENTERPRISE ACCOUNTING PLATFORM') }}</p>
                <h1 class="auth-login-headline auth-login-serif">{{ __('Enterprise financial operations, unified.') }}</h1>
                <p class="auth-login-sub">{{ __('Close the books faster with live bank feeds, automated reconciliation, and audit-ready reports — all in one place.') }}</p>
            </div>

            <div class="auth-login-hero-bottom">
                <div class="auth-login-trust auth-login-mono">
                    <span>{{ __('SOC 2 Type II') }}</span>
                    <span>{{ __('99.99% Uptime SLA') }}</span>
                    <span>{{ __('256-bit Encryption') }}</span>
                </div>
                <p class="auth-login-copyright">{{ __('© 2026 CamelotBooks, Inc.') }}</p>
            </div>
        </aside>

        <main class="auth-login-panel">
            <div class="auth-login-card{{ $wide ? ' auth-login-card--wide' : '' }}">
                <div class="auth-login-header">
                    @if ($backHref)
                        <a href="{{ $backHref }}" class="auth-login-back-link">&larr; {{ __('Back to sign in') }}</a>
                    @else
                        <span class="auth-login-header-spacer" aria-hidden="true"></span>
                    @endif
                    <a href="mailto:support@camelotbooks.com" class="auth-login-help-link">{{ __('Need help? Contact support') }}</a>
                </div>

                {{ $slot }}
            </div>
        </main>
    </body>
</html>
