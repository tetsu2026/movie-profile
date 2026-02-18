<x-public-layout>
    <div class="min-h-screen py-12 px-4" style="background-color: #F5F5F7;">
        <div class="max-w-2xl mx-auto">

            {{-- プロフィールカード --}}
            <div class="rounded-3xl bg-white border border-gray-100 p-8 md:p-10">

                {{-- アクセントライン --}}
                <div class="w-10 h-1 rounded-full mb-6" style="background-color: #1D1D1F;"></div>

                {{-- 氏名 --}}
                <h1 class="text-4xl md:text-5xl font-extrabold mb-6" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F; letter-spacing: -0.02em;">
                    {{ $profile->name ?? 'ユーザー名未設定' }}
                </h1>

                {{-- 経歴 --}}
                <div class="min-h-[30vh] pt-6 border-t border-gray-100">
                    @if($profile && $profile->biography)
                        <p class="text-gray-600 whitespace-pre-wrap leading-relaxed text-base">{{ $profile->biography }}</p>
                    @else
                        <p class="text-gray-300 italic text-sm">経歴が設定されていません</p>
                    @endif
                </div>

                {{-- サムネイル動画エリア --}}
                <div class="pt-6 mt-6 border-t border-gray-100">
                    <div class="flex justify-end">
                        @if($profile && $profile->thumbnailVideo && $profile->thumbnailVideo->status === 'completed')
                            <x-video-thumbnail
                                :video="$profile->thumbnailVideo"
                                :popup-video="$profile->popupVideo"
                                :inline="true"
                            />
                        @else
                            <x-video-thumbnail-placeholder :inline="true" />
                        @endif
                    </div>
                </div>
            </div>

            {{-- 管理ページリンク --}}
            <div class="mt-4 text-right">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-400 hover:text-gray-700 transition-colors duration-150 cursor-pointer">
                    管理ページへ
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            </div>

        </div>
    </div>

    {{-- ポップアップ動画モーダル --}}
    @if($profile && $profile->popupVideo && $profile->popupVideo->status === 'completed')
        <x-video-modal
            :video="$profile->popupVideo"
            :id="$profile->popupVideo->id"
        />
    @endif
</x-public-layout>
