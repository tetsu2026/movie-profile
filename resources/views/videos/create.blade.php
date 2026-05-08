<x-app-layout>
    <div class="max-w-xl mx-auto px-6 py-8">

        {{-- ページタイトル --}}
        <div class="mb-6">
            <a href="{{ route('videos.index') }}"
               class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 transition-colors duration-150 mb-4 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                動画管理に戻る
            </a>
            <h1 class="text-2xl font-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F;">動画アップロード</h1>
        </div>

        {{-- エラーメッセージ --}}
        @if(session('error'))
            <div class="mb-5 rounded-2xl px-5 py-4 text-sm font-medium flex items-center gap-3"
                 style="background-color: #FFF1F2; color: #991B1B; border: 1px solid #FECDD3;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        @error('video')
            <div class="mb-5 rounded-2xl px-5 py-4 text-sm font-medium flex items-center gap-3"
                 style="background-color: #FFF1F2; color: #991B1B; border: 1px solid #FECDD3;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                {{ $message }}
            </div>
        @enderror

        {{-- XHR 経由のバリデーションエラーをここに動的表示 --}}
        <div id="ajax-error" class="mb-5 rounded-2xl px-5 py-4 text-sm font-medium flex items-center gap-3 hidden"
             style="background-color: #FFF1F2; color: #991B1B; border: 1px solid #FECDD3;">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span id="ajax-error-message"></span>
        </div>

        {{-- アップロードフォーム --}}
        <div class="rounded-2xl bg-white border border-gray-100 p-6 md:p-8">
            <form method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data" class="space-y-6" id="upload-form">
                @csrf

                {{-- ファイル選択エリア（クリックで選択） --}}
                <div id="drop-zone"
                     class="cursor-pointer rounded-xl border-2 border-dashed border-gray-300 p-8 text-center transition-colors duration-150 hover:border-blue-400"
                     onclick="document.getElementById('video').click()">
                    <input
                        type="file"
                        name="video"
                        id="video"
                        accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv"
                        required
                        class="hidden"
                    >

                    {{-- ファイル未選択時 --}}
                    <div id="placeholder-text">
                        <p class="text-gray-500">クリックして動画を選択</p>
                        <p class="mt-1 text-sm text-gray-400">mp4, mov, avi, wmv / 最大5MB / 1分以内</p>
                    </div>

                    {{-- ファイル選択後 --}}
                    <div id="file-info" class="hidden">
                        <p id="file-name" class="font-medium" style="color: #1D1D1F;"></p>
                        <p id="file-size" class="text-sm text-gray-500"></p>
                    </div>
                </div>

                {{-- プログレスバー（アップロード中に表示） --}}
                <div id="progress-bar" class="hidden rounded-lg bg-gray-200 overflow-hidden">
                    <div id="progress-fill"
                         class="rounded-lg py-1 text-center text-xs text-white transition-all duration-300"
                         style="width: 0%; background-color: #2563EB;">
                        0%
                    </div>
                </div>

                {{-- ボタン --}}
                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <a href="{{ route('videos.index') }}"
                       class="inline-flex items-center justify-center px-6 py-2.5 rounded-full text-sm font-medium border border-gray-200 bg-white hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                       style="color: #1D1D1F;">
                        キャンセル
                    </a>
                    <x-primary-button id="submit-btn">
                        アップロード
                    </x-primary-button>
                </div>
            </form>
        </div>

        {{-- 注意事項 --}}
        <div class="mt-4 rounded-xl px-4 py-3 text-sm" style="background-color: #FFFBEB; color: #92400E;">
            アップロード後、自動でエンコード処理が行われます。完了まで数分かかる場合があります。
        </div>

    </div>

    <script>
        // ファイル選択時の表示更新
        document.getElementById('video').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                document.getElementById('placeholder-text').classList.add('hidden');
                document.getElementById('file-info').classList.remove('hidden');
                document.getElementById('file-name').textContent = file.name;
                document.getElementById('file-size').textContent = (file.size / (1024 * 1024)).toFixed(1) + ' MB';
            } else {
                document.getElementById('placeholder-text').classList.remove('hidden');
                document.getElementById('file-info').classList.add('hidden');
            }
        });

        // フォーム送信時にプログレスバーを表示（XHRで送信）
        document.getElementById('upload-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const formData = new FormData(form);
            const progressBar = document.getElementById('progress-bar');
            const progressFill = document.getElementById('progress-fill');
            const submitBtn = document.getElementById('submit-btn');

            // UIを更新
            progressBar.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.textContent = 'アップロード中...';

            const ajaxError = document.getElementById('ajax-error');
            const ajaxErrorMessage = document.getElementById('ajax-error-message');
            ajaxError.classList.add('hidden');

            const resetUI = () => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'アップロード';
                progressBar.classList.add('hidden');
                progressFill.style.width = '0%';
                progressFill.textContent = '0%';
            };

            const showError = (message) => {
                ajaxErrorMessage.textContent = message;
                ajaxError.classList.remove('hidden');
                ajaxError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                resetUI();
            };

            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);

            // Acceptを application/json にすることで、Laravelはバリデーション失敗時に
            // 302リダイレクトではなく 422 + JSON で返す。
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);

            // プログレス更新
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressFill.style.width = percent + '%';
                    progressFill.textContent = percent + '%';
                }
            });

            // 完了時
            xhr.addEventListener('load', function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    window.location.href = '{{ route("videos.index") }}';
                    return;
                }

                if (xhr.status === 422) {
                    // Laravelのバリデーションエラー
                    try {
                        const res = JSON.parse(xhr.responseText);
                        const messages = res.errors
                            ? Object.values(res.errors).flat()
                            : [res.message || 'バリデーションエラーが発生しました'];
                        showError(messages.join(' / '));
                    } catch (_) {
                        showError('バリデーションエラーが発生しました');
                    }
                    return;
                }

                if (xhr.status === 413) {
                    // サーバ側(php.ini / nginx)の上限超過
                    showError('ファイルサイズが大きすぎます');
                    return;
                }

                showError('アップロードに失敗しました（HTTP ' + xhr.status + '）');
            });

            // エラー時
            xhr.addEventListener('error', function() {
                showError('アップロードに失敗しました。もう一度お試しください。');
            });

            xhr.send(formData);
        });
    </script>
</x-app-layout>
