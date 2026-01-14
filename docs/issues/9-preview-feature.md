# Issue #9: プレビュー機能実装

## 背景 / 目的

自分の公開ページをプレビュー表示する機能を実装し、公開前に表示内容を確認できるようにする。公開プロフィールページと同じレイアウトで表示し、編集画面へ戻るボタンを配置する。

- **依存**: #8
- **ラベル**: frontend, backend

---

## スコープ / 作業項目

### 1. PreviewController作成
```bash
php artisan make:controller PreviewController
```
- `show()` メソッド: 認証ユーザーのプロフィールをプレビュー表示

### 2. ルート定義
- `routes/web.php`
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/preview', [PreviewController::class, 'show'])->name('preview');
});
```

### 3. Bladeテンプレート作成
- `resources/views/preview.blade.php`
- 公開プロフィールページ（`users/show.blade.php`）と同じレイアウト
- 追加要素:
  - 「これは下書き状態のプレビューです」という注意書き（目立つ背景色で表示）
  - 「編集ページへ戻る」ボタン
  - 「ダッシュボードへ戻る」ボタン

### 4. 動画プレースホルダー表示
- 動画が未設定の場合は「動画未設定」プレースホルダーを表示
- 動画が設定されている場合は後のIssueで実際の動画表示を実装

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] PreviewController@showが実装され、認証ユーザーのプロフィールを取得
- [ ] プレビュー画面が公開プロフィールページと同じレイアウトで表示される
- [ ] 「これは下書き状態のプレビューです」という注意書きが表示される
- [ ] 編集ページへ戻るボタンが機能する
- [ ] 動画が未設定の場合はプレースホルダーが表示される
- [ ] Tailwind CSSでレスポンシブ対応される

---

## テスト観点

### プレビュー表示
- [ ] /dashboard/preview にアクセスしてプレビュー画面が表示される
- [ ] ユーザー名が表示される
- [ ] 経歴が表示される（改行が保持される）
- [ ] 「これは下書き状態のプレビューです」という注意書きが目立つ背景色（黄色など）で表示される
- [ ] サムネイル動画エリアにプレースホルダーが表示される（動画未設定の場合）
- [ ] ポップアップ動画エリアにプレースホルダーが表示される（動画未設定の場合）

### ナビゲーション
- [ ] 「編集ページへ戻る」ボタンをクリックして /dashboard/profile/edit へ遷移
- [ ] 「ダッシュボードへ戻る」ボタンをクリックして /dashboard へ遷移

### レスポンシブデザイン
- [ ] モバイル表示（375px幅）でレイアウトが崩れない
- [ ] タブレット・デスクトップ表示で最適なレイアウトになる

### 検証方法
1. テストユーザーでログイン
2. ダッシュボードから「プレビュー」をクリック
3. プレビュー画面が表示されることを確認
4. 編集ページ・ダッシュボードへのボタンが機能することを確認

---

## 実装例

### PreviewController.php
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PreviewController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile;

        return view('preview', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }
}
```

### preview.blade.php
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            プレビュー
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- 注意書き --}}
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
                <p class="font-bold">下書き状態のプレビュー</p>
                <p>これは公開ページのプレビューです。他のユーザーには表示されません。</p>
            </div>

            {{-- ナビゲーションボタン --}}
            <div class="flex gap-4 mb-6">
                <a href="{{ route('profile.edit') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    編集ページへ戻る
                </a>
                <a href="{{ route('dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    ダッシュボードへ戻る
                </a>
            </div>

            {{-- 公開プロフィールページと同じレイアウト --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-4">{{ $profile->name }}</h1>

                    @if($profile->biography)
                        <p class="text-gray-700 whitespace-pre-wrap mb-8">{{ $profile->biography }}</p>
                    @else
                        <p class="text-gray-500 mb-8">経歴が設定されていません</p>
                    @endif

                    {{-- サムネイル動画エリア --}}
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">サムネイル動画</h2>
                        @if($profile->thumbnail_video_id)
                            {{-- 後のIssueで動画表示を実装 --}}
                            <div class="bg-gray-200 rounded-full w-32 h-32 flex items-center justify-center">
                                <span class="text-gray-500">動画</span>
                            </div>
                        @else
                            <div class="bg-gray-200 rounded-full w-32 h-32 flex items-center justify-center">
                                <span class="text-gray-500">動画未設定</span>
                            </div>
                        @endif
                    </div>

                    {{-- ポップアップ動画エリア --}}
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-4">ポップアップ動画</h2>
                        @if($profile->popup_video_id)
                            {{-- 後のIssueで動画表示を実装 --}}
                            <div class="bg-gray-200 w-full h-64 flex items-center justify-center">
                                <span class="text-gray-500">動画（クリックで再生）</span>
                            </div>
                        @else
                            <div class="bg-gray-200 w-full h-64 flex items-center justify-center">
                                <span class="text-gray-500">動画未設定</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 課題確認事項

- **プレビューURLの共有**: プレビューURLを他のユーザーと共有する機能は必要か？（Phase 2で検討）
- **リアルタイムプレビュー**: プロフィール編集画面でリアルタイムプレビューを表示する？（Phase 2で検討）

---

## 参考資料

- サイトマップ: `docs/04_sitemap.md`
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
