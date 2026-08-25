<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="pos-m-shell">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CamelotBooks POS' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .pos-m-shell{font-family:'Inter',system-ui,sans-serif;background:#F5F7F6;color:#13292C;margin:0;padding:0;min-height:100dvh}
        .pos-m-shell *,.pos-m-shell *::before,.pos-m-shell *::after{box-sizing:border-box}
        [x-cloak]{display:none !important}
    </style>
</head>
<body class="pos-m-body">
    @yield('content')

    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-cloak
            class="pos-m-toast pos-m-toast--success">
            <span>{{ session('success') }}</span>
            <button @click="show = false" class="pos-m-toast-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-cloak
            class="pos-m-toast pos-m-toast--error">
            <span>{{ session('error') }}</span>
            <button @click="show = false" class="pos-m-toast-close">&times;</button>
        </div>
    @endif
</body>
</html>
