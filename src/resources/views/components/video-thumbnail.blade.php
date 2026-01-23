{{-- サムネイル動画コンポーネント --}}
@props([
    'video',
    'popupVideo' => null,
    'size' => 'md'
])

@php
    $sizeClasses = [
        'sm' => 'w-24 h-24',
        'md' => 'w-24 h-24 md:w-28 md:h-28 lg:w-32 lg:h-32',
        'lg' => 'w-40 h-40',
    ];
@endphp

<div class="fixed bottom-8 right-8 z-50 {{ $sizeClasses[$size] }}">
    @if($popupVideo && $popupVideo->status === 'completed')
        {{-- クリック可能なサムネイル（ポップアップ動画あり） --}}
        <button
            type="button"
            class="w-full h-full rounded-full overflow-hidden shadow-lg hover:scale-105 transition-transform cursor-pointer border-4 border-white"
            x-data
            @click="$dispatch('open-video-modal', { videoId: '{{ $popupVideo->id }}' })"
            aria-label="動画を再生"
        >
            <video
                class="w-full h-full object-cover"
                autoplay
                loop
                muted
                playsinline
                preload="metadata"
            >
                <source src="{{ $video->encoded_url }}" type="video/mp4">
            </video>
        </button>
    @else
        {{-- クリック不可のサムネイル（ポップアップ動画なし） --}}
        <div class="w-full h-full rounded-full overflow-hidden shadow-lg border-4 border-white">
            <video
                class="w-full h-full object-cover"
                autoplay
                loop
                muted
                playsinline
                preload="metadata"
            >
                <source src="{{ $video->encoded_url }}" type="video/mp4">
            </video>
        </div>
    @endif
</div>
