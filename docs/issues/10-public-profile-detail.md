# Issue #10: 公開プロフィールページの詳細実装

## 背景 / 目的

公開プロフィールページに経歴表示機能を追加し、動画エリアのプレースホルダーを配置する。実際の動画表示は後のIssueで実装するが、レイアウトと動画エリアの準備を完了させる。

- **依存**: #7
- **ラベル**: frontend, backend

---

## スコープ / 作業項目

### 1. PublicProfileControllerの更新
- `show()` メソッドを更新:
  - Eager Loadingで動画情報を取得（N+1問題対策）
  - `User::with(['profile', 'profile.thumbnailVideo', 'profile.popupVideo'])->findOrFail($id)`

### 2. Bladeテンプレートの詳細実装
- `resources/views/users/show.blade.php` を更新
- 経歴表示:
  - 改行を保持（`whitespace-pre-wrap`）
  - 未設定の場合は「経歴が設定されていません」と表示
- サムネイル動画エリア:
  - 円形枠、画面右下配置
  - 動画未設定時はプレースホルダー表示
- ポップアップ動画エリア:
  - サムネイルクリック時にモーダル表示する準備（実際の動画再生は後のIssue）
  - 動画未設定時はプレースホルダー表示

### 3. レスポンシブデザイン
- Tailwind CSSでモバイル・タブレット・デスクトップ対応
- サムネイル動画の配置をレスポンシブに調整

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] プロフィールページで経歴が改行を保持して表示される（whitespace-pre-wrap）
- [ ] サムネイル動画エリア（円形、画面右下配置）のプレースホルダーが表示される
- [ ] ポップアップ動画のプレースホルダーが表示される（モーダル未実装でOK）
- [ ] Tailwind CSSでレスポンシブ対応される
- [ ] 動画が設定されていない場合は「動画未設定」と表示される
- [ ] Eager Loadingでプロフィール・動画情報を取得し、N+1問題が発生しない

---

## テスト観点

### 経歴表示
- [ ] Tinkerでテストユーザーの経歴に改行を含むテキストを保存:
```php
$profile = User::find(1)->profile;
$profile->update(['biography' => "これは1行目です。\n\nこれは3行目です。"]);
```
- [ ] /users/1 にアクセスして経歴が改行を保持して表示される
- [ ] 経歴が未設定の場合、「経歴が設定されていません」と表示される

### 動画エリア
- [ ] サムネイル動画エリアが円形で画面右下に配置される
- [ ] 動画未設定時に「動画未設定」プレースホルダーが表示される
- [ ] ポップアップ動画エリアに「動画未設定」プレースホルダーが表示される

### レスポンシブデザイン
- [ ] モバイル表示（375px幅）でサムネイル動画が適切な位置に表示される
- [ ] タブレット・デスクトップ表示で最適なレイアウトになる

### N+1問題の確認
- [ ] Laravel Debugbarをインストール:
```bash
composer require barryvdh/laravel-debugbar --dev
```
- [ ] /users/1 にアクセスしてクエリ数を確認
- [ ] Eager Loading使用により、クエリ数が最小限に抑えられている

### 検証方法
1. Tinkerでテストユーザーに経歴を設定
2. /users/1 にアクセス
3. 経歴が改行付きで表示されることを確認
4. 動画エリアのプレースホルダーが表示されることを確認

---

## 実装例

### PublicProfileController.php（更新）
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    public function show(int $id): View
    {
        // Eager Loadingでプロフィールと動画情報を取得（N+1問題対策）
        $user = User::with([
            'profile',
            'profile.thumbnailVideo',
            'profile.popupVideo'
        ])->findOrFail($id);

        return view('users.show', [
            'user' => $user,
            'profile' => $user->profile,
        ]);
    }
}
```

### users/show.blade.php（更新）
```blade
<x-guest-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto relative">
            {{-- メインコンテンツ --}}
            <h1 class="text-3xl font-bold mb-4">{{ $profile->name }}</h1>

            {{-- 経歴 --}}
            @if($profile->biography)
                <p class="text-gray-700 whitespace-pre-wrap mb-8">{{ $profile->biography }}</p>
            @else
                <p class="text-gray-500 mb-8">経歴が設定されていません</p>
            @endif

            {{-- サムネイル動画（円形、右下配置） --}}
            <div class="fixed bottom-8 right-8 z-10">
                @if($profile->thumbnail_video_id && $profile->thumbnailVideo)
                    {{-- 後のIssueで動画表示を実装 --}}
                    <div class="bg-gray-300 rounded-full w-32 h-32 flex items-center justify-center cursor-pointer hover:bg-gray-400 transition">
                        <span class="text-gray-600">動画</span>
                    </div>
                @else
                    <div class="bg-gray-200 rounded-full w-32 h-32 flex items-center justify-center">
                        <span class="text-gray-500 text-sm">動画未設定</span>
                    </div>
                @endif
            </div>

            {{-- ポップアップ動画エリア（モーダル準備） --}}
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">紹介動画</h2>
                @if($profile->popup_video_id && $profile->popupVideo)
                    {{-- 後のIssueで動画表示を実装 --}}
                    <div class="bg-gray-300 w-full h-64 flex items-center justify-center cursor-pointer hover:bg-gray-400 transition">
                        <span class="text-gray-600">クリックして再生</span>
                    </div>
                @else
                    <div class="bg-gray-200 w-full h-64 flex items-center justify-center">
                        <span class="text-gray-500">動画未設定</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-guest-layout>
```

---

## 課題確認事項

- **サムネイル動画の位置**: 画面右下固定（fixed）でOK？スクロールしても追従する？
- **モーダル実装**: ポップアップ動画のモーダル表示は後のIssueで実装？（Phase 2で検討）
- **SEO対策**: metaタグ（title, description）は必要か？（Phase 2で検討）

---

## 参考資料

- サイトマップ: `docs/04_sitemap.md`
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`

---

## 更新履歴

### 2026-01-23: レイアウト改善

**変更内容:**
1. サムネ動画を `fixed` 配置からボックス内右下配置に変更
2. 自己紹介欄に `min-height` を追加し画面下まで表示
3. 「管理ページへ」リンクをボックス欄外（下部右寄せ）に追加
4. ポップアップ動画をモーダル表示時に自動再生するよう変更

**実装例の更新:**
```blade
<x-guest-layout>
    <div class="min-h-screen bg-gray-50 py-8 px-4">
        <div class="max-w-4xl mx-auto">
            {{-- プロフィールボックス --}}
            <div class="bg-white rounded-lg shadow-md p-8">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    {{ $profile->name ?? 'ユーザー名未設定' }}
                </h1>

                {{-- 経歴表示（min-heightで画面下まで表示） --}}
                <div class="min-h-[40vh] border-t border-gray-200 pt-4">
                    @if($profile->biography)
                        <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $profile->biography }}</p>
                    @else
                        <p class="text-gray-500 italic">経歴が設定されていません</p>
                    @endif
                </div>

                {{-- サムネイル動画エリア（右寄せ、ボックス内配置） --}}
                <div class="pt-6 mt-6">
                    <div class="flex justify-end">
                        <x-video-thumbnail :video="$profile->thumbnailVideo" :popup-video="$profile->popupVideo" :inline="true" />
                    </div>
                </div>
            </div>

            {{-- 管理ページへのリンク（ボックス欄外） --}}
            <div class="mt-4 text-right">
                <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                    管理ページへ
                </a>
            </div>
        </div>
    </div>

    {{-- ポップアップ動画モーダル（自動再生） --}}
    @if($profile->popupVideo && $profile->popupVideo->status === 'completed')
        <x-video-modal :video="$profile->popupVideo" :id="$profile->popupVideo->id" />
    @endif
</x-guest-layout>
```
