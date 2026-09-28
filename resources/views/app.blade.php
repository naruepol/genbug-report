<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <meta name="theme-color" content="#ffffff">

        <title inertia>{{ config('app.name') }}</title>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="bg-page font-sans text-ink antialiased">
        @inertia
    </body>
</html>
