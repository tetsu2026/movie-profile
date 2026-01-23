{{-- ポップアップ動画モーダルコンポーネント --}}
@props([
    'video',
    'id'
])

<div
    x-data="{ open: false }"
    x-show="open"
    x-on:open-video-modal.window="if ($event.detail.videoId === '{{ $id }}') { open = true; }"
    x-on:keydown.escape.window="if (open) { open = false; $refs.video.pause(); }"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] flex items-center justify-center"
    style="display: none;"
>
    {{-- オーバーレイ --}}
    <div
        class="absolute inset-0 bg-black/70"
        @click="open = false; $refs.video.pause()"
    ></div>

    {{-- モーダルコンテンツ --}}
    <div class="relative z-10 w-full max-w-4xl mx-4">
        {{-- 閉じるボタン --}}
        <button
            type="button"
            class="absolute -top-12 right-0 text-white hover:text-gray-300 transition p-2"
            @click="open = false; $refs.video.pause()"
            aria-label="閉じる"
        >
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        {{-- 動画プレイヤー --}}
        <video
            x-ref="video"
            class="w-full rounded-lg shadow-2xl max-h-[80vh]"
            controls
            playsinline
            preload="none"
        >
            <source src="{{ $video->encoded_url }}" type="video/mp4">
            <p class="text-white">お使いのブラウザは動画再生に対応していません。</p>
        </video>
    </div>
</div>
