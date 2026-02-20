<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            動画アップロード
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            {{-- エラーメッセージ --}}
            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- アップロード制限 --}}
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
                        <p class="font-bold mb-2">アップロード制限</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>ファイルサイズ: 100MB以内</li>
                            <li>動画の長さ: 1分以内</li>
                            <li>対応形式: mp4, mov, avi, wmv</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-6">
                            <label for="video" class="block text-sm font-medium text-gray-700 mb-2">
                                動画ファイル <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="file"
                                name="video"
                                id="video"
                                accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv"
                                required
                                class="block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-blue-50 file:text-blue-700
                                    hover:file:bg-blue-100
                                    cursor-pointer"
                            >
                            @error('video')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">mp4, mov, avi, wmv形式の動画ファイルを選択してください</p>
                        </div>

                        <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('videos.index') }}"
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded transition duration-200">
                                キャンセル
                            </a>
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded transition duration-200">
                                アップロード
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 注意事項 --}}
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded p-4">
                <p class="text-sm text-yellow-800">
                    <strong>注意:</strong> アップロードした動画は自動的にエンコードされます。エンコード完了まで数分かかる場合があります。
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
