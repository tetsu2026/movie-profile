<x-guest-layout>
    <div class="container mx-auto px-4 py-16 text-center">
        <div class="max-w-md mx-auto">
            <h1 class="text-6xl font-bold text-red-600 mb-4">403</h1>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Forbidden</h2>
            <p class="text-lg text-gray-700 mb-8">この操作を実行する権限がありません</p>

            <div class="space-y-4">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded transition duration-200">
                        ダッシュボードへ戻る
                    </a>
                @else
                    <a href="{{ route('home') }}"
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded transition duration-200">
                        トップページへ戻る
                    </a>
                @endauth
            </div>
        </div>
    </div>
</x-guest-layout>
