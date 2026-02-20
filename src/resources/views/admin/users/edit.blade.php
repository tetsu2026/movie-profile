<x-app-layout>
    <div class="max-w-xl mx-auto px-6 py-8">

        {{-- ページタイトル --}}
        <div class="mb-6">
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 transition-colors duration-150 mb-4 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                ユーザー管理に戻る
            </a>
            <h1 class="text-2xl font-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">ユーザー編集</h1>
            <p class="text-sm text-gray-400 mt-1">{{ $user->email }}</p>
        </div>

        <div class="rounded-2xl bg-white border border-gray-100 p-6 md:p-8">
            <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- 名前 --}}
                <div>
                    <x-input-label for="name" value="名前" />
                    <x-text-input id="name" type="text" name="name"
                                  :value="old('name', $profile->name)"
                                  required maxlength="50"
                                  class="@error('name') ring-2 ring-red-300 @enderror" />
                    @error('name')
                        <p class="mt-1.5 text-xs" style="color: #DC2626;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 経歴 --}}
                <div>
                    <x-input-label for="biography" value="経歴・自己紹介" />
                    <textarea
                        name="biography"
                        id="biography"
                        rows="5"
                        maxlength="1000"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all duration-150 resize-none @error('biography') ring-2 ring-red-300 @enderror"
                        style="color: #1D1D1F;"
                    >{{ old('biography', $profile->biography) }}</textarea>
                    @error('biography')
                        <p class="mt-1.5 text-xs" style="color: #DC2626;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- サムネイル動画 --}}
                <div>
                    <x-input-label for="thumbnail_video_id" value="サムネイル用動画" />
                    <select
                        name="thumbnail_video_id"
                        id="thumbnail_video_id"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all duration-150 cursor-pointer"
                        style="color: #1D1D1F;">
                        <option value="">選択しない</option>
                        @foreach($videos as $video)
                            <option value="{{ $video->id }}"
                                    {{ old('thumbnail_video_id', $profile->thumbnail_video_id) == $video->id ? 'selected' : '' }}>
                                {{ $video->original_filename }} ({{ $video->created_at->format('Y/m/d H:i') }})
                            </option>
                        @endforeach
                    </select>
                    @error('thumbnail_video_id')
                        <p class="mt-1.5 text-xs" style="color: #DC2626;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 権限 --}}
                <div>
                    <x-input-label for="role" value="権限" />
                    <select
                        name="role"
                        id="role"
                        required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition-all duration-150 cursor-pointer @error('role') ring-2 ring-red-300 @enderror"
                        style="color: #1D1D1F;">
                        <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>一般ユーザー</option>
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>管理者</option>
                    </select>
                    @error('role')
                        <p class="mt-1.5 text-xs" style="color: #DC2626;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ボタン --}}
                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <a href="{{ route('admin.users.index') }}"
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
</x-app-layout>
