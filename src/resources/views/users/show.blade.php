<x-guest-layout>
    <div class="min-h-screen bg-gray-50 py-8 px-4">
        <div class="max-w-4xl mx-auto">
            {{-- プロフィールヘッダー --}}
            <div class="bg-white rounded-lg shadow-md p-8 mb-6">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ $profile->name ?? 'ユーザー名未設定' }}
                </h1>

                {{-- 経歴表示 --}}
                @if($profile && $profile->biography)
                    <div class="prose max-w-none">
                        <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $profile->biography }}</p>
                    </div>
                @else
                    <p class="text-gray-500 italic">経歴が設定されていません</p>
                @endif
            </div>

            {{-- トップページに戻るリンク --}}
            <div class="mt-8 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    トップページに戻る
                </a>
            </div>
        </div>
    </div>

    {{-- サムネイル動画 --}}
    @if($profile && $profile->thumbnailVideo && $profile->thumbnailVideo->status === 'completed')
        <x-video-thumbnail
            :video="$profile->thumbnailVideo"
            :popup-video="$profile->popupVideo"
        />
    @else
        <x-video-thumbnail-placeholder />
    @endif

    {{-- ポップアップ動画モーダル --}}
    @if($profile && $profile->popupVideo && $profile->popupVideo->status === 'completed')
        <x-video-modal
            :video="$profile->popupVideo"
            :id="$profile->popupVideo->id"
        />
    @endif
</x-guest-layout>
