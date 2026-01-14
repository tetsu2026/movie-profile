<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ダッシュボード
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- 成功メッセージ --}}
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            {{-- プロフィール完成度 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">プロフィール完成度</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            @if($completionStatus['name'])
                                <span class="text-green-600 font-bold mr-2">✓</span>
                                <span>名前が設定されています</span>
                            @else
                                <span class="text-red-600 font-bold mr-2">✗</span>
                                <span class="text-red-600">名前を入力してください</span>
                            @endif
                        </li>
                        <li class="flex items-center">
                            @if($completionStatus['biography'])
                                <span class="text-green-600 font-bold mr-2">✓</span>
                                <span>経歴が設定されています</span>
                            @else
                                <span class="text-yellow-600 font-bold mr-2">-</span>
                                <span class="text-gray-600">経歴を入力してください（任意）</span>
                            @endif
                        </li>
                        <li class="flex items-center">
                            @if($completionStatus['thumbnail_video'])
                                <span class="text-green-600 font-bold mr-2">✓</span>
                                <span>サムネイル動画が設定されています</span>
                            @else
                                <span class="text-yellow-600 font-bold mr-2">-</span>
                                <span class="text-gray-600">サムネイル動画をアップロードしてください（任意）</span>
                            @endif
                        </li>
                    </ul>
                </div>
            </div>

            {{-- 動画統計 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">動画アップロード状況</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-blue-600">{{ $videoStats['total'] }}</p>
                            <p class="text-sm text-gray-600">合計</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-green-600">{{ $videoStats['completed'] }}</p>
                            <p class="text-sm text-gray-600">使用可能</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-yellow-600">{{ $videoStats['encoding'] }}</p>
                            <p class="text-sm text-gray-600">エンコード中</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-red-600">{{ $videoStats['failed'] }}</p>
                            <p class="text-sm text-gray-600">エンコード失敗</p>
                        </div>
                    </div>

                    @if($videoStats['encoding'] > 0)
                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-yellow-800 text-sm">
                                <strong>{{ $videoStats['encoding'] }}本</strong>の動画がエンコード中です。完了までお待ちください。
                            </p>
                        </div>
                    @endif

                    @if($videoStats['failed'] > 0)
                        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
                            <p class="text-red-800 text-sm">
                                <strong>{{ $videoStats['failed'] }}本</strong>の動画のエンコードに失敗しました。動画管理ページから確認してください。
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- アクションリンク --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('dashboard.profile.edit') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg text-center transition duration-200 shadow-md hover:shadow-lg">
                    プロフィール編集
                </a>
                <a href="{{ route('preview') }}"
                   class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 px-6 rounded-lg text-center transition duration-200 shadow-md hover:shadow-lg">
                    プレビュー
                </a>
                <a href="{{ route('users.show', ['id' => Auth::id()]) }}"
                   class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-4 px-6 rounded-lg text-center transition duration-200 shadow-md hover:shadow-lg">
                    公開ページを見る
                </a>

                @if(Auth::user()->role === 'admin')
                    <a href="#"
                       class="bg-red-600 hover:bg-red-700 text-white font-bold py-4 px-6 rounded-lg text-center transition duration-200 shadow-md hover:shadow-lg col-span-full">
                        ユーザー管理（管理者専用）
                    </a>
                @endif
            </div>

            {{-- 今後実装予定の機能（グレーアウト） --}}
            <div class="bg-gray-100 border border-gray-300 rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-700">今後実装予定の機能</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white p-4 rounded border border-gray-200 opacity-60">
                        <p class="text-gray-600 font-semibold">動画管理</p>
                        <p class="text-sm text-gray-500">Issue #12で実装予定</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
