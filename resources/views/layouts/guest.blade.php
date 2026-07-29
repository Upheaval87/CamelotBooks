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
    <body class="h-full font-sans antialiased bg-gradient-to-br from-neutral-50 via-white to-neutral-100 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950">
        <div class="min-h-full flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            <div class="w-full sm:max-w-md">
                <div class="mb-8 flex flex-col items-center">
                    <div class="w-12 h-12 rounded-xl bg-accent flex items-center justify-center shadow-lg shadow-accent/20 mb-4">
                        <span class="text-white font-bold text-lg tracking-tight">CB</span>
                    </div>
                    <h1 class="text-xl font-bold text-neutral-900 dark:text-white tracking-tight">CamelotBooks</h1>
                    <p class="text-sm text-neutral-400 mt-1">Enterprise Accounting Platform</p>
                </div>

                <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-elevated border border-neutral-200 dark:border-neutral-800 px-8 py-8">
                    {{ $slot }}
                </div>

                <p class="mt-8 text-center text-xs text-neutral-400">Contact your administrator for access.</p>
            </div>
        </div>
    </body>
</html>
