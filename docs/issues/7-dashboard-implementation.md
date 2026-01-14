# Issue #7: ダッシュボード実装

## 背景 / 目的

認証後のダッシュボード画面を実装し、プロフィール完成度と各機能へのナビゲーションを提供する。ユーザーがプロフィール編集・動画管理・プレビュー・公開ページへ簡単にアクセスできるようにする。

- **依存**: #6
- **ラベル**: frontend, backend

---

## スコープ / 作業項目

### 1. DashboardController作成
```bash
php artisan make:controller DashboardController
```
- `index()` メソッド実装:
  - 認証ユーザーのプロフィール情報取得
  - 動画アップロード数（ステータス別）取得
  - プロフィール完成度の計算（名前・経歴・動画の有無）

### 2. ルート定義
- `routes/web.php`
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
```

### 3. Bladeテンプレート作成
- `resources/views/dashboard.blade.php`
- 表示内容:
  - プロフィール完成度（名前・経歴・動画の入力状況）
  - 動画アップロード数（completed/encoding/failedのカウント）
  - 各機能へのリンク:
    - プロフィール編集（/dashboard/profile/edit）
    - 動画管理（/dashboard/videos）
    - プレビュー（/dashboard/preview）
    - 公開ページ（/users/:id）
  - ログアウトボタン
  - 管理者のみ: ユーザー管理リンク（/admin/users）

### 4. プロフィール完成度表示
- 名前入力済み: ✓
- 経歴入力済み: ✓
- サムネイル動画設定済み: ✓
- ポップアップ動画設定済み: ✓

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] DashboardController@indexが実装され、認証ユーザーのプロフィール情報を取得
- [ ] ダッシュボード画面にプロフィール完成度（名前・経歴・動画の有無）が表示される
- [ ] プロフィール編集/動画管理/プレビュー/公開ページへのリンクが配置される
- [ ] ログアウトボタンが機能する（POST /logout）
- [ ] 管理者の場合のみ「ユーザー管理」リンクが表示される（`@if(Auth::user()->role === 'admin')`）
- [ ] 動画アップロード数（ステータス別）が表示される

---

## テスト観点

### 動作確認
- [ ] ログイン後、自動的にダッシュボードへリダイレクト
- [ ] ダッシュボードにプロフィール情報が表示される
- [ ] 「プロフィール編集」リンクをクリックして /dashboard/profile/edit へ遷移（後のIssueで実装）
- [ ] 「動画管理」リンクをクリックして /dashboard/videos へ遷移（後のIssueで実装）
- [ ] 「プレビュー」リンクをクリックして /dashboard/preview へ遷移（後のIssueで実装）
- [ ] 「公開ページを見る」リンクをクリックして /users/:id へ遷移
- [ ] ログアウトボタンをクリックしてログアウト

### 管理者表示
- [ ] Tinkerで管理者ユーザーを作成:
```php
$admin = User::factory()->create(['email' => 'admin@example.com', 'role' => 'admin']);
$admin->profile()->create(['name' => '管理者']);
```
- [ ] 管理者でログインすると「ユーザー管理」リンクが表示される
- [ ] 一般ユーザーでログインすると「ユーザー管理」リンクが表示されない

### プロフィール完成度
- [ ] 名前未入力の場合、「名前を入力してください」と表示される
- [ ] 経歴未入力の場合、「経歴を入力してください」と表示される
- [ ] 動画未設定の場合、「動画をアップロードしてください」と表示される

### 検証方法
1. テストユーザーでログイン
2. ダッシュボードが表示されることを確認
3. 各リンクが正しく機能することを確認

---

## 実装例

### DashboardController.php
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile;

        // 動画数のカウント（ステータス別）
        $videoStats = [
            'total' => $user->videos()->count(),
            'completed' => $user->videos()->where('status', 'completed')->count(),
            'encoding' => $user->videos()->where('status', 'encoding')->count(),
            'failed' => $user->videos()->where('status', 'failed')->count(),
        ];

        // プロフィール完成度
        $completionStatus = [
            'name' => !empty($profile->name),
            'biography' => !empty($profile->biography),
            'thumbnail_video' => !empty($profile->thumbnail_video_id),
            'popup_video' => !empty($profile->popup_video_id),
        ];

        return view('dashboard', [
            'profile' => $profile,
            'videoStats' => $videoStats,
            'completionStatus' => $completionStatus,
        ]);
    }
}
```

### dashboard.blade.php
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ダッシュボード
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- プロフィール完成度 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">プロフィール完成度</h3>
                    <ul class="space-y-2">
                        <li>
                            @if($completionStatus['name'])
                                <span class="text-green-600">✓</span> 名前が設定されています
                            @else
                                <span class="text-red-600">✗</span> 名前を入力してください
                            @endif
                        </li>
                        <li>
                            @if($completionStatus['biography'])
                                <span class="text-green-600">✓</span> 経歴が設定されています
                            @else
                                <span class="text-yellow-600">-</span> 経歴を入力してください（任意）
                            @endif
                        </li>
                        <li>
                            @if($completionStatus['thumbnail_video'])
                                <span class="text-green-600">✓</span> サムネイル動画が設定されています
                            @else
                                <span class="text-yellow-600">-</span> サムネイル動画をアップロードしてください（任意）
                            @endif
                        </li>
                        <li>
                            @if($completionStatus['popup_video'])
                                <span class="text-green-600">✓</span> ポップアップ動画が設定されています
                            @else
                                <span class="text-yellow-600">-</span> ポップアップ動画をアップロードしてください（任意）
                            @endif
                        </li>
                    </ul>
                </div>
            </div>

            {{-- 動画統計 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">動画アップロード状況</h3>
                    <p>合計: {{ $videoStats['total'] }}本</p>
                    <p>使用可能: {{ $videoStats['completed'] }}本</p>
                    @if($videoStats['encoding'] > 0)
                        <p class="text-yellow-600">エンコード中: {{ $videoStats['encoding'] }}本</p>
                    @endif
                    @if($videoStats['failed'] > 0)
                        <p class="text-red-600">エンコード失敗: {{ $videoStats['failed'] }}本</p>
                    @endif
                </div>
            </div>

            {{-- アクションリンク --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ route('profile.edit') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded text-center">
                    プロフィール編集
                </a>
                <a href="{{ route('videos.index') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-3 px-6 rounded text-center">
                    動画管理
                </a>
                <a href="{{ route('preview') }}" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded text-center">
                    プレビュー
                </a>
                <a href="{{ route('users.show', ['id' => Auth::id()]) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded text-center">
                    公開ページを見る
                </a>

                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('admin.users.index') }}" class="bg-red-500 hover:bg-red-700 text-white font-bold py-3 px-6 rounded text-center col-span-2">
                        ユーザー管理（管理者専用）
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 課題確認事項

- **動画統計の表示位置**: ダッシュボードに表示する？別ページ（動画管理）のみ？
- **プロフィール完成度の計算**: パーセンテージ表示は必要か？（例: 75%完成）

---

## 参考資料

- サイトマップ: `docs/04_sitemap.md`
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
