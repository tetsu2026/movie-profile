{{-- サムネイル動画プレースホルダーコンポーネント --}}
@props([
    'size' => 'md',
    'inline' => false
])

@php
    $sizeClasses = [
        'sm' => 'w-24 h-24',
        'md' => 'w-24 h-24 md:w-28 md:h-28 lg:w-32 lg:h-32',
        'lg' => 'w-40 h-40',
    ];

    // inline モードの場合は通常フロー配置、そうでなければ fixed 配置
    $positionClasses = $inline ? '' : 'fixed bottom-8 right-8 z-50';
@endphp

<div class="{{ $positionClasses }}">
    <div class="{{ $sizeClasses[$size] }} bg-gray-200 rounded-full flex items-center justify-center shadow-lg border-4 border-white">
        <span class="text-gray-500 text-xs text-center px-2">動画未設定</span>
    </div>
</div>
