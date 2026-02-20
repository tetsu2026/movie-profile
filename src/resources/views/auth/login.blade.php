<x-guest-layout>
    <h1 class="text-xl font-bold mb-1" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">おかえりなさい</h1>
    <p class="text-sm text-gray-500 mb-6">アカウントにログインしてください</p>

    <!-- セッションステータス -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- メールアドレス -->
        <div>
            <x-input-label for="email" value="メールアドレス" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- パスワード -->
        <div>
            <x-input-label for="password" value="パスワード" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- ログイン状態を保持 -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                       class="w-4 h-4 rounded border-gray-300 focus:ring-gray-900 cursor-pointer"
                       style="accent-color: #1D1D1F;">
                <span class="text-sm text-gray-500">ログイン状態を保持</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-sm text-gray-500 hover:text-gray-900 transition-colors duration-150">
                    パスワードを忘れた場合
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center mt-2">
            ログイン
        </x-primary-button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-6">
        アカウントをお持ちでない方は
        <a href="{{ route('register') }}" class="font-medium hover:opacity-70 transition-opacity duration-150" style="color: #1D1D1F;">
            新規登録
        </a>
    </p>
</x-guest-layout>
