<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            プレビュー
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- 注意書き --}}
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded" role="alert">
                <p class="font-bold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    プレビュー表示中
                </p>
                <p class="mt-1">これは公開ページのプレビューです。他のユーザーには表示されません。</p>
            </div>

            {{-- ナビゲーションボタン --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('dashboard.profile.edit') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg text-center transition duration-200 shadow-md hover:shadow-lg">
                    編集ページへ戻る
                </a>
                <a href="{{ route('dashboard') }}"
                   class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg text-center transition duration-200 shadow-md hover:shadow-lg">
                    ダッシュボードへ戻る
                </a>
                <a href="{{ route('users.show', ['id' => Auth::id()]) }}"
                   class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg text-center transition duration-200 shadow-md hover:shadow-lg">
                    公開ページを見る
                </a>
            </div>

            {{-- 公開プロフィールページと同じレイアウト --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    {{-- プロフィールヘッダー --}}
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        {{ $profile->name ?? 'ユーザー名未設定' }}
                    </h1>

                    {{-- 経歴表示 --}}
                    @if($profile && $profile->biography)
                        <div class="prose max-w-none mb-8">
                            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $profile->biography }}</p>
                        </div>
                    @else
                        <p class="text-gray-500 italic mb-8">経歴が設定されていません</p>
                    @endif
                </div>
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
</x-app-layout>
