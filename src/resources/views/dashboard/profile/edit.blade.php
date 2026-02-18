<x-app-layout>
    <div class="max-w-2xl mx-auto px-6 py-8">

        {{-- ページタイトル --}}
        <div class="mb-6">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 transition-colors duration-150 mb-4 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                ダッシュボードに戻る
            </a>
            <h1 class="text-2xl font-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">プロフィール編集</h1>
        </div>

        <div class="rounded-2xl bg-white border border-gray-100 p-6 md:p-8">
            <form method="POST" action="{{ route('dashboard.profile.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- 名前 --}}
                <div>
                    <x-input-label for="name" value="名前" />
                    <span class="text-xs text-gray-400 mb-2 block">公開プロフィールに表示されます（50文字以内）</span>
                    <x-text-input id="name" type="text" name="name"
                                  :value="old('name', $profile->name)"
                                  required maxlength="50"
                                  placeholder="山田 太郎"
                                  class="@error('name') ring-2 ring-red-300 @enderror" />
                    @error('name')
                        <p class="mt-1.5 text-xs" style="color: #DC2626;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 経歴 --}}
                <div>
                    <x-input-label for="biography" value="経歴・自己紹介" />
                    <span class="text-xs text-gray-400 mb-2 block">任意（1000文字以内）</span>
                    <textarea
                        name="biography"
                        id="biography"
                        rows="7"
                        maxlength="1000"
                        placeholder="あなたの経歴や自己紹介を入力してください。"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all duration-150 resize-none @error('biography') ring-2 ring-red-300 @enderror"
                        style="color: #1D1D1F;"
                    >{{ old('biography', $profile->biography) }}</textarea>
                    <div class="mt-1.5 flex justify-end">
                        <span class="text-xs text-gray-400">残り <span id="biography-count">{{ 1000 - mb_strlen($profile->biography ?? '') }}</span> 文字</span>
                    </div>
                    @error('biography')
                        <p class="mt-1.5 text-xs" style="color: #DC2626;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- サムネイル動画 --}}
                <div>
                    <x-input-label for="thumbnail_video_id" value="サムネイル動画" />
                    <span class="text-xs text-gray-400 mb-2 block">画面右下に円形で自動再生される動画</span>
                    <select
                        name="thumbnail_video_id"
                        id="thumbnail_video_id"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all duration-150 cursor-pointer @error('thumbnail_video_id') ring-2 ring-red-300 @enderror"
                        style="color: #1D1D1F;">
                        <option value="">選択しない</option>
                        @foreach($completedVideos as $video)
                            <option value="{{ $video->id }}"
                                    @if(old('thumbnail_video_id', $profile->thumbnail_video_id) == $video->id) selected @endif>
                                {{ $video->original_filename }} ({{ $video->created_at->format('Y/m/d H:i') }})
                            </option>
                        @endforeach
                    </select>
                    @error('thumbnail_video_id')
                        <p class="mt-1.5 text-xs" style="color: #DC2626;">{{ $message }}</p>
                    @enderror

                    @if($completedVideos->count() === 0)
                        <div class="mt-3 rounded-xl px-4 py-3 text-sm" style="background-color: #FFFBEB; color: #92400E;">
                            エンコード完了済みの動画がありません。
                            <a href="{{ route('videos.index') }}" class="underline font-medium">動画管理ページ</a>からアップロードしてください。
                        </div>
                    @endif
                </div>

                {{-- ポップアップ動画 --}}
                <div>
                    <x-input-label for="popup_video_id" value="ポップアップ動画" />
                    <span class="text-xs text-gray-400 mb-2 block">サムネイル動画クリック時にモーダルで表示される動画</span>
                    <select
                        name="popup_video_id"
                        id="popup_video_id"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all duration-150 cursor-pointer @error('popup_video_id') ring-2 ring-red-300 @enderror"
                        style="color: #1D1D1F;">
                        <option value="">選択しない</option>
                        @foreach($completedVideos as $video)
                            <option value="{{ $video->id }}"
                                    @if(old('popup_video_id', $profile->popup_video_id) == $video->id) selected @endif>
                                {{ $video->original_filename }} ({{ $video->created_at->format('Y/m/d H:i') }})
                            </option>
                        @endforeach
                    </select>
                    @error('popup_video_id')
                        <p class="mt-1.5 text-xs" style="color: #DC2626;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ボタン --}}
                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center px-6 py-2.5 rounded-full text-sm font-medium border border-gray-200 bg-white hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                       style="color: #1D1D1F;">
                        キャンセル
                    </a>
                    <x-primary-button>
                        保存する
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 文字数カウント
        document.getElementById('biography').addEventListener('input', function() {
            document.getElementById('biography-count').textContent = 1000 - this.value.length;
        });
    </script>
</x-app-layout>
