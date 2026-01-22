<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            動画管理
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- 成功・エラーメッセージ --}}
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            {{-- ヘッダーアクション --}}
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">アップロード済み動画</h3>
                    <p class="text-sm text-gray-600">合計 {{ $videos->count() }} 本</p>
                </div>
                <a href="{{ route('videos.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                    動画をアップロード
                </a>
            </div>

            {{-- 動画一覧 --}}
            @if($videos->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ファイル名
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ステータス
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        作成日時
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        プロフィール設定
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($videos as $video)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $video->original_filename }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($video->status === 'uploading')
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    アップロード中
                                                </span>
                                            @elseif($video->status === 'encoding')
                                                <div>
                                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        エンコード中 (試行 {{ $video->retry_count + 1 }}/3)
                                                    </span>
                                                </div>
                                            @elseif($video->status === 'completed')
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    完了
                                                </span>
                                            @elseif($video->status === 'failed')
                                                <div>
                                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        エンコード失敗
                                                    </span>
                                                    @if($video->error_message)
                                                        <p class="text-sm text-red-600 mt-2">{{ Str::limit($video->error_message, 100) }}</p>
                                                    @endif
                                                    <p class="text-sm text-gray-600 mt-1">別の動画をお試しください</p>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $video->created_at->format('Y/m/d H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if(in_array($video->id, $usedVideoIds))
                                                <span class="text-xs text-orange-600 bg-orange-100 px-2 py-1 rounded">
                                                    設定中
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400 px-2 py-1">
                                                    -
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if(in_array($video->id, $usedVideoIds))
                                                {{-- 使用中の動画：警告付き確認 --}}
                                                <form method="POST" action="{{ route('videos.destroy', $video->id) }}" class="inline"
                                                      onsubmit="return confirm('この動画はプロフィールのサムネイル動画に設定されています。\n\n削除すると、サムネイル動画の設定も解除されます。\n\n本当に削除しますか？');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="force_delete" value="1">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        削除
                                                    </button>
                                                </form>
                                            @else
                                                {{-- 未使用の動画：通常の確認 --}}
                                                <form method="POST" action="{{ route('videos.destroy', $video->id) }}" class="inline"
                                                      onsubmit="return confirm('本当に削除しますか？この操作は取り消せません。');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        削除
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                {{-- 空の状態 --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">まだ動画がありません</h3>
                        <p class="mt-2 text-sm text-gray-500">動画をアップロードして、プロフィールに設定しましょう。</p>
                        <div class="mt-6">
                            <a href="{{ route('videos.create') }}"
                               class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                動画をアップロード
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ダッシュボードに戻るリンク --}}
            <div class="mt-6">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    ダッシュボードに戻る
                </a>
            </div>
        </div>
    </div>

</x-app-layout>
