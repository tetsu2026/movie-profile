<x-guest-layout>
    <div class="text-center py-4">
        <p class="text-5xl font-extrabold mb-3" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">403</p>
        <h1 class="text-xl font-semibold mb-2" style="color: #1D1D1F;">アクセス権限がありません</h1>
        <p class="text-sm text-gray-400 mb-6">この操作を実行する権限がありません。</p>

        @auth
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium hover:opacity-70 transition-opacity duration-150 cursor-pointer"
               style="color: #1D1D1F;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                ダッシュボードに戻る
            </a>
        @else
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium hover:opacity-70 transition-opacity duration-150 cursor-pointer"
               style="color: #1D1D1F;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                トップページに戻る
            </a>
        @endauth
    </div>
</x-guest-layout>
