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
        <div class="mb-6">
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 transition-colors duration-150 mb-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                ダッシュボード
            </a>
            <h1 class="text-2xl font-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">ユーザー管理</h1>
            <p class="text-xs font-semibold tracking-widest uppercase text-gray-400 mt-1">Admin</p>
        </div>

        <div class="rounded-2xl bg-white border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid #F5F5F7;">
                            <th class="px-6 py-3.5 text-left text-xs font-semibold tracking-widest uppercase text-gray-400">ID</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold tracking-widest uppercase text-gray-400">メールアドレス</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold tracking-widest uppercase text-gray-400">名前</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold tracking-widest uppercase text-gray-400">権限</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold tracking-widest uppercase text-gray-400">作成日時</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold tracking-widest uppercase text-gray-400">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($users as $user)
                            <tr class="hover:bg-gray-50 transition-colors duration-100">
                                <td class="px-6 py-4 text-sm text-gray-400">{{ $user->id }}</td>
                                <td class="px-6 py-4 text-sm" style="color: #1D1D1F;">{{ $user->email }}</td>
                                <td class="px-6 py-4 text-sm" style="color: #1D1D1F;">{{ $user->profile->name ?? '未設定' }}</td>
                                <td class="px-6 py-4">
                                    @if($user->role === 'admin')
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: #FFF1F2; color: #B91C1C;">管理者</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold" style="background-color: #EFF6FF; color: #1D4ED8;">一般ユーザー</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400 whitespace-nowrap">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.users.edit', $user->id) }}"
                                           class="text-sm font-medium px-3 py-1.5 rounded-lg transition-colors duration-150 cursor-pointer hover:bg-gray-100"
                                           style="color: #1D1D1F;">
                                            編集
                                        </a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="inline"
                                              onsubmit="return confirm('本当に削除しますか？この操作は取り消せません。');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-sm font-medium px-3 py-1.5 rounded-lg transition-colors duration-150 cursor-pointer hover:bg-red-50"
                                                    style="color: #DC2626;">
                                                削除
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ページネーション --}}
            <div class="px-6 py-4 border-t border-gray-50">
                {{ $users->links() }}
            </div>
        </div>

    </div>
</x-app-layout>
