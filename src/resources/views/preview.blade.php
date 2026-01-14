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

                    {{-- サムネイル動画エリア --}}
                    <div class="border-t border-gray-200 pt-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-6">サムネイル動画</h2>

                        @if($profile && $profile->thumbnail_video_id)
                            {{-- 後のIssueで動画表示を実装 --}}
                            <div class="flex flex-col items-center justify-center bg-gray-100 rounded-lg p-12">
                                <div class="bg-gray-300 rounded-full w-32 h-32 flex items-center justify-center mb-4">
                                    <svg class="w-16 h-16 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-600">動画表示機能は後のIssueで実装予定</p>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center bg-gray-100 rounded-lg p-12">
                                <div class="bg-gray-300 rounded-full w-32 h-32 flex items-center justify-center mb-4">
                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 text-lg">動画未設定</p>
                                <p class="text-gray-400 text-sm mt-2">プロフィール編集画面で動画を選択してください</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
