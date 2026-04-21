<x-guest-layout>
    <h1 class="text-xl font-bold mb-1" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">確認メールを送信しました</h1>
    <p class="text-sm text-gray-500 mb-6">
        ご登録ありがとうございます。登録したメールアドレスに確認リンクをお送りしました。リンクをクリックして確認を完了してください。
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-xl px-4 py-3 text-sm font-medium" style="background-color: #F0FDF4; color: #166534;">
            確認メールを再送しました。
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="flex justify-center">
        @csrf
        <x-primary-button>
            再送信する
        </x-primary-button>
    </form>
</x-guest-layout>
