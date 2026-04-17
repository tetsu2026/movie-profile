<div x-data="{
    open: false,
    messages: [],
    input: '',
    loading: false,
    error: null,
    initialized: false,

    async toggle() {
        this.open = !this.open;
        if (this.open && !this.initialized) {
            await this.loadHistory();
            this.initialized = true;
        }
        if (this.open) {
            this.$nextTick(() => this.scrollToBottom());
        }
    },

    async loadHistory() {
        try {
            const res = await fetch('/chatbot/history', {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                this.messages = data.messages || [];
            }
        } catch (e) {
            console.error('履歴取得エラー:', e);
        }
    },

    async send() {
        if (!this.input.trim() || this.loading) return;

        const question = this.input.trim();
        this.input = '';
        this.error = null;
        this.messages.push({ role: 'user', content: question });
        this.$nextTick(() => this.scrollToBottom());

        this.loading = true;
        try {
            const res = await fetch('/chatbot/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ message: question })
            });

            if (res.status === 429) {
                this.error = '送信回数の上限に達しました。しばらくお待ちください。';
                return;
            }

            if (!res.ok) {
                this.error = 'エラーが発生しました。もう一度お試しください。';
                return;
            }

            const data = await res.json();
            this.messages.push({ role: 'assistant', content: data.message });
        } catch (e) {
            this.error = '通信エラーが発生しました。';
        } finally {
            this.loading = false;
            this.$nextTick(() => this.scrollToBottom());
        }
    },

    scrollToBottom() {
        const el = this.$refs.chatLog;
        if (el) el.scrollTop = el.scrollHeight;
    },

    handleKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            this.send();
        }
    }
}" class="fixed bottom-4 right-4 z-50">

    {{-- チャットボタン --}}
    <button
        x-show="!open"
        x-on:click="toggle()"
        class="w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition-colors"
        aria-label="チャットボットを開く"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
    </button>

    {{-- チャットパネル --}}
    <div
        x-show="open"
        x-transition
        class="w-[400px] h-[600px] bg-white rounded-lg shadow-2xl flex flex-col border border-gray-200"
    >
        {{-- ヘッダー --}}
        <div class="flex items-center justify-between px-4 py-3 bg-blue-600 text-white rounded-t-lg">
            <h2 class="text-sm font-semibold">ヘルプチャット</h2>
            <button x-on:click="toggle()" class="text-white hover:text-gray-200" aria-label="閉じる">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- 会話エリア --}}
        <div x-ref="chatLog" role="log" aria-live="polite" class="flex-1 overflow-y-auto p-4 space-y-3">
            {{-- 初期メッセージ --}}
            <div class="flex justify-start">
                <div class="bg-gray-100 text-gray-800 rounded-lg rounded-bl-none px-3 py-2 max-w-[80%] text-sm">
                    操作方法（動画アップロードやプロフィール編集など）についてご質問ください。AIがお答えします。
                </div>
            </div>

            <template x-for="(msg, i) in messages" :key="i">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div
                        :class="msg.role === 'user'
                            ? 'bg-blue-600 text-white rounded-lg rounded-br-none px-3 py-2 max-w-[80%] text-sm whitespace-pre-line'
                            : 'bg-gray-100 text-gray-800 rounded-lg rounded-bl-none px-3 py-2 max-w-[80%] text-sm whitespace-pre-line'"
                        x-text="msg.content"
                    ></div>
                </div>
            </template>

            {{-- ローディング --}}
            <div x-show="loading" class="flex justify-start">
                <div class="bg-gray-100 text-gray-500 rounded-lg px-3 py-2 text-sm">
                    <span class="animate-pulse">回答を生成中...</span>
                </div>
            </div>

            {{-- エラー --}}
            <div x-show="error" class="text-center">
                <p class="text-red-500 text-xs" x-text="error"></p>
            </div>
        </div>

        {{-- 入力エリア --}}
        <div class="border-t border-gray-200 p-3">
            <div class="flex gap-2">
                <textarea
                    x-model="input"
                    x-on:keydown="handleKeydown($event)"
                    rows="1"
                    placeholder="質問を入力..."
                    maxlength="100"
                    class="flex-1 resize-none border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :disabled="loading"
                ></textarea>
                <button
                    x-on:click="send()"
                    :disabled="loading || !input.trim()"
                    class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    送信
                </button>
            </div>
        </div>
    </div>
</div>
