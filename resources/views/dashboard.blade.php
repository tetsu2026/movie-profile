<x-app-layout>
    <div class="max-w-6xl mx-auto px-6 py-8 space-y-5">

        {{-- 成功メッセージ --}}
        @if(session('success'))
            <div class="rounded-2xl px-5 py-4 text-sm font-medium flex items-center gap-3"
                 style="background-color: #F0FDF4; color: #166534; border: 1px solid #BBF7D0;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- ページタイトル --}}
        <div>
            <h1 class="text-2xl font-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">ダッシュボード</h1>
            <p class="text-sm text-gray-500 mt-1">ようこそ、{{ Auth::user()->name ?? Auth::user()->email }} さん</p>
        </div>

        {{-- ベントーグリッド: アクションカード --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('dashboard.profile.edit') }}"
               class="rounded-2xl p-5 bg-white border border-gray-100 hover:shadow-md transition-all duration-200 cursor-pointer group"
               style="min-height: 100px;">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-4" style="background-color: #EFF6FF;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color: #2563EB;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold" style="color: #1D1D1F;">プロフィール編集</p>
            </a>

            <a href="{{ route('videos.index') }}"
               class="rounded-2xl p-5 bg-white border border-gray-100 hover:shadow-md transition-all duration-200 cursor-pointer group">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-4" style="background-color: #F0FDF4;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color: #16A34A;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M12 18.75H4.5a2.25 2.25 0 01-2.25-2.25V9m12.841 9.091L16.5 19.5m-1.409-1.409c.407-.24.857-.264 1.272-.08l1.524.7a.75.75 0 001.028-.68v-9.5a.75.75 0 00-1.028-.68l-1.524.7a1.125 1.125 0 01-1.272-.08L11.25 7.5"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold" style="color: #1D1D1F;">動画管理</p>
            </a>

            <a href="{{ route('preview') }}"
               class="rounded-2xl p-5 bg-white border border-gray-100 hover:shadow-md transition-all duration-200 cursor-pointer group">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-4" style="background-color: #FAF5FF;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color: #7C3AED;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold" style="color: #1D1D1F;">プレビュー</p>
            </a>

            <a href="{{ route('users.show', ['id' => Auth::id()]) }}"
               class="rounded-2xl p-5 border border-gray-200 hover:shadow-md transition-all duration-200 cursor-pointer group"
               style="background-color: #1D1D1F;">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-4" style="background-color: rgba(255,255,255,0.1);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-white">公開ページを見る</p>
            </a>

            @if(Auth::user()->role === 'admin')
                <a href="{{ route('admin.users.index') }}"
                   class="col-span-2 md:col-span-4 rounded-2xl p-5 border border-red-200 hover:shadow-md transition-all duration-200 cursor-pointer flex items-center gap-4"
                   style="background-color: #FFF1F2;">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background-color: #FEE2E2;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" style="color: #DC2626;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold" style="color: #DC2626;">ユーザー管理（管理者専用）</p>
                </a>
            @endif
        </div>

        {{-- ベントーグリッド: プロフィール完成度 + 動画統計 --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- プロフィール完成度 --}}
            <div class="rounded-2xl p-6 bg-white border border-gray-100">
                <p class="text-xs font-semibold tracking-widest text-gray-400 uppercase mb-4">Profile</p>
                <h2 class="text-base font-bold mb-4" style="color: #1D1D1F;">プロフィール完成度</h2>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3">
                        @if($completionStatus['name'])
                            <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #DCFCE7;">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color: #16A34A;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </span>
                            <span class="text-sm text-gray-700">名前が設定されています</span>
                        @else
                            <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #FEE2E2;">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color: #DC2626;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </span>
                            <span class="text-sm" style="color: #DC2626;">名前を入力してください</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-3">
                        @if($completionStatus['biography'])
                            <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #DCFCE7;">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color: #16A34A;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </span>
                            <span class="text-sm text-gray-700">経歴が設定されています</span>
                        @else
                            <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #FEF9C3;">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color: #CA8A04;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                                </svg>
                            </span>
                            <span class="text-sm text-gray-500">経歴を入力してください（任意）</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-3">
                        @if($completionStatus['thumbnail_video'])
                            <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #DCFCE7;">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color: #16A34A;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                            </span>
                            <span class="text-sm text-gray-700">サムネイル動画が設定されています</span>
                        @else
                            <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: #FEF9C3;">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="color: #CA8A04;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                                </svg>
                            </span>
                            <span class="text-sm text-gray-500">サムネイル動画をアップロードしてください（任意）</span>
                        @endif
                    </li>
                </ul>
            </div>

            {{-- 動画統計（Alpine.jsでポーリング自動更新） --}}
            <div class="rounded-2xl p-6 bg-white border border-gray-100"
                 x-data="{
                    stats: {
                        total: {{ $videoStats['total'] }},
                        completed: {{ $videoStats['completed'] }},
                        encoding: {{ $videoStats['encoding'] }},
                        failed: {{ $videoStats['failed'] }}
                    },
                    timer: null,
                    init() {
                        if (this.stats.encoding > 0) {
                            this.startPolling();
                        }
                    },
                    startPolling() {
                        this.timer = setInterval(() => this.fetchStats(), 5000);
                    },
                    stopPolling() {
                        if (this.timer) {
                            clearInterval(this.timer);
                            this.timer = null;
                        }
                    },
                    async fetchStats() {
                        try {
                            const res = await fetch('{{ route('dashboard.video-stats') }}', {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (res.ok) {
                                this.stats = await res.json();
                                if (this.stats.encoding === 0) {
                                    this.stopPolling();
                                }
                            }
                        } catch (e) {}
                    },
                    destroy() {
                        this.stopPolling();
                    }
                 }">
                <p class="text-xs font-semibold tracking-widest text-gray-400 uppercase mb-4">Videos</p>
                <h2 class="text-base font-bold mb-4" style="color: #1D1D1F;">動画アップロード状況</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl p-4 text-center" style="background-color: #F5F5F7;">
                        <p class="text-3xl font-extrabold" style="color: #1D1D1F; letter-spacing: -0.04em;" x-text="stats.total"></p>
                        <p class="text-xs text-gray-500 mt-1">合計</p>
                    </div>
                    <div class="rounded-xl p-4 text-center" style="background-color: #F0FDF4;">
                        <p class="text-3xl font-extrabold" style="color: #16A34A; letter-spacing: -0.04em;" x-text="stats.completed"></p>
                        <p class="text-xs text-gray-500 mt-1">使用可能</p>
                    </div>
                    <div class="rounded-xl p-4 text-center" style="background-color: #FFFBEB;">
                        <p class="text-3xl font-extrabold" style="color: #D97706; letter-spacing: -0.04em;" x-text="stats.encoding"></p>
                        <p class="text-xs text-gray-500 mt-1">エンコード中</p>
                    </div>
                    <div class="rounded-xl p-4 text-center" style="background-color: #FFF1F2;">
                        <p class="text-3xl font-extrabold" style="color: #DC2626; letter-spacing: -0.04em;" x-text="stats.failed"></p>
                        <p class="text-xs text-gray-500 mt-1">失敗</p>
                    </div>
                </div>

                <template x-if="stats.encoding > 0">
                    <div class="mt-4 rounded-xl px-4 py-3 text-sm" style="background-color: #FFFBEB; color: #92400E;">
                        <strong x-text="stats.encoding + '本'"></strong>の動画がエンコード中です。完了までお待ちください。
                    </div>
                </template>
                <template x-if="stats.failed > 0">
                    <div class="mt-4 rounded-xl px-4 py-3 text-sm" style="background-color: #FFF1F2; color: #991B1B;">
                        <strong x-text="stats.failed + '本'"></strong>の動画のエンコードに失敗しました。
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>
