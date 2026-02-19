<x-app-layout>
    @php
        $themeColor = $profile->theme_color ?? '#667eea';
        $hasPopupVideo = $profile && $profile->popupVideo && $profile->popupVideo->status === 'completed';
    @endphp

    <div class="min-h-screen py-8 px-4" style="background-color: #F5F5F7;">
        <div class="max-w-2xl mx-auto space-y-4">

            {{-- プレビューバナー --}}
            <div class="rounded-2xl px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                 style="background-color: #1D1D1F; color: #ffffff;">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-sm font-medium">プレビュー表示中 — 他のユーザーには表示されません</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dashboard.profile.edit') }}"
                       class="text-sm font-medium px-4 py-1.5 rounded-full border border-white/30 hover:border-white/60 transition-colors duration-150 cursor-pointer">
                        編集に戻る
                    </a>
                    <a href="{{ route('users.show', ['id' => Auth::id()]) }}"
                       target="_blank"
                       class="text-sm font-medium px-4 py-1.5 rounded-full transition-colors duration-150 cursor-pointer"
                       style="background-color: #2563EB;">
                        公開ページを見る
                    </a>
                </div>
            </div>

            {{-- プロフィールカード --}}
            <div class="rounded-3xl bg-white p-8 md:p-10 border-2 relative overflow-hidden"
                 style="border-color: {{ $themeColor }};"
                 x-data="{
                     popupOpen: false,
                     playing: false,
                     thumbVisible: true,
                     openPopup() {
                         this.thumbVisible = false;
                         setTimeout(() => {
                             this.popupOpen = true;
                             this.$nextTick(() => this.$refs.popupVideo.play());
                         }, 160);
                     },
                     closePopup() {
                         this.popupOpen = false;
                         this.$refs.popupVideo.pause();
                         this.$refs.popupVideo.load();
                         setTimeout(() => { this.thumbVisible = true; }, 220);
                     }
                 }"
                 @if($hasPopupVideo)
                     x-on:open-video-modal.window="openPopup()"
                     x-on:keydown.escape.window="if(popupOpen) closePopup()"
                 @endif
            >
                {{-- 通常コンテンツ: opacity で表示/非表示（高さを維持するため display:none は使わない） --}}
                <div class="transition-opacity duration-200"
                     :class="{ 'opacity-0 pointer-events-none': !thumbVisible }">

                    {{-- 氏名 --}}
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-6"
                        style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1D1D1F; letter-spacing: -0.02em;">
                        {{ $profile->name ?? 'ユーザー名未設定' }}
                    </h1>

                    {{-- 経歴 --}}
                    <div class="min-h-[30vh] pt-6 border-t border-gray-100">
                        @if($profile && $profile->biography)
                            <p class="text-gray-600 whitespace-pre-wrap leading-relaxed text-base">{{ $profile->biography }}</p>
                        @else
                            <p class="text-gray-300 italic text-sm">経歴が設定されていません</p>
                        @endif
                    </div>

                    {{-- サムネイル動画エリア --}}
                    <div class="pt-6 mt-6 border-t border-gray-100">
                        <div class="flex justify-end">
                            @if($profile && $profile->thumbnailVideo && $profile->thumbnailVideo->status === 'completed')
                                <x-video-thumbnail
                                    :video="$profile->thumbnailVideo"
                                    :popup-video="$profile->popupVideo"
                                    :inline="true"
                                    :theme-color="$themeColor"
                                />
                            @else
                                <x-video-thumbnail-placeholder :inline="true" />
                            @endif
                        </div>
                    </div>
                </div>

                {{-- フルカード動画プレーヤー (カード全体を覆う absolute オーバーレイ) --}}
                @if($hasPopupVideo)
                    <div
                        x-show="popupOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute inset-0 bg-black"
                        style="display: none;"
                    >
                        {{-- 動画本体 --}}
                        <video
                            x-ref="popupVideo"
                            class="w-full h-full object-contain"
                            playsinline
                            preload="none"
                            @play="playing = true"
                            @pause="playing = false"
                            @ended="playing = false"
                        >
                            <source src="{{ $profile->popupVideo->encoded_url }}" type="video/mp4">
                        </video>

                        {{-- クリッカブルオーバーレイ (再生/停止トグル) --}}
                        <button
                            type="button"
                            class="absolute inset-0 flex items-center justify-center cursor-pointer"
                            @click="playing ? $refs.popupVideo.pause() : $refs.popupVideo.play()"
                            :aria-label="playing ? '停止' : '再生'"
                        >
                            <div
                                x-show="!playing"
                                class="rounded-full w-16 h-16 flex items-center justify-center transition-transform duration-200 hover:scale-110"
                                style="background-color: {{ $themeColor }};"
                            >
                                <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                            <div
                                x-show="playing"
                                class="absolute bottom-0 left-0 right-0 py-2 px-4 text-left"
                                style="background: linear-gradient(to top, rgba(0,0,0,0.6), transparent);"
                            >
                                <span class="text-white text-xs opacity-80">クリックで停止</span>
                            </div>
                        </button>

                        {{-- 閉じるボタン (右上、常時表示) --}}
                        <button
                            type="button"
                            class="absolute top-3 right-3 flex items-center justify-center w-9 h-9 rounded-full text-white transition-colors duration-150 cursor-pointer"
                            style="background-color: rgba(0,0,0,0.5);"
                            @mouseenter="$el.style.backgroundColor='rgba(0,0,0,0.75)'"
                            @mouseleave="$el.style.backgroundColor='rgba(0,0,0,0.5)'"
                            @click.stop="closePopup()"
                            aria-label="閉じる"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>

                    </div>
                @endif

            </div>

        </div>
    </div>

</x-app-layout>
