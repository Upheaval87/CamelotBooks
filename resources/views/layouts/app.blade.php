<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $companyId = session('current_company_id');
            $currencySymbol = \App\Models\SystemSetting::getValue('localization', 'currency_symbol', $companyId, '$');
        @endphp
        <meta name="currency-symbol" content="{{ $currencySymbol }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#4f46e5">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <script>
            window.currencySymbol = document.querySelector('meta[name="currency-symbol"]')?.getAttribute('content') || '$';
            window.formatMoney = function(amount) {
                var val = parseFloat(amount) || 0;
                var negative = val < 0 ? '-' : '';
                var formatted = Math.abs(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return negative + window.currencySymbol + formatted;
            };
            window.formatNumber = function(amount) {
                var val = parseFloat(amount) || 0;
                var negative = val < 0 ? '-' : '';
                return negative + Math.abs(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };
            window.currencySuffix = function(label) {
                return label + ' (' + window.currencySymbol + ')';
            };
        </script>
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            }
        </script>
    </body>
</html>
