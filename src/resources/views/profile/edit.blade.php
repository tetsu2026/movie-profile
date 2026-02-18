<x-app-layout>
    <div class="max-w-2xl mx-auto px-6 py-8 space-y-6">

        {{-- ページタイトル --}}
        <div class="mb-2">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 transition-colors duration-150 mb-4 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                ダッシュボードに戻る
            </a>
            <h1 class="text-2xl font-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">アカウント設定</h1>
        </div>

        {{-- プロフィール情報 --}}
        <div class="rounded-2xl bg-white border border-gray-100 p-6 md:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        {{-- パスワード変更 --}}
        <div class="rounded-2xl bg-white border border-gray-100 p-6 md:p-8">
            @include('profile.partials.update-password-form')
        </div>

        {{-- アカウント削除 --}}
        <div class="rounded-2xl bg-white border border-gray-100 p-6 md:p-8">
            @include('profile.partials.delete-user-form')
        </div>

    </div>
</x-app-layout>
