<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $companyId = session('current_company_id');
            $currencySymbol = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');
        @endphp
        <meta name="currency-symbol" content="{{ $currencySymbol }}">

        <title>{{ config('app.name', 'CamelotBooks') }}</title>

        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#041C44">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased bg-atlas-canvas">

        {{-- ═══ Sidebar (desktop only) ═══ --}}
        @include('layouts.sidebar')

        {{-- ═══ Main area ═══ --}}
        <div class="flex flex-col ml-[216px] min-h-screen lg:ml-[216px] max-lg:ml-0">

            {{-- ═══ Top Navigation Bar ═══ --}}
            @include('layouts.topnav')

            {{-- ═══ Page Content ═══ --}}
            <main class="flex-1 p-6">
                @isset($header)
                    <div class="mb-6">
                        {{ $header }}
                    </div>
                @endisset
                {{ $slot }}
            </main>
        </div>

        {{-- ═══ Mobile sidebar overlay ═══ --}}
        <div x-data="{ mobileOpen: false }"
             x-on:open-mobile-sidebar.window="mobileOpen = true"
             x-on:close-mobile-sidebar.window="mobileOpen = false"
             x-on:keydown.escape.window="mobileOpen = false"
             class="lg:hidden">

            {{-- Backdrop --}}
            <div x-show="mobileOpen" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 bg-navy/50" x-cloak x-on:click="mobileOpen = false"></div>

            {{-- Drawer --}}
            <div x-show="mobileOpen" x-transition:enter="transition-transform duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition-transform duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 class="fixed inset-y-0 left-0 z-50 w-[216px] overflow-y-auto" x-cloak>
                @include('layouts.sidebar-nav-content')
            </div>
        </div>

        {{-- ═══ Toast Container ═══ --}}
        <div id="toast-container" class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

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

            /* ── Atlas Toast ── */
            window.atlasToast = function(message, type) {
                type = type || 'success';
                var container = document.getElementById('toast-container');
                if (!container) return;
                var colors = { success: 'border-atlas-blue', warning: 'border-atlas-amber', error: 'border-atlas-danger' };
                var icons = {
                    success: '<svg class="w-5 h-5 text-atlas-blue shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                    warning: '<svg class="w-5 h-5 text-atlas-amber shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    error: '<svg class="w-5 h-5 text-atlas-danger shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
                };
                var persist = type === 'error' ? '' : 'setTimeout(function(){ t.remove(); }, 4000)';
                var t = document.createElement('div');
                t.className = 'pointer-events-auto flex items-center gap-3 bg-white border-l-[3px] ' + (colors[type] || colors.success) + ' rounded-lg px-4 py-3 shadow-lg min-w-[280px] max-w-sm';
                t.innerHTML = (icons[type] || icons.success) + '<span class="text-sm text-atlas-navy">' + message + '</span>';
                container.appendChild(t);
                if (persist) { var id = setTimeout(function(){ if (t.parentNode) t.remove(); }, 5000); }
            };

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            }
        </script>
    </body>
</html>
