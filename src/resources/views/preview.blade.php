<x-app-layout>
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
            <div class="rounded-3xl bg-white border border-gray-100 p-8 md:p-10">

                {{-- アクセントライン --}}
                <div class="w-10 h-1 rounded-full mb-6" style="background-color: #1D1D1F;"></div>

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
                            />
                        @else
                            <x-video-thumbnail-placeholder :inline="true" />
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ポップアップ動画モーダル --}}
    @if($profile && $profile->popupVideo && $profile->popupVideo->status === 'completed')
        <x-video-modal
            :video="$profile->popupVideo"
            :id="$profile->popupVideo->id"
        />
    @endif
</x-app-layout>
