<x-public-layout>
    @php
        $themeColor = $profile->theme_color ?? '#667eea';
        $hasPopupVideo = $profile && $profile->popupVideo && $profile->popupVideo->status === 'completed';
    @endphp

    <div class="min-h-screen py-12 px-4" style="background-color: #F5F5F7;">
        <div class="max-w-2xl mx-auto">

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
                    @if($profile && $profile->thumbnailVideo && $profile->thumbnailVideo->status === 'completed')
                        <div class="pt-6 mt-6 border-t border-gray-100">
                            <div class="flex justify-end">
                                <x-video-thumbnail
                                    :video="$profile->thumbnailVideo"
                                    :popup-video="$profile->popupVideo"
                                    :inline="true"
                                    :theme-color="$themeColor"
                                />
                            </div>
                        </div>
                    @endif
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
                            {{-- 一時停止中: テーマ色の再生アイコン --}}
                            <div
                                x-show="!playing"
                                class="rounded-full w-16 h-16 flex items-center justify-center transition-transform duration-200 hover:scale-110"
                                style="background-color: {{ $themeColor }};"
                            >
                                <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>

                            {{-- 再生中: 下部グラデーションヒント --}}
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

            {{-- 管理ページリンク --}}
            <div class="mt-4 text-right">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-400 hover:text-gray-700 transition-colors duration-150 cursor-pointer">
                    管理ページへ
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </a>
            </div>

        </div>
    </div>

</x-public-layout>
