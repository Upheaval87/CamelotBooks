<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>404 — {{ config('app.name', 'CamelotBooks') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased">
        <div class="min-h-full flex flex-col items-center justify-center px-4 bg-atlas-navy">
            <div class="mb-8 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-atlas-amber flex items-center justify-center">
                    <span class="text-white font-bold text-lg">CB</span>
                </div>
                <span class="text-atlas-amber font-semibold text-xl tracking-wide">CamelotBooks</span>
            </div>
            <div class="w-full max-w-md text-center px-8 py-10 bg-white rounded-lg border border-gray-200">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-atlas-blue/10 flex items-center justify-center">
                    <span class="text-atlas-blue text-2xl font-bold">404</span>
                </div>
                <h1 class="text-xl font-semibold text-atlas-navy mb-2">Page not found</h1>
                <p class="text-sm text-navy-400 mb-6">The page you're looking for doesn't exist or has been moved.</p>
                <a href="{{ url('/') }}" class="inline-flex items-center px-5 py-2 bg-atlas-amber text-white text-sm font-semibold rounded-md hover:brightness-110 transition">
                    Back to Home
                </a>
            </div>
        </div>
    </body>
</html>
