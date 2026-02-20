<section>
    <header class="mb-6">
        <h2 class="text-lg font-semibold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">
            プロフィール情報
        </h2>
        <p class="mt-1 text-sm text-gray-400">
            メールアドレスと表示名を更新できます。
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="名前" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="メールアドレス" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-gray-500">
                        メールアドレスが未確認です。
                        <button form="send-verification"
                                class="underline font-medium hover:opacity-70 transition-opacity duration-150 cursor-pointer"
                                style="color: #1D1D1F;">
                            確認メールを再送信
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1.5 text-sm font-medium" style="color: #166534;">
                            確認メールを送信しました。
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2 border-t border-gray-100">
            <x-primary-button>保存する</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-400"
                >保存しました</p>
            @endif
        </div>
    </form>
</section>
