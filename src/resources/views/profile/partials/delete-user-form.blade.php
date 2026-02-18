<section class="space-y-5">
    <header>
        <h2 class="text-lg font-semibold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">
            アカウント削除
        </h2>
        <p class="mt-1 text-sm text-gray-400">
            削除したアカウントは復元できません。削除前にデータのバックアップを行ってください。
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >アカウントを削除する</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 md:p-8">
            @csrf
            @method('delete')

            <h2 class="text-lg font-semibold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">
                本当に削除しますか？
            </h2>

            <p class="mt-2 text-sm text-gray-400">
                アカウントを削除すると、すべてのデータが完全に削除されます。パスワードを入力して削除を確認してください。
            </p>

            <div class="mt-5">
                <x-input-label for="password" value="パスワード" class="sr-only" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="パスワードを入力"
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1.5" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    キャンセル
                </x-secondary-button>
                <x-danger-button>
                    削除する
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
