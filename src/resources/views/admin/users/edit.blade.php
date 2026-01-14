<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ユーザー編集: {{ $user->email }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                        @csrf
                        @method('PUT')

                        {{-- 名前 --}}
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                名前 <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $profile->name) }}"
                                required
                                maxlength="50"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 経歴 --}}
                        <div class="mb-6">
                            <label for="biography" class="block text-sm font-medium text-gray-700 mb-2">
                                経歴・自己紹介
                            </label>
                            <textarea
                                name="biography"
                                id="biography"
                                rows="6"
                                maxlength="1000"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('biography') border-red-500 @enderror"
                            >{{ old('biography', $profile->biography) }}</textarea>
                            @error('biography')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">任意（1000文字以内）</p>
                        </div>

                        {{-- サムネイル用動画 --}}
                        <div class="mb-6">
                            <label for="thumbnail_video_id" class="block text-sm font-medium text-gray-700 mb-2">
                                サムネイル用動画
                            </label>
                            <select
                                name="thumbnail_video_id"
                                id="thumbnail_video_id"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">選択しない</option>
                                @foreach($videos as $video)
                                    <option value="{{ $video->id }}"
                                            {{ old('thumbnail_video_id', $profile->thumbnail_video_id) == $video->id ? 'selected' : '' }}>
                                        {{ $video->original_filename }}
                                        ({{ $video->created_at->format('Y/m/d H:i') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('thumbnail_video_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 権限 --}}
                        <div class="mb-6">
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                権限 <span class="text-red-500">*</span>
                            </label>
                            <select
                                name="role"
                                id="role"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('role') border-red-500 @enderror"
                            >
                                <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>
                                    一般ユーザー
                                </option>
                                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>
                                    管理者
                                </option>
                            </select>
                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('admin.users.index') }}"
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded transition duration-200">
                                キャンセル
                            </a>
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded transition duration-200">
                                保存
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
