<nav x-data="{ open: false }" class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200/80">
    <div class="max-w-6xl mx-auto px-6">
        <div class="flex justify-between items-center h-14">
            <!-- ロゴ -->
            <a href="{{ route('dashboard') }}"
               class="text-base font-bold tracking-tight"
               style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">
                動画プロフィール
            </a>

            <!-- PC: ユーザーメニュー -->
            <div class="hidden sm:flex items-center gap-2">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium border border-gray-200 bg-white hover:bg-gray-50 transition-colors duration-150 cursor-pointer focus:outline-none"
                                style="color: #1D1D1F;">
                            <span>{{ Auth::user()->name ?? Auth::user()->email }}</span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            アカウント設定
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                ログアウト
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- モバイル: ハンバーガー -->
            <div class="flex items-center sm:hidden">
                <button @click="open = !open"
                        class="p-2 rounded-xl text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors duration-150 focus:outline-none cursor-pointer">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- モバイルメニュー -->
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden border-t border-gray-100">
        <div class="px-6 py-4">
            <p class="text-sm font-semibold" style="color: #1D1D1F;">{{ Auth::user()->name ?? Auth::user()->email }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ Auth::user()->email }}</p>
        </div>
        <div class="pb-4 px-3 space-y-1">
            <x-responsive-nav-link :href="route('profile.edit')">
                アカウント設定
            </x-responsive-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                    ログアウト
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
