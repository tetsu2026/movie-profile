<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col bg-gray-100">
        {{-- メインコンテンツ --}}
        <main class="flex-1 flex flex-col items-center justify-center px-4 py-12">
            <div class="max-w-4xl mx-auto text-center">
                {{-- メインタイトル --}}
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    動画付き自己紹介プラットフォーム
                </h1>

                {{-- サブタイトル --}}
                <p class="text-lg md:text-xl text-gray-600 mb-12 max-w-2xl mx-auto">
                    動画を使った魅力的な自己紹介ページを簡単に作成できます。<br>
                    あなたの個性を動画で表現しましょう。
                </p>

                {{-- アクションボタン --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-20">
                    <a href="{{ route('register') }}"
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                        新規登録
                    </a>
                    <a href="{{ route('login') }}"
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                        ログイン
                    </a>
                </div>
            </div>

            {{-- 機能説明 --}}
            <div class="max-w-3xl mx-auto w-full mt-16">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white p-6 rounded-lg shadow text-left">
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">簡単作成</h3>
                        <p class="text-gray-600">
                            動画をアップロードするだけで、プロフェッショナルな自己紹介ページを作成できます。
                        </p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow text-left">
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">動画で魅力を</h3>
                        <p class="text-gray-600">
                            テキストだけでは伝わらない、あなたの人柄や個性を動画で表現できます。
                        </p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow text-left">
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">簡単シェア</h3>
                        <p class="text-gray-600">
                            作成したプロフィールページは専用URLで簡単にシェアできます。
                        </p>
                    </div>
                </div>
            </div>
        </main>

        {{-- フッター --}}
        <footer class="py-6 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}
        </footer>
    </div>
</body>
</html>
