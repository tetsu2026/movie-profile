<x-guest-layout>
    <h1 class="text-xl font-bold mb-1" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">アカウント作成</h1>
    <p class="text-sm text-gray-500 mb-6">無料で始めましょう</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- 名前 -->
        <div>
            <x-input-label for="name" value="名前" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <!-- メールアドレス -->
        <div>
            <x-input-label for="email" value="メールアドレス" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- パスワード -->
        <div>
            <x-input-label for="password" value="パスワード" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- パスワード確認 -->
        <div>
            <x-input-label for="password_confirmation" value="パスワード（確認）" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full justify-center mt-2">
            アカウントを作成
        </x-primary-button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-6">
        すでにアカウントをお持ちの方は
        <a href="{{ route('login') }}" class="font-medium hover:opacity-70 transition-opacity duration-150" style="color: #1D1D1F;">
            ログイン
        </a>
    </p>
</x-guest-layout>
