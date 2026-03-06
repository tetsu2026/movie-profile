<x-app-layout>
    <div class="max-w-6xl mx-auto px-6 py-8">

        {{-- メッセージ --}}
        @if(session('success'))
            <div class="mb-5 rounded-2xl px-5 py-4 text-sm font-medium flex items-center gap-3"
                 style="background-color: #F0FDF4; color: #166534; border: 1px solid #BBF7D0;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-5 rounded-2xl px-5 py-4 text-sm font-medium flex items-center gap-3"
                 style="background-color: #FFF1F2; color: #991B1B; border: 1px solid #FECDD3;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- ヘッダー --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 transition-colors duration-150 mb-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    ダッシュボード
                </a>
                <h1 class="text-2xl font-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">動画管理</h1>
                <p class="text-sm text-gray-400 mt-0.5">合計 {{ $videos->count() }} 本</p>
            </div>
            <a href="{{ route('videos.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-white transition-all duration-150 hover:opacity-90 cursor-pointer"
               style="background-color: #1D1D1F;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                アップロード
            </a>
        </div>

        {{-- 動画一覧 --}}
        @if($videos->count() > 0)
            @php
                $hasEncoding = $videos->contains(fn ($v) => in_array($v->status, ['uploading', 'encoding']));
            @endphp
            <div class="rounded-2xl bg-white border border-gray-100 overflow-hidden"
                 x-data="{
                    statuses: {
                        @foreach($videos as $video)
                            {{ $video->id }}: { status: '{{ $video->status }}', retry_count: {{ $video->retry_count ?? 0 }}, error_message: '{{ addslashes($video->error_message ?? '') }}' },
                        @endforeach
                    },
                    timer: null,
                    init() {
                        if ({{ $hasEncoding ? 'true' : 'false' }}) {
                            this.startPolling();
                        }
                    },
                    startPolling() {
                        this.timer = setInterval(() => this.fetchStatuses(), 5000);
                    },
                    stopPolling() {
                        if (this.timer) {
                            clearInterval(this.timer);
                            this.timer = null;
                        }
                    },
                    async fetchStatuses() {
                        try {
                            const res = await fetch('{{ route('videos.statuses') }}', {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (res.ok) {
                                const videos = await res.json();
                                let hasEncoding = false;
                                videos.forEach(v => {
                                    this.statuses[v.id] = {
                                        status: v.status,
                                        retry_count: v.retry_count ?? 0,
                                        error_message: v.error_message ?? ''
                                    };
                                    if (v.status === 'uploading' || v.status === 'encoding') {
                                        hasEncoding = true;
                                    }
                                });
                                if (!hasEncoding) {
                                    this.stopPolling();
                                }
                            }
                        } catch (e) {}
                    },
                    destroy() {
                        this.stopPolling();
                    }
                 }">
                {{-- PC版: テーブル表示 --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr style="border-bottom: 1px solid #F5F5F7;">
                                <th class="px-6 py-3.5 text-left text-xs font-semibold tracking-widest uppercase text-gray-400">ファイル名</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold tracking-widest uppercase text-gray-400">ステータス</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold tracking-widest uppercase text-gray-400">作成日時</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold tracking-widest uppercase text-gray-400">設定状況</th>
                                <th class="px-6 py-3.5 text-right text-xs font-semibold tracking-widest uppercase text-gray-400">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($videos as $video)
                                <tr class="hover:bg-gray-50 transition-colors duration-100">
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium" style="color: #1D1D1F;">{{ $video->original_filename }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <template x-if="statuses[{{ $video->id }}]?.status === 'uploading'">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: #EFF6FF; color: #1D4ED8;">
                                                アップロード中
                                            </span>
                                        </template>
                                        <template x-if="statuses[{{ $video->id }}]?.status === 'encoding'">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: #FFFBEB; color: #B45309;">
                                                エンコード中
                                            </span>
                                        </template>
                                        <template x-if="statuses[{{ $video->id }}]?.status === 'completed'">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: #F0FDF4; color: #15803D;">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                </svg>
                                                完了
                                            </span>
                                        </template>
                                        <template x-if="statuses[{{ $video->id }}]?.status === 'failed'">
                                            <div>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: #FFF1F2; color: #B91C1C;">
                                                    エンコード失敗
                                                </span>
                                                <p x-show="statuses[{{ $video->id }}]?.error_message"
                                                   x-text="statuses[{{ $video->id }}]?.error_message?.substring(0, 80)"
                                                   class="text-xs text-gray-400 mt-1.5"></p>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-400 whitespace-nowrap">
                                        {{ $video->created_at->format('Y/m/d H:i') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $isThumbnail = $video->id === $thumbnailVideoId;
                                            $isPopup = $video->id === $popupVideoId;
                                        @endphp
                                        <div class="flex flex-col gap-1">
                                            @if($isThumbnail)
                                                <span class="inline-flex text-xs px-2 py-0.5 rounded-full font-medium" style="background-color: #FFF7ED; color: #C2410C;">サムネイル</span>
                                            @endif
                                            @if($isPopup)
                                                <span class="inline-flex text-xs px-2 py-0.5 rounded-full font-medium" style="background-color: #F0F9FF; color: #0369A1;">ポップアップ</span>
                                            @endif
                                            @if(!$isThumbnail && !$isPopup)
                                                <span class="text-xs text-gray-300">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @php
                                            if ($isThumbnail && $isPopup) {
                                                $confirmMessage = 'この動画はサムネイル・ポップアップ動画に設定されています。\n削除すると両方の設定が解除されます。\n本当に削除しますか？';
                                            } elseif ($isThumbnail) {
                                                $confirmMessage = 'この動画はサムネイル動画に設定されています。\n削除すると設定も解除されます。\n本当に削除しますか？';
                                            } elseif ($isPopup) {
                                                $confirmMessage = 'この動画はポップアップ動画に設定されています。\n削除すると設定も解除されます。\n本当に削除しますか？';
                                            } else {
                                                $confirmMessage = '本当に削除しますか？この操作は取り消せません。';
                                            }
                                            $isUsed = $isThumbnail || $isPopup;
                                        @endphp
                                        <form method="POST" action="{{ route('videos.destroy', $video->id) }}" class="inline"
                                              onsubmit="return confirm('{{ $confirmMessage }}');">
                                            @csrf
                                            @method('DELETE')
                                            @if($isUsed)
                                                <input type="hidden" name="force_delete" value="1">
                                            @endif
                                            <button type="submit"
                                                    class="text-sm font-medium px-3 py-1.5 rounded-lg transition-colors duration-150 cursor-pointer hover:bg-red-50"
                                                    style="color: #DC2626;">
                                                削除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- スマホ版: カード表示 --}}
                <div class="md:hidden divide-y divide-gray-50">
                    @foreach($videos as $video)
                        @php
                            $isThumbnail = $video->id === $thumbnailVideoId;
                            $isPopup = $video->id === $popupVideoId;
                            if ($isThumbnail && $isPopup) {
                                $confirmMessage = 'この動画はサムネイル・ポップアップ動画に設定されています。\n削除すると両方の設定が解除されます。\n本当に削除しますか？';
                            } elseif ($isThumbnail) {
                                $confirmMessage = 'この動画はサムネイル動画に設定されています。\n削除すると設定も解除されます。\n本当に削除しますか？';
                            } elseif ($isPopup) {
                                $confirmMessage = 'この動画はポップアップ動画に設定されています。\n削除すると設定も解除されます。\n本当に削除しますか？';
                            } else {
                                $confirmMessage = '本当に削除しますか？この操作は取り消せません。';
                            }
                            $isUsed = $isThumbnail || $isPopup;
                        @endphp
                        <div class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium truncate" style="color: #1D1D1F;">{{ $video->original_filename }}</p>
                                    <div class="mt-2">
                                        <template x-if="statuses[{{ $video->id }}]?.status === 'uploading'">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: #EFF6FF; color: #1D4ED8;">
                                                アップロード中
                                            </span>
                                        </template>
                                        <template x-if="statuses[{{ $video->id }}]?.status === 'encoding'">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: #FFFBEB; color: #B45309;">
                                                エンコード中
                                            </span>
                                        </template>
                                        <template x-if="statuses[{{ $video->id }}]?.status === 'completed'">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: #F0FDF4; color: #15803D;">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                </svg>
                                                完了
                                            </span>
                                        </template>
                                        <template x-if="statuses[{{ $video->id }}]?.status === 'failed'">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: #FFF1F2; color: #B91C1C;">
                                                エンコード失敗
                                            </span>
                                        </template>
                                    </div>
                                    @if($isThumbnail || $isPopup)
                                        <div class="flex items-center gap-2 mt-1.5">
                                            @if($isThumbnail)
                                                <span class="inline-flex text-xs px-2 py-0.5 rounded-full font-medium" style="background-color: #FFF7ED; color: #C2410C;">サムネイル</span>
                                            @endif
                                            @if($isPopup)
                                                <span class="inline-flex text-xs px-2 py-0.5 rounded-full font-medium" style="background-color: #F0F9FF; color: #0369A1;">ポップアップ</span>
                                            @endif
                                        </div>
                                    @endif
                                    <template x-if="statuses[{{ $video->id }}]?.status === 'failed' && statuses[{{ $video->id }}]?.error_message">
                                        <p x-text="statuses[{{ $video->id }}]?.error_message?.substring(0, 80)"
                                           class="text-xs text-gray-400 mt-1.5"></p>
                                    </template>
                                </div>
                                <form method="POST" action="{{ route('videos.destroy', $video->id) }}" class="flex-shrink-0"
                                      onsubmit="return confirm('{{ $confirmMessage }}');">
                                    @csrf
                                    @method('DELETE')
                                    @if($isUsed)
                                        <input type="hidden" name="force_delete" value="1">
                                    @endif
                                    <button type="submit"
                                            class="text-sm font-medium px-3 py-1.5 rounded-lg transition-colors duration-150 cursor-pointer hover:bg-red-50"
                                            style="color: #DC2626;">
                                        削除
                                    </button>
                                </form>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">{{ $video->created_at->format('Y/m/d H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            {{-- 空の状態 --}}
            <div class="rounded-2xl bg-white border border-gray-100 p-16 text-center">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-5" style="background-color: #F5F5F7;">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M12 18.75H4.5a2.25 2.25 0 01-2.25-2.25V9m12.841 9.091L16.5 19.5"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold mb-2" style="color: #1D1D1F;">まだ動画がありません</h3>
                <p class="text-sm text-gray-400 mb-6">動画をアップロードして、プロフィールに設定しましょう。</p>
                <a href="{{ route('videos.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-semibold text-white transition-all duration-150 hover:opacity-90 cursor-pointer"
                   style="background-color: #1D1D1F;">
                    動画をアップロード
                </a>
            </div>
        @endif

    </div>
</x-app-layout>
