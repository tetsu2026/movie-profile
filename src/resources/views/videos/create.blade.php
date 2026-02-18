<x-app-layout>
    <div class="max-w-xl mx-auto px-6 py-8">

        {{-- ページタイトル --}}
        <div class="mb-6">
            <a href="{{ route('videos.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 transition-colors duration-150 mb-4 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                動画管理に戻る
            </a>
            <h1 class="text-2xl font-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">動画をアップロード</h1>
        </div>

        {{-- エラーメッセージ --}}
        @if(session('error'))
            <div class="mb-5 rounded-2xl px-5 py-4 text-sm font-medium flex items-center gap-3"
                 style="background-color: #FFF1F2; color: #991B1B; border: 1px solid #FECDD3;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- アップロードフォーム --}}
        <div class="rounded-2xl bg-white border border-gray-100 p-6 md:p-8">

            {{-- 制限事項 --}}
            <div class="rounded-xl p-4 mb-6" style="background-color: #EFF6FF;">
                <p class="text-xs font-semibold tracking-widest uppercase mb-2" style="color: #2563EB;">アップロード制限</p>
                <ul class="space-y-1">
                    <li class="flex items-center gap-2 text-sm" style="color: #1D4ED8;">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        ファイルサイズ: 100MB以内
                    </li>
                    <li class="flex items-center gap-2 text-sm" style="color: #1D4ED8;">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        動画の長さ: 1分以内
                    </li>
                    <li class="flex items-center gap-2 text-sm" style="color: #1D4ED8;">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        対応形式: MP4, MOV, AVI, WMV
                    </li>
                </ul>
            </div>

            <form method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="video" value="動画ファイル" />
                    <div class="mt-2">
                        <input
                            type="file"
                            name="video"
                            id="video"
                            accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv"
                            required
                            class="w-full text-sm text-gray-500 cursor-pointer
                                   file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0
                                   file:text-sm file:font-semibold file:cursor-pointer
                                   file:transition-colors file:duration-150"
                            style="file:background-color: #F5F5F7; file:color: #1D1D1F;"
                        >
                    </div>
                    @error('video')
                        <p class="mt-1.5 text-xs" style="color: #DC2626;">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-gray-400">MP4, MOV, AVI, WMV 形式に対応</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <a href="{{ route('videos.index') }}"
                       class="inline-flex items-center justify-center px-6 py-2.5 rounded-full text-sm font-medium border border-gray-200 bg-white hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                       style="color: #1D1D1F;">
                        キャンセル
                    </a>
                    <x-primary-button>
                        アップロード
                    </x-primary-button>
                </div>
            </form>
        </div>

        {{-- 注意事項 --}}
        <div class="mt-4 rounded-xl px-4 py-3 text-sm" style="background-color: #FFFBEB; color: #92400E;">
            アップロード後、自動でエンコード処理が行われます。完了まで数分かかる場合があります。
        </div>

    </div>
</x-app-layout>
