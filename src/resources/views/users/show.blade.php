<x-public-layout>
    <div class="min-h-screen bg-gray-50 py-8 px-4">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            {{-- プロフィールボックス --}}
            <div class="bg-white rounded-lg shadow-md p-8">
                {{-- 氏名 --}}
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ $profile->name ?? 'ユーザー名未設定' }}
                </h1>

                {{-- 経歴表示（min-heightで画面下まで表示） --}}
                <div class="min-h-[40vh] border-t border-gray-200 pt-4">
                    @if($profile && $profile->biography)
                        <div class="prose max-w-none">
                            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $profile->biography }}</p>
                        </div>
                    @else
                        <p class="text-gray-500 italic">経歴が設定されていません</p>
                    @endif
                </div>

                {{-- サムネイル動画エリア --}}
                <div class="pt-6 mt-6">
                    {{-- サムネイル動画（右下配置） --}}
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

            {{-- 管理ページへのリンク（ボックス欄外） --}}
            <div class="mt-4 text-right">
                <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    管理ページへ
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
