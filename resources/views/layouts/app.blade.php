<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', '動画プロフィール(Laravel版)') }}</title>

        <!-- Fonts: Plus Jakarta Sans + Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-M7BT87F0L2"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-M7BT87F0L2');
        </script>

        <style>
            body { font-family: 'Inter', sans-serif; }
            h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; }
            @media (prefers-reduced-motion: reduce) {
                * { transition: none !important; animation: none !important; }
            }
        </style>
    </head>
    <body class="font-sans antialiased" style="background-color: #F5F5F7; color: #1D1D1F;">
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- ページヘッダー -->
            @isset($header)
                <header style="background-color: #F5F5F7;">
                    <div class="max-w-6xl mx-auto py-6 px-6">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- ページコンテンツ -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
