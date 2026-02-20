@props([
    'videoSrc',
    'themeColor' => '#667eea',
    'videoRef' => 'popupVideo',
    'autoClose' => false,
    'buttonBackground' => null,
])

@php
    // buttonBackground が指定されていない場合は themeColor を単色で使用
    $bgStyle = $buttonBackground ?? "background-color: {$themeColor};";
@endphp

{{-- フルカード動画プレーヤー (カード全体を覆う absolute オーバーレイ) --}}
<div
    x-show="popupOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="absolute inset-0 bg-black"
    style="display: none;"
>
    {{-- 動画本体 --}}
    <video
        x-ref="{{ $videoRef }}"
        class="w-full h-full object-contain"
        playsinline
        preload="none"
        @play="playing = true"
        @pause="playing = false"
        @if($autoClose)
        @ended="closePopup()"
        @else
        @ended="playing = false"
        @endif
    >
        <source src="{{ $videoSrc }}" type="video/mp4">
    </video>

    {{-- クリッカブルオーバーレイ (再生/停止トグル) --}}
    <button
        type="button"
        class="absolute inset-0 flex items-center justify-center cursor-pointer"
        @click="playing ? $refs.{{ $videoRef }}.pause() : $refs.{{ $videoRef }}.play()"
        :aria-label="playing ? '停止' : '再生'"
    >
        {{-- 一時停止中: テーマ色の再生アイコン --}}
        <div
            x-show="!playing"
            class="rounded-full w-16 h-16 flex items-center justify-center transition-transform duration-200 hover:scale-110"
            style="{{ $bgStyle }}"
        >
            <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M8 5v14l11-7z"/>
            </svg>
        </div>

        {{-- 再生中: 下部グラデーションヒント --}}
        <div
            x-show="playing"
            class="absolute bottom-0 left-0 right-0 py-2 px-4 text-left"
            style="background: linear-gradient(to top, rgba(0,0,0,0.6), transparent);"
        >
            <span class="text-white text-xs opacity-80">クリックで停止</span>
        </div>
    </button>

    {{-- 閉じるボタン (右上、常時表示) --}}
    <button
        type="button"
        class="absolute top-3 right-3 flex items-center justify-center w-9 h-9 rounded-full text-white transition-colors duration-150 cursor-pointer"
        style="background-color: rgba(0,0,0,0.5);"
        @mouseenter="$el.style.backgroundColor='rgba(0,0,0,0.75)'"
        @mouseleave="$el.style.backgroundColor='rgba(0,0,0,0.5)'"
        @click.stop="closePopup()"
        aria-label="閉じる"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

</div>
