# Issue #19: 管理者ミドルウェア実装

## 背景 / 目的

管理者権限チェックを行うミドルウェアを作成し、管理者専用機能（/admin/*）へのアクセスを制限する。一般ユーザーがアクセスした場合は403エラーを表示する。

- **依存**: #6
- **ラベル**: backend

---

## スコープ / 作業項目

### 1. AdminMiddleware作成
```bash
php artisan make:middleware AdminMiddleware
```
- `handle()` メソッド実装:
  - `Auth::user()->role === 'admin'` でチェック
  - 管理者以外は403エラー

### 2. ミドルウェア登録
- `bootstrap/app.php`（Laravel 11）または `app/Http/Kernel.php`（Laravel 10以前）にミドルウェアを登録
- エイリアス名: `admin`

### 3. ルートにミドルウェア適用
- `routes/web.php` の `/admin/*` ルートにミドルウェア適用
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // 管理者専用ルート
});
```

### 4. 403エラーページカスタマイズ（オプション）
- `resources/views/errors/403.blade.php` 作成
- 「この操作を実行する権限がありません」メッセージ表示

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] AdminMiddlewareが作成される
- [ ] Auth::user()->role === 'admin'でチェックが実施される
- [ ] 管理者以外がアクセスすると403エラーページが表示される
- [ ] bootstrap/app.phpまたはapp/Http/Kernel.phpにミドルウェアが登録される
- [ ] routes/web.phpで/admin/*ルートにミドルウェアが適用される
- [ ] 管理者でログインすると/admin/*にアクセスできる
- [ ] 一般ユーザーでログインすると/admin/*にアクセスできない（403エラー）

---

## テスト観点

### 管理者アクセス（成功）
- [ ] Tinkerで管理者ユーザーを作成
```php
$admin = User::factory()->create(['email' => 'admin@example.com', 'role' => 'admin']);
$admin->profile()->create(['name' => '管理者']);
```
- [ ] 管理者でログイン
- [ ] /admin/users にアクセス（後のIssueで実装）
- [ ] 403エラーが表示されず、正常にアクセスできる

### 一般ユーザーアクセス（失敗）
- [ ] 一般ユーザー（role='user'）でログイン
- [ ] /admin/users にアクセス
- [ ] 403エラーページが表示される
- [ ] 「この操作を実行する権限がありません」メッセージが表示される

### 未認証ユーザーアクセス（失敗）
- [ ] ログアウト状態で /admin/users にアクセス
- [ ] ログインページ（/login）へリダイレクトされる（authミドルウェアが先に動作）

### 検証方法
1. Tinkerで管理者と一般ユーザーを作成
2. それぞれでログインして /admin/* へのアクセスを試行
3. 期待通りのエラーが表示されることを確認

---

## 実装例

### app/Http/Middleware/AdminMiddleware.php
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        abort(403, 'この操作を実行する権限がありません');
    }
}
```

### bootstrap/app.php（Laravel 11の場合）
```php
use App\Http\Middleware\AdminMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

### app/Http/Kernel.php（Laravel 10以前の場合）
```php
protected $middlewareAliases = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    // ... 他のミドルウェア
];
```

### routes/web.php
```php
// 管理者専用ページ（管理者のみ）
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // 後のIssueでユーザー管理ルートを追加
    // Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
});
```

### resources/views/errors/403.blade.php（オプション）
```blade
<x-guest-layout>
    <div class="container mx-auto px-4 py-16 text-center">
        <h1 class="text-4xl font-bold text-red-600 mb-4">403 Forbidden</h1>
        <p class="text-xl text-gray-700 mb-8">この操作を実行する権限がありません</p>
        <a href="{{ route('dashboard') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            ダッシュボードへ戻る
        </a>
    </div>
</x-guest-layout>
```

---

## 課題確認事項

- **複数の権限**: 将来的にadmin以外の権限（moderator, editorなど）を追加する可能性はあるか？
- **権限チェックの場所**: ミドルウェアでチェックする？それともポリシー（Policy）を使用する？
- **エラーページデザイン**: 403エラーページのデザインは統一する？

---

## 参考資料

- 要件定義書: `docs/01_requirements.md`（権限要件）
- サイトマップ: `docs/04_sitemap.md`（管理者専用ページ）
- Laravel Middleware公式: https://laravel.com/docs/11.x/middleware
