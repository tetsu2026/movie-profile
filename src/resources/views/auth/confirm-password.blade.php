<x-guest-layout>
    <h1 class="text-xl font-bold mb-1" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">パスワードの確認</h1>
    <p class="text-sm text-gray-500 mb-6">
        セキュリティ保護されたエリアです。続行するにはパスワードを入力してください。
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" value="パスワード" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full justify-center mt-2">
            確認する
        </x-primary-button>
    </form>
</x-guest-layout>
