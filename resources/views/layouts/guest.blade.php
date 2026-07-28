<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CamelotBooks') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased">
        <div class="min-h-full flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4 bg-atlas-navy">
            {{-- Brand --}}
            <div class="mb-6 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-atlas-amber flex items-center justify-center">
                    <span class="text-white font-bold text-lg">CB</span>
                </div>
                <span class="text-atlas-amber font-semibold text-xl tracking-wide">CamelotBooks</span>
            </div>

            {{-- Card --}}
            <div class="w-full sm:max-w-md px-8 py-6 bg-white rounded-lg border border-gray-200">
                {{ $slot }}
            </div>

            {{-- Footer note --}}
            <p class="mt-6 text-sm text-white/40">Contact your administrator for access.</p>
        </div>
    </body>
</html>
