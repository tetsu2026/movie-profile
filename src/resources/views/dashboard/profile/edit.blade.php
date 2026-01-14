<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            プロフィール編集
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('dashboard.profile.update') }}">
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
                                placeholder="山田 太郎"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">公開プロフィールに表示される名前です（50文字以内）</p>
                        </div>

                        {{-- 経歴 --}}
                        <div class="mb-6">
                            <label for="biography" class="block text-sm font-medium text-gray-700 mb-2">
                                経歴・自己紹介
                            </label>
                            <textarea
                                name="biography"
                                id="biography"
                                rows="8"
                                maxlength="1000"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('biography') border-red-500 @enderror"
                                placeholder="あなたの経歴や自己紹介を入力してください。&#10;&#10;例：&#10;・大学で情報工学を専攻&#10;・Web開発エンジニアとして5年の経験&#10;・趣味は登山とカメラ"
                            >{{ old('biography', $profile->biography) }}</textarea>
                            <div class="mt-1 flex justify-between text-sm">
                                <span class="text-gray-500">残り: <span id="biography-count">{{ 1000 - mb_strlen($profile->biography ?? '') }}</span> 文字</span>
                                <span class="text-gray-400">任意（1000文字以内）</span>
                            </div>
                            @error('biography')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 動画選択エリア（後のIssueで実装） --}}
                        <div class="mb-6 p-4 bg-gray-100 border border-gray-300 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">サムネイル動画選択</h3>
                            <p class="text-sm text-gray-500">動画選択機能は Issue #14 で実装予定です</p>
                        </div>

                        {{-- ボタン --}}
                        <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('dashboard') }}"
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

    <script>
        // 文字数カウント
        document.getElementById('biography').addEventListener('input', function() {
            const maxLength = 1000;
            const currentLength = this.value.length;
            document.getElementById('biography-count').textContent = maxLength - currentLength;
        });
    </script>
</x-app-layout>
