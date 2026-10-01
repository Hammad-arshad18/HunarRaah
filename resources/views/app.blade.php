<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex,nofollow">

        @include('shared.theme')

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <link rel="stylesheet" href="{{ asset('studio.css') }}?v={{ filemtime(public_path('studio.css')) }}">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        <title>{{ config('app.name', 'Teaching Studio') }}</title>
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
