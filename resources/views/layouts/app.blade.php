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
        <meta name="theme-color" content="#128F8E">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <script>
            window.favouritesIndexUrl = "{{ route('favourites.index') }}";
            window.favouritesStoreUrl = "{{ route('favourites.store') }}";
            window.favouritesDestroyUrl = "{{ route('favourites.destroy', ['pageKey' => ':pageKey']) }}";
            window.favouritesReorderUrl = "{{ route('favourites.reorder') }}";
            window.favouritesPreferencesUrl = "{{ route('favourites.preferences') }}";
            window.favouritesPagesUrl = "{{ route('favourites.pages') }}";
            window.todoIndexUrl = "{{ route('todo.index') }}";
        </script>

        @if(!\Illuminate\Support\Facades\Vite::isRunningHot())
            <script src="{{ \Illuminate\Support\Facades\Vite::asset('resources/js/scoped-search-field.js') }}"></script>
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased bg-neutral-50 dark:bg-neutral-950">

        @include('layouts.topbar-two-row')

        <div class="flex flex-col min-h-screen">
            <div class="flex flex-1 min-h-0">
                @php
                    $routeName = request()->route()?->getName() ?? '';
                    $favouriteMeta = \App\Services\FavouritesService::metaForRoute($routeName);
                    if ($favouriteMeta === null) {
                        $favouriteMeta = \App\Services\FavouritesService::metaForRecord($routeName, $header ?? '');
                    }
                @endphp
                <x-favourites.sidebar :favourite-meta="$favouriteMeta" :favourite-override="isset($favourite) ? true : false" />
                <main class="flex-1 min-w-0 p-6 lg:p-8 max-w-8xl mx-auto w-full">
                    <div class="animate-fade-in-up">
                        @isset($favourite)
                            <div class="shrink-0">
                                {{ $favourite }}
                            </div>
                        @endisset
                        {{ $slot }}
                    </div>
                </main>
                @if(isset($favouriteMeta) && !isset($favourite))
                    <div class="fav-float-toggle"
                         x-data="{ store: $store.favourites }"
                         x-show="!store.pinned"
                         x-cloak>
                        <x-favourite-toggle :page-key="$favouriteMeta['key']" :label="$favouriteMeta['label']" :icon="$favouriteMeta['icon']" :url="$favouriteMeta['url']" />
                    </div>
                @endif
            </div>
        </div>


        {{-- Feedback system: toasts viewport (JS-created), flash emitter, confirm modal root --}}
        <x-feedback.flashes />
        <div id="feedback-confirm-root"></div>

        {{-- Global search modal --}}
        <x-global-search-modal :search-url="route('accounting.search.global')" />

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

            window.atlasToast = function(message, type) {
                if (window.feedback) window.feedback.toast(type || 'success', message);
            };

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(function(){});
            }
        </script>
    </body>
</html>
