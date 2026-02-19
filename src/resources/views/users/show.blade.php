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
                         // 160ms: コンテンツのフェードアウト(opacity transition 200ms)が始まってから動画を表示
                         setTimeout(() => {
                             this.popupOpen = true;
                             this.$nextTick(() => this.$refs.popupVideo.play());
                         }, 160);
                     },
                     closePopup() {
                         this.popupOpen = false;
                         this.$refs.popupVideo.pause();
                         this.$refs.popupVideo.load();
                         // 220ms: 動画のフェードアウト(opacity transition 200ms)が完了してからコンテンツを再表示
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
                    <x-fullcard-video-player
                        :videoSrc="$profile->popupVideo->encoded_url"
                        :themeColor="$themeColor"
                    />
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
