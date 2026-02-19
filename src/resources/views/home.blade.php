<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', '動画プロフィール') }}</title>

    <!-- Fonts: Plus Jakarta Sans + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; animation: none !important; }
        }
    </style>
</head>
<body class="font-sans antialiased" style="background-color: #F5F5F7; color: #1D1D1F;">

    {{-- ========== ナビゲーション ========== --}}
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200/80">
        <div class="max-w-6xl mx-auto px-6 h-14 flex items-center justify-between">
            <span class="text-base font-bold tracking-tight" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">
                動画プロフィール
            </span>
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}"
                   class="text-sm font-medium px-4 py-2 rounded-full text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors duration-200 cursor-pointer">
                    ログイン
                </a>
                <a href="{{ route('register') }}"
                   class="text-sm font-semibold px-4 py-2 rounded-full text-white transition-colors duration-200 cursor-pointer"
                   style="background-color: #1D1D1F;">
                    無料で始める
                </a>
            </div>
        </div>
    </nav>

    {{-- ========== ヒーローセクション ========== --}}
    <section class="max-w-6xl mx-auto px-6 pt-20 pb-16 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold mb-6 border border-gray-200"
             style="background-color: #fff; color: #2563EB;">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            VIDEO PROFILE PLATFORM
        </div>
        <h1 class="text-5xl md:text-7xl font-extrabold leading-tight mb-6" style="letter-spacing: -0.03em;">
            動画で、<br class="md:hidden">あなたを伝える
        </h1>
        <p class="text-lg md:text-xl text-gray-500 max-w-xl mx-auto leading-relaxed mb-10">
            動画付き自己紹介ページを5分で作成。<br>
            採用担当者に、あなたの魅力を直接届けよう。
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('register') }}"
               class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-full text-sm font-semibold text-white transition-all duration-200 hover:opacity-90 cursor-pointer"
               style="background-color: #1D1D1F;">
                無料で始める
                {{-- 矢印アイコン --}}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </a>
            <a href="{{ route('login') }}"
               class="inline-flex items-center justify-center px-8 py-3.5 rounded-full text-sm font-semibold transition-all duration-200 cursor-pointer border border-gray-300 bg-white hover:bg-gray-50"
               style="color: #1D1D1F;">
                ログインして続ける
            </a>
        </div>
    </section>

    {{-- ========== ベントーグリッド: 機能紹介 ========== --}}
    <section class="max-w-6xl mx-auto px-6 py-16">
        <div class="mb-10">
            <p class="text-xs font-semibold tracking-widest text-gray-400 uppercase mb-2">Features</p>
            <h2 class="text-3xl md:text-4xl font-bold" style="letter-spacing: -0.02em;">すべてが、シンプルに</h2>
        </div>

        {{-- メインベントーグリッド --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-5">

            {{-- 大カード: メイン機能 (col-span-2, row-span-2) --}}
            <div class="col-span-2 row-span-2 rounded-3xl p-8 flex flex-col justify-between min-h-64 cursor-pointer transition-transform duration-300 hover:scale-[1.01]"
                 style="background-color: #1D1D1F; color: #fff;">
                {{-- ビデオアイコン --}}
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-6" style="background-color: rgba(255,255,255,0.1);">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M12 18.75H4.5a2.25 2.25 0 01-2.25-2.25V9m12.841 9.091L16.5 19.5m-1.409-1.409c.407-.24.857-.264 1.272-.08l1.524.7a.75.75 0 001.028-.68v-9.5a.75.75 0 00-1.028-.68l-1.524.7a1.125 1.125 0 01-1.272-.08L11.25 7.5"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-bold mb-3">動画で、<br>直接伝わる</h3>
                    <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.6);">
                        テキストだけでは伝わらない声のトーン、表情、熱量を動画でそのまま届けられます。
                        採用担当者の記憶に残るプロフィールを作りましょう。
                    </p>
                </div>
            </div>

            {{-- カードA: 5分で完成 --}}
            <div class="col-span-1 rounded-3xl p-6 flex flex-col justify-between min-h-32 bg-white cursor-pointer transition-transform duration-300 hover:scale-[1.02]">
                <span class="text-4xl md:text-5xl font-extrabold" style="color: #1D1D1F; letter-spacing: -0.04em;">5分</span>
                <p class="text-sm font-medium text-gray-500 mt-2">で完成</p>
            </div>

            {{-- カードB: URLシェア --}}
            <div class="col-span-1 rounded-3xl p-6 flex flex-col justify-between min-h-32 cursor-pointer transition-transform duration-300 hover:scale-[1.02]"
                 style="background-color: #2563EB; color: #fff;">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: rgba(255,255,255,0.2);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold mt-4">URLで<br>シェア</p>
            </div>

            {{-- カードC: 1分対応 --}}
            <div class="col-span-1 rounded-3xl p-6 flex flex-col justify-between min-h-32 bg-white cursor-pointer transition-transform duration-300 hover:scale-[1.02]">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: #F5F5F7;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color: #1D1D1F;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-3xl font-extrabold" style="color: #1D1D1F; letter-spacing: -0.04em;">1分</span>
                    <p class="text-xs text-gray-500 mt-1">動画に対応</p>
                </div>
            </div>

            {{-- カードD: 100MB --}}
            <div class="col-span-1 rounded-3xl p-6 flex flex-col justify-between min-h-32 bg-white cursor-pointer transition-transform duration-300 hover:scale-[1.02]">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: #F5F5F7;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color: #1D1D1F;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                </div>
                <div>
                    <span class="text-3xl font-extrabold" style="color: #1D1D1F; letter-spacing: -0.04em;">100MB</span>
                    <p class="text-xs text-gray-500 mt-1">まで対応</p>
                </div>
            </div>

            {{-- ワイドカード: プレビュー機能 --}}
            <div class="col-span-2 md:col-span-3 rounded-3xl p-7 bg-white flex flex-col md:flex-row items-start md:items-center gap-6 cursor-pointer transition-transform duration-300 hover:scale-[1.01]">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0" style="background-color: #F5F5F7;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="color: #2563EB;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold mb-1" style="color: #1D1D1F;">リアルタイムプレビュー</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">プロフィールの変更が即座に画面に反映されます。公開前に仕上がりを確認できるので安心です。</p>
                </div>
            </div>

            {{-- カードE: モバイル対応 --}}
            <div class="col-span-2 md:col-span-1 rounded-3xl p-6 flex flex-col justify-between cursor-pointer transition-transform duration-300 hover:scale-[1.02]"
                 style="background-color: #ECFDF5;">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background-color: rgba(255,255,255,0.7);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color: #059669;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18h3"/>
                    </svg>
                </div>
                <div class="mt-6">
                    <h3 class="text-sm font-bold" style="color: #065F46;">スマホでも<br>美しく</h3>
                </div>
            </div>

        </div>
    </section>

    {{-- ========== 統計セクション ========== --}}
    <section class="max-w-6xl mx-auto px-6 py-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-5">
            <div class="rounded-3xl p-7 bg-white text-center cursor-default">
                <p class="text-4xl font-extrabold mb-1" style="color: #1D1D1F; letter-spacing: -0.04em;">1,000+</p>
                <p class="text-sm text-gray-500">登録ユーザー</p>
            </div>
            <div class="rounded-3xl p-7 bg-white text-center cursor-default">
                <p class="text-4xl font-extrabold mb-1" style="color: #2563EB; letter-spacing: -0.04em;">99.9%</p>
                <p class="text-sm text-gray-500">稼働率</p>
            </div>
            <div class="rounded-3xl p-7 bg-white text-center cursor-default">
                <p class="text-4xl font-extrabold mb-1" style="color: #1D1D1F; letter-spacing: -0.04em;">5分</p>
                <p class="text-sm text-gray-500">平均作成時間</p>
            </div>
            <div class="rounded-3xl p-7 text-center cursor-default"
                 style="background-color: #1D1D1F;">
                <p class="text-4xl font-extrabold mb-1 text-white" style="letter-spacing: -0.04em;">無料</p>
                <p class="text-sm" style="color: rgba(255,255,255,0.5);">スタート</p>
            </div>
        </div>
    </section>

    {{-- ========== 使い方セクション ========== --}}
    <section class="max-w-6xl mx-auto px-6 py-16">
        <div class="mb-10">
            <p class="text-xs font-semibold tracking-widest text-gray-400 uppercase mb-2">How it works</p>
            <h2 class="text-3xl md:text-4xl font-bold" style="letter-spacing: -0.02em;">3ステップで完成</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5">
            {{-- ステップ1 --}}
            <div class="rounded-3xl p-8 bg-white cursor-default">
                <span class="text-xs font-bold tracking-widest text-gray-300 uppercase">Step 01</span>
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center my-6" style="background-color: #F5F5F7;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="color: #1D1D1F;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold mb-2" style="color: #1D1D1F;">アカウントを作成</h3>
                <p class="text-sm text-gray-500 leading-relaxed">メールアドレスだけで30秒で登録完了。クレジットカードは不要です。</p>
            </div>

            {{-- ステップ2 --}}
            <div class="rounded-3xl p-8 bg-white cursor-default">
                <span class="text-xs font-bold tracking-widest text-gray-300 uppercase">Step 02</span>
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center my-6" style="background-color: #F5F5F7;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="color: #1D1D1F;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold mb-2" style="color: #1D1D1F;">動画をアップロード</h3>
                <p class="text-sm text-gray-500 leading-relaxed">スマホで撮影した動画をそのままアップロード。最大100MB、1分まで対応。</p>
            </div>

            {{-- ステップ3 --}}
            <div class="rounded-3xl p-8 cursor-default transition-transform duration-300"
                 style="background-color: #1D1D1F; color: #fff;">
                <span class="text-xs font-bold tracking-widest uppercase" style="color: rgba(255,255,255,0.3);">Step 03</span>
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center my-6" style="background-color: rgba(255,255,255,0.1);">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold mb-2">URLをシェア</h3>
                <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.6);">専用URLをSNSや履歴書に貼るだけ。採用担当者がどこからでも閲覧できます。</p>
            </div>
        </div>
    </section>

    {{-- ========== プロフィール例ベントーグリッド ========== --}}
    <section class="max-w-6xl mx-auto px-6 py-16">
        <div class="mb-10">
            <p class="text-xs font-semibold tracking-widest text-gray-400 uppercase mb-2">Examples</p>
            <h2 class="text-3xl md:text-4xl font-bold" style="letter-spacing: -0.02em;">こんなプロフィールが作れる</h2>
        </div>

        {{-- 3カード均等グリッド --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5 items-stretch">

            {{-- プロフィールカード1: インディゴ --}}
            <div class="rounded-3xl bg-white p-6 border-2 flex flex-col relative overflow-hidden transition-shadow duration-300 hover:shadow-lg"
                 style="border-color: #667eea;"
                 x-data="{
                     popupOpen: false,
                     playing: false,
                     thumbVisible: true,
                     openPopup() {
                         this.thumbVisible = false;
                         // 160ms: コンテンツのフェードアウト(opacity transition 200ms)が始まってから動画を表示
                         setTimeout(() => {
                             this.popupOpen = true;
                             this.$nextTick(() => this.$refs.sampleVideo.play());
                         }, 160);
                     },
                     closePopup() {
                         this.popupOpen = false;
                         this.$refs.sampleVideo.pause();
                         this.$refs.sampleVideo.load();
                         // 220ms: 動画のフェードアウト(opacity transition 200ms)が完了してからコンテンツを再表示
                         setTimeout(() => { this.thumbVisible = true; }, 220);
                     }
                 }"
            >
                {{-- 通常コンテンツ --}}
                <div class="flex flex-col flex-1 transition-opacity duration-200"
                     :class="{ 'opacity-0 pointer-events-none': !thumbVisible }">
                    <h3 class="text-2xl font-extrabold mb-5"
                        style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F; letter-spacing: -0.02em;">
                        田中 太郎
                    </h3>
                    <div class="pt-4 border-t border-gray-100 flex-1">
                        <p class="text-sm text-gray-500 leading-relaxed">
                            フルスタックエンジニアとして5年の経験。ReactとLaravelが得意です。
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-gray-100">
                        <div class="flex justify-end">
                            <button
                                type="button"
                                @click="openPopup()"
                                class="w-16 h-16 rounded-full border-4 flex items-center justify-center cursor-pointer hover:scale-105 transition-transform duration-200 focus:outline-none"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-color: #667eea;"
                                aria-label="サンプル動画を再生"
                            >
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- フルカード動画オーバーレイ --}}
                <x-fullcard-video-player
                    :videoSrc="config('app.demo_video_url', '/cat-work.mp4')"
                    videoRef="sampleVideo"
                    buttonBackground="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"
                    :autoClose="true"
                />
            </div>

            {{-- プロフィールカード2: ローズ --}}
            <div class="rounded-3xl bg-white p-6 border-2 flex flex-col relative overflow-hidden transition-shadow duration-300 hover:shadow-lg"
                 style="border-color: #f5576c;"
                 x-data="{
                     popupOpen: false,
                     playing: false,
                     thumbVisible: true,
                     openPopup() {
                         this.thumbVisible = false;
                         // 160ms: コンテンツのフェードアウト(opacity transition 200ms)が始まってから動画を表示
                         setTimeout(() => {
                             this.popupOpen = true;
                             this.$nextTick(() => this.$refs.sampleVideo.play());
                         }, 160);
                     },
                     closePopup() {
                         this.popupOpen = false;
                         this.$refs.sampleVideo.pause();
                         this.$refs.sampleVideo.load();
                         // 220ms: 動画のフェードアウト(opacity transition 200ms)が完了してからコンテンツを再表示
                         setTimeout(() => { this.thumbVisible = true; }, 220);
                     }
                 }"
            >
                {{-- 通常コンテンツ --}}
                <div class="flex flex-col flex-1 transition-opacity duration-200"
                     :class="{ 'opacity-0 pointer-events-none': !thumbVisible }">
                    <h3 class="text-2xl font-extrabold mb-5"
                        style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F; letter-spacing: -0.02em;">
                        佐藤 花子
                    </h3>
                    <div class="pt-4 border-t border-gray-100 flex-1">
                        <p class="text-sm text-gray-500 leading-relaxed">
                            UIデザイン3年目。ユーザー中心設計で使いやすいプロダクトを作ります。
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-gray-100">
                        <div class="flex justify-end">
                            <button
                                type="button"
                                @click="openPopup()"
                                class="w-16 h-16 rounded-full border-4 flex items-center justify-center cursor-pointer hover:scale-105 transition-transform duration-200 focus:outline-none"
                                style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-color: #f5576c;"
                                aria-label="サンプル動画を再生"
                            >
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- フルカード動画オーバーレイ --}}
                <x-fullcard-video-player
                    :videoSrc="config('app.demo_video_url', '/cat-work.mp4')"
                    videoRef="sampleVideo"
                    buttonBackground="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"
                    :autoClose="true"
                />
            </div>

            {{-- プロフィールカード3: スカイ --}}
            <div class="rounded-3xl bg-white p-6 border-2 flex flex-col relative overflow-hidden transition-shadow duration-300 hover:shadow-lg"
                 style="border-color: #4facfe;"
                 x-data="{
                     popupOpen: false,
                     playing: false,
                     thumbVisible: true,
                     openPopup() {
                         this.thumbVisible = false;
                         // 160ms: コンテンツのフェードアウト(opacity transition 200ms)が始まってから動画を表示
                         setTimeout(() => {
                             this.popupOpen = true;
                             this.$nextTick(() => this.$refs.sampleVideo.play());
                         }, 160);
                     },
                     closePopup() {
                         this.popupOpen = false;
                         this.$refs.sampleVideo.pause();
                         this.$refs.sampleVideo.load();
                         // 220ms: 動画のフェードアウト(opacity transition 200ms)が完了してからコンテンツを再表示
                         setTimeout(() => { this.thumbVisible = true; }, 220);
                     }
                 }"
            >
                {{-- 通常コンテンツ --}}
                <div class="flex flex-col flex-1 transition-opacity duration-200"
                     :class="{ 'opacity-0 pointer-events-none': !thumbVisible }">
                    <h3 class="text-2xl font-extrabold mb-5"
                        style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F; letter-spacing: -0.02em;">
                        鈴木 一郎
                    </h3>
                    <div class="pt-4 border-t border-gray-100 flex-1">
                        <p class="text-sm text-gray-500 leading-relaxed">
                            マーケター歴8年。データドリブンな施策で成果を出します。
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-gray-100">
                        <div class="flex justify-end">
                            <button
                                type="button"
                                @click="openPopup()"
                                class="w-16 h-16 rounded-full border-4 flex items-center justify-center cursor-pointer hover:scale-105 transition-transform duration-200 focus:outline-none"
                                style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-color: #4facfe;"
                                aria-label="サンプル動画を再生"
                            >
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- フルカード動画オーバーレイ --}}
                <x-fullcard-video-player
                    :videoSrc="config('app.demo_video_url', '/cat-work.mp4')"
                    videoRef="sampleVideo"
                    buttonBackground="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);"
                    :autoClose="true"
                />
            </div>

        </div>

    </section>

    {{-- ========== 第2ベントーグリッド: 詳細機能 ========== --}}
    <section class="max-w-6xl mx-auto px-6 py-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-5">

            {{-- 対応フォーマット --}}
            <div class="col-span-2 rounded-3xl p-7 bg-white cursor-default">
                <p class="text-xs font-semibold tracking-widest text-gray-400 uppercase mb-4">対応フォーマット</p>
                <div class="flex flex-wrap gap-2">
                    @foreach(['MP4', 'MOV', 'AVI', 'WMV'] as $fmt)
                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold border border-gray-200" style="color: #1D1D1F; background-color: #F5F5F7;">
                            {{ $fmt }}
                        </span>
                    @endforeach
                </div>
                <p class="text-sm text-gray-500 mt-4 leading-relaxed">スマホで撮影した動画をそのままアップロードできます。変換は自動で行われます。</p>
            </div>

            {{-- エンコード --}}
            <div class="col-span-1 rounded-3xl p-6 cursor-default" style="background-color: #EFF6FF;">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-4" style="background-color: #DBEAFE;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color: #1D4ED8;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold mb-1" style="color: #1E40AF;">自動エンコード</h3>
                <p class="text-xs leading-relaxed" style="color: #1D4ED8;">アップロード後、自動でWeb最適化処理。待つだけで完了します。</p>
            </div>

            {{-- 管理ダッシュボード --}}
            <div class="col-span-1 rounded-3xl p-6 cursor-default" style="background-color: #FFF7ED;">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-4" style="background-color: #FED7AA;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color: #C2410C;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold mb-1" style="color: #9A3412;">管理ダッシュボード</h3>
                <p class="text-xs leading-relaxed" style="color: #C2410C;">動画の管理・切り替えが直感的なダッシュボードでできます。</p>
            </div>

        </div>
    </section>

    {{-- ========== 最終CTAセクション ========== --}}
    <section class="max-w-6xl mx-auto px-6 py-10 pb-24">
        <div class="rounded-3xl p-12 md:p-16 text-center cursor-default" style="background-color: #1D1D1F;">
            <p class="text-xs font-semibold tracking-widest uppercase mb-4" style="color: rgba(255,255,255,0.4);">Get Started</p>
            <h2 class="text-3xl md:text-5xl font-bold text-white mb-4" style="letter-spacing: -0.02em;">
                今すぐ始めよう
            </h2>
            <p class="text-sm md:text-base mb-10 max-w-md mx-auto leading-relaxed" style="color: rgba(255,255,255,0.5);">
                無料で登録して、動画付きプロフィールを作成しましょう。設定は5分で完了します。
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-full text-sm font-semibold transition-all duration-200 hover:opacity-90 cursor-pointer"
                   style="background-color: #fff; color: #1D1D1F;">
                    無料で始める
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center px-8 py-3.5 rounded-full text-sm font-semibold transition-all duration-200 cursor-pointer border"
                   style="border-color: rgba(255,255,255,0.2); color: rgba(255,255,255,0.7);">
                    ログインして続ける
                </a>
            </div>
        </div>
    </section>

    {{-- ========== フッター ========== --}}
    <footer class="border-t border-gray-200 py-8">
        <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <span class="text-sm font-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">
                動画プロフィール
            </span>
            <p class="text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name', '動画プロフィール') }}. All rights reserved.
            </p>
        </div>
    </footer>

</body>
</html>
