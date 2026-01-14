# Issue #6: トップページと公開プロフィールページの骨組み作成

## 背景 / 目的

トップページ（/）と公開プロフィールページ（/users/:id）のBladeテンプレートを作成し、Walking Skeletonのエンドツーエンド動作を完成させる。動画機能は未実装でOK、レイアウトとルーティングの骨組みを整える。

- **依存**: #5
- **ラベル**: frontend, backend

---

## スコープ / 作業項目

### 1. トップページ実装（/）
- コントローラー作成:
```bash
php artisan make:controller HomeController
```
- ルート定義: `routes/web.php`
```php
Route::get('/', [HomeController::class, 'index'])->name('home');
```
- Bladeテンプレート: `resources/views/home.blade.php`
  - サービス概要説明
  - 新規登録ボタン（→ /register）
  - ログインボタン（→ /login）
  - サンプル公開ページへのリンク（オプション）

### 2. 公開プロフィールページ実装（/users/:id）
- コントローラー作成:
```bash
php artisan make:controller PublicProfileController
```
- ルート定義: `routes/web.php`
```php
Route::get('/users/{id}', [PublicProfileController::class, 'show'])->name('users.show');
```
- Bladeテンプレート: `resources/views/users/show.blade.php`
  - ユーザー名表示（`$profile->name`）
  - 経歴表示（`$profile->biography`）
  - 動画エリアのプレースホルダー（「動画未設定」と表示）
  - 存在しないユーザーの場合は404エラーページ

### 3. レイアウトファイル整備
- `resources/views/layouts/guest.blade.php`（未認証ユーザー用）
- `resources/views/layouts/app.blade.php`（認証ユーザー用）
- Tailwind CSSでレスポンシブデザイン適用

### 4. 404エラーページカスタマイズ（オプション）
- `resources/views/errors/404.blade.php`

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] トップページ（/）が表示され、登録・ログインへのリンクが機能する
- [ ] PublicProfileController@showが実装され、/users/:idで404エラーまたはプロフィールページが表示される
- [ ] プロフィールページでユーザー名・経歴が表示される（動画は未実装でOK）
- [ ] 存在しないユーザーIDにアクセスすると404エラーページが表示される
- [ ] Tailwind CSSでレスポンシブデザインが適用される
- [ ] ヘッダー・フッターが共通レイアウトとして実装される

---

## テスト観点

### トップページ
- [ ] http://localhost/ にアクセスしてトップページが表示される
- [ ] 「新規登録」ボタンをクリックして /register へ遷移
- [ ] 「ログイン」ボタンをクリックして /login へ遷移
- [ ] Tailwind CSSのスタイルが適用されている

### 公開プロフィールページ
- [ ] Tinkerでテストユーザー・プロフィールを作成:
```php
$user = User::factory()->create();
$user->profile()->create(['name' => 'テストユーザー', 'biography' => 'これはテストの経歴です。']);
```
- [ ] http://localhost/users/1 にアクセスしてプロフィールページが表示される
- [ ] ユーザー名「テストユーザー」が表示される
- [ ] 経歴「これはテストの経歴です。」が表示される
- [ ] 動画エリアに「動画未設定」プレースホルダーが表示される
- [ ] http://localhost/users/999（存在しないID）にアクセスして404エラーページが表示される

### レスポンシブデザイン
- [ ] モバイル表示（375px幅）でレイアウトが崩れない
- [ ] タブレット表示（768px幅）でレイアウトが適切に表示される
- [ ] デスクトップ表示（1024px幅）で最適なレイアウトになる

### 検証方法
1. ブラウザで http://localhost/ にアクセス
2. Tinkerでテストデータ作成
3. http://localhost/users/1 にアクセス
4. DevToolsでレスポンシブ表示を確認

---

## 実装例

### HomeController.php
```php
<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home');
    }
}
```

### PublicProfileController.php
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    public function show(int $id): View
    {
        $user = User::with('profile')->findOrFail($id);

        return view('users.show', [
            'user' => $user,
            'profile' => $user->profile,
        ]);
    }
}
```

### home.blade.php
```blade
<x-guest-layout>
    <div class="container mx-auto px-4 py-16">
        <h1 class="text-4xl font-bold text-center mb-8">動画付き自己紹介プラットフォーム</h1>
        <p class="text-center text-gray-600 mb-12">動画を使った魅力的な自己紹介ページを簡単に作成できます</p>

        <div class="flex justify-center gap-4">
            <a href="{{ route('register') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded">
                新規登録
            </a>
            <a href="{{ route('login') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded">
                ログイン
            </a>
        </div>
    </div>
</x-guest-layout>
```

### users/show.blade.php
```blade
<x-guest-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-4">{{ $profile->name }}</h1>

            @if($profile->biography)
                <p class="text-gray-700 whitespace-pre-wrap mb-8">{{ $profile->biography }}</p>
            @else
                <p class="text-gray-500 mb-8">経歴が設定されていません</p>
            @endif

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
        </div>
    </div>
</x-guest-layout>
```

---

## 課題確認事項

- **サンプルユーザー**: トップページに表示するサンプル公開ページのリンクは必要か？
- **OGP設定**: Phase 1ではOGPタグ（SNSシェア対応）は不要？
- **パンくずリスト**: 公開プロフィールページにパンくずリストは必要か？

---

## 参考資料

- サイトマップ: `docs/04_sitemap.md`
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
