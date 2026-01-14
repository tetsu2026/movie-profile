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

            {{-- サムネイル動画エリア --}}
            <div class="bg-white rounded-lg shadow-md p-8">
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
                        <p class="text-gray-400 text-sm mt-2">このユーザーはまだサムネイル動画を設定していません</p>
                    </div>
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
</x-guest-layout>
