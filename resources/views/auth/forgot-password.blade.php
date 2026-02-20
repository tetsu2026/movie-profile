<x-guest-layout>
    <h1 class="text-xl font-bold mb-1" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">パスワードをお忘れですか？</h1>
    <p class="text-sm text-gray-500 mb-6">メールアドレスを入力すると、パスワード再設定リンクをお送りします。</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="メールアドレス" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full justify-center mt-2">
            再設定リンクを送信
        </x-primary-button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-6">
        <a href="{{ route('login') }}" class="font-medium hover:opacity-70 transition-opacity duration-150" style="color: #1D1D1F;">
            ログインに戻る
        </a>
    </p>
</x-guest-layout>
