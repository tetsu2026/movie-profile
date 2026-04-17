# ルーティング設計書（画面/HTTP）

## ルート一覧

### 公開ページ（認証不要）

#### GET / (home)
**Controller**: HomeController@index
**Middleware**: web
**説明**: トップページ表示（サービス紹介、登録・ログインへの導線）
**成功時**: トップページのビューを返却
**失敗時**: -

---

#### GET /users/{id} (users.show)
**Controller**: PublicProfileController@show
**Middleware**: web
**説明**: ユーザーの公開プロフィールページを表示
**成功時**: プロフィールページのビューを返却（ユーザー名、経歴、動画）
**失敗時**: ユーザーが存在しない場合は404エラーページ

---

### 認証ページ（Laravel Breeze標準）

#### GET /register (register)
**Controller**: Auth\RegisteredUserController@create
**Middleware**: guest
**説明**: ユーザー登録フォーム表示
**成功時**: 登録フォームのビューを返却
**失敗時**: 既にログイン済みの場合はダッシュボードへリダイレクト

---

#### POST /register (register)
**Controller**: Auth\RegisteredUserController@store
**Middleware**: guest
**説明**: ユーザー登録処理
**成功時**: ユーザー作成 → プロフィールレコード作成 → ログイン → ダッシュボードへリダイレクト
**失敗時**: バリデーションエラーをセッションに格納して登録フォームへリダイレクト

---

#### GET /login (login)
**Controller**: Auth\AuthenticatedSessionController@create
**Middleware**: guest
**説明**: ログインフォーム表示
**成功時**: ログインフォームのビューを返却
**失敗時**: 既にログイン済みの場合はダッシュボードへリダイレクト

---

#### POST /login (login)
**Controller**: Auth\AuthenticatedSessionController@store
**Middleware**: guest
**説明**: ログイン処理
**成功時**: 認証成功 → セッション作成 → ダッシュボードへリダイレクト
**失敗時**: 認証失敗メッセージをセッションに格納してログインフォームへリダイレクト

---

#### POST /logout (logout)
**Controller**: Auth\AuthenticatedSessionController@destroy
**Middleware**: auth
**説明**: ログアウト処理
**成功時**: セッション破棄 → トップページへリダイレクト
**失敗時**: -

---

### Breezeアカウント設定（認証必須）

#### GET /profile (profile.edit)
**Controller**: ProfileController@edit
**Middleware**: auth
**説明**: Breezeアカウント設定画面（メールアドレス変更、パスワード変更、アカウント削除）
**成功時**: アカウント設定フォームのビューを返却
**失敗時**: 未認証の場合はログインページへリダイレクト

---

#### PATCH /profile (profile.update)
**Controller**: ProfileController@update
**Middleware**: auth
**説明**: アカウント情報（名前・メールアドレス）の更新処理
**成功時**: アカウント情報更新 → 成功メッセージ → アカウント設定ページへリダイレクト
**失敗時**: バリデーションエラーをセッションに格納してアカウント設定ページへリダイレクト

---

#### DELETE /profile (profile.destroy)
**Controller**: ProfileController@destroy
**Middleware**: auth
**説明**: アカウント削除処理
**成功時**: ユーザーアカウント削除 → トップページへリダイレクト
**失敗時**: パスワード確認失敗時はエラーメッセージ表示

---

### パスワードリセット（Laravel Breeze標準）

#### GET /forgot-password (password.request)
**Controller**: Auth\PasswordResetLinkController@create
**Middleware**: guest
**説明**: パスワードリセットリンク入力フォーム表示

---

#### POST /forgot-password (password.email)
**Controller**: Auth\PasswordResetLinkController@store
**Middleware**: guest
**説明**: パスワードリセットメール送信処理

---

#### GET /reset-password/{token} (password.reset)
**Controller**: Auth\NewPasswordController@create
**Middleware**: guest
**説明**: 新パスワード入力フォーム表示

---

#### POST /reset-password (password.store)
**Controller**: Auth\NewPasswordController@store
**Middleware**: guest
**説明**: 新パスワード保存処理

---

### 一般ユーザーページ（認証必須）

#### GET /dashboard (dashboard)
**Controller**: DashboardController@index
**Middleware**: auth, verified
**説明**: ダッシュボード表示（プロフィール状態、各機能へのリンク）
**成功時**: ダッシュボードのビューを返却（プロフィール情報、動画数）
**失敗時**: 未認証の場合はログインページへリダイレクト

---

#### GET /dashboard/profile/edit (dashboard.profile.edit)
**Controller**: Dashboard\ProfileController@edit
**Middleware**: auth
**説明**: プロフィール編集フォーム表示
**成功時**: 編集フォームのビューを返却（現在のプロフィール情報、動画リスト）
**失敗時**: 未認証の場合はログインページへリダイレクト

---

#### PUT /dashboard/profile (dashboard.profile.update)
**Controller**: Dashboard\ProfileController@update
**Middleware**: auth
**説明**: プロフィール更新処理
**成功時**: プロフィール更新 → 成功メッセージ → ダッシュボードへリダイレクト
**失敗時**: バリデーションエラーをセッションに格納して編集フォームへリダイレクト

---

#### GET /dashboard/videos (videos.index)
**Controller**: VideoController@index
**Middleware**: auth
**説明**: 自分の動画一覧表示
**成功時**: 動画一覧のビューを返却（動画リスト、ステータス、削除ボタン）
**失敗時**: 未認証の場合はログインページへリダイレクト

---

#### GET /dashboard/videos/upload (videos.create)
**Controller**: VideoController@create
**Middleware**: auth
**説明**: 動画アップロードフォーム表示
**成功時**: アップロードフォームのビューを返却
**失敗時**: 未認証の場合はログインページへリダイレクト

---

#### POST /dashboard/videos (videos.store)
**Controller**: VideoController@store
**Middleware**: auth
**説明**: 動画アップロード処理
**成功時**:
1. バリデーション通過
2. videosレコード作成（status: uploading）
3. S3に元動画アップロード
4. エンコードジョブ開始（同期処理 Phase 1）
5. 成功メッセージ → 動画一覧へリダイレクト

**失敗時**:
- バリデーションエラー（ファイルサイズ、長さ、形式）をセッションに格納してアップロードフォームへリダイレクト
- S3アップロード失敗時はエラーメッセージを表示
- エンコード失敗時はstatusをfailedに更新してエラー通知

---

#### DELETE /dashboard/videos/{id} (videos.destroy)
**Controller**: VideoController@destroy
**Middleware**: auth
**説明**: 動画削除処理
**成功時**:
1. 動画の所有権チェック
2. プロフィールで使用中でないかチェック
3. S3から動画ファイル削除
4. videosレコード削除
5. 成功メッセージ → 動画一覧へリダイレクト

**失敗時**:
- 所有権がない場合は403エラー
- プロフィールで使用中の場合はエラーメッセージ → 動画一覧へリダイレクト

---

#### GET /dashboard/preview (preview)
**Controller**: PreviewController@show
**Middleware**: auth
**説明**: 自分の公開ページのプレビュー表示
**成功時**: 公開ページと同じレイアウトのビューを返却（下書き状態の注意書き付き）
**失敗時**: 未認証の場合はログインページへリダイレクト

---

### チャットボット（認証必須）

#### POST /chatbot/message (chatbot.message)
**Controller**: ChatbotController@message
**Middleware**: auth, throttle:10,1
**説明**: ユーザーの質問を受け取り、RAG+LLMで生成した応答を返却
**リクエストボディ**: `{"message": "動画のアップロード方法は？"}` (JSON、100文字以内)
**成功時**:
1. 質問を `chat_messages` に保存 (role=user)
2. 直近5件の履歴を取得して文脈構築（フォールバック応答 `context_chunks IS NULL` とその直前のユーザー質問はペアで除外）
3. Titan Embeddings APIで質問をベクトル化（1024次元）
4. Postgres pgvectorでコサイン類似度検索、類似度 >= 0.3 のチャンクを top-3 取得
5. ヒットあり: Bedrock Converse API（`BEDROCK_MODEL_ID` で指定）にシステムプロンプト + チャンク + 履歴 + 質問を送信
6. ヒットなし: 「情報が見つかりませんでした」固定応答を生成（LLM呼び出しなし）
7. 応答を `chat_messages` に保存 (role=assistant、ヒット時は context_chunks に参照IDを記録、ヒットなしは null)
8. JSON `{"message": "応答本文", "context_chunks": [1, 5, 12]}` を返却

**失敗時**:
- 未認証: 401
- レート制限超過: 429 Too Many Requests
- バリデーションエラー(空文字・100文字超): 422
- Bedrockエラー: 503 + 「一時的にエラーが発生しました」

---

#### GET /chatbot/history (chatbot.history)
**Controller**: ChatbotController@history
**Middleware**: auth
**説明**: ログインユーザーの会話履歴を取得（ウィジェット初回展開時に使用）
**クエリパラメータ**: `?limit=20` (最大100)
**成功時**: JSON `[{id, role, content, created_at}, ...]` を時系列昇順で返却
**失敗時**: 未認証の場合は401

---

### 管理者専用ページ

#### GET /admin/users (admin.users.index)
**Controller**: Admin\UserController@index
**Middleware**: auth, admin
**説明**: 全ユーザーの一覧表示
**成功時**: ユーザー一覧のビューを返却（ID, メール, 名前, 権限, 作成日時）
**失敗時**:
- 未認証の場合はログインページへリダイレクト
- 管理者権限がない場合は403エラーページ

---

#### GET /admin/users/{id}/edit (admin.users.edit)
**Controller**: Admin\UserController@edit
**Middleware**: auth, admin
**説明**: 特定ユーザーのプロフィール編集フォーム表示
**成功時**: 編集フォームのビューを返却（プロフィール情報、権限変更ドロップダウン）
**失敗時**:
- 未認証の場合はログインページへリダイレクト
- 管理者権限がない場合は403エラーページ
- ユーザーが存在しない場合は404エラー

---

#### PUT /admin/users/{id} (admin.users.update)
**Controller**: Admin\UserController@update
**Middleware**: auth, admin
**説明**: 特定ユーザーのプロフィール更新処理
**成功時**:
1. プロフィール更新
2. 権限変更（admin / user）
3. 成功メッセージ → ユーザー一覧へリダイレクト

**失敗時**:
- バリデーションエラーをセッションに格納して編集フォームへリダイレクト
- 管理者権限がない場合は403エラー

---

#### DELETE /admin/users/{id} (admin.users.destroy)
**Controller**: Admin\UserController@destroy
**Middleware**: auth, admin
**説明**: ユーザー削除処理（ソフトデリート）
**成功時**:
1. ユーザーのdeleted_atを更新（ソフトデリート）
2. プロフィール・動画はカスケード削除
3. S3の動画ファイルも削除
4. 成功メッセージ → ユーザー一覧へリダイレクト

**失敗時**:
- 管理者権限がない場合は403エラー
- ユーザーが存在しない場合は404エラー

---

## Middleware定義

### web
- Laravelのデフォルトミドルウェアグループ
- セッション管理、CSRF保護、クッキー暗号化などを提供
- 全てのWebルートに適用

---

### guest
- 未認証ユーザーのみアクセス可能
- 既にログインしている場合は `/dashboard` へリダイレクト
- 適用ルート: `/register`, `/login`

---

### auth
- 認証済みユーザーのみアクセス可能
- 未認証の場合は `/login` へリダイレクト
- 適用ルート: `/dashboard/*`, `/admin/*`

---

### admin
- 管理者権限を持つユーザーのみアクセス可能
- 権限チェック: `Auth::user()->role === 'admin'`
- 権限がない場合は403エラーページ表示
- 適用ルート: `/admin/*`

**実装例**:
```php
public function handle($request, Closure $next)
{
    if (Auth::check() && Auth::user()->role === 'admin') {
        return $next($request);
    }
    abort(403, 'この操作を実行する権限がありません');
}
```

---

## レスポンスパターン

### 成功時

#### ビュー返却（GET リクエスト）
```php
return view('pages.profile.edit', [
    'profile' => $profile,
    'videos' => $videos,
]);
```

#### リダイレクト（POST / PUT / DELETE リクエスト）
```php
return redirect()->route('dashboard')
    ->with('success', 'プロフィールを更新しました');
```

---

### 失敗時

#### バリデーションエラー
```php
$request->validate([
    'name' => 'required|max:50',
    'biography' => 'nullable|max:1000',
]);

// エラー時は自動的に元のフォームへリダイレクト
// エラーメッセージは $errors 変数で取得可能
```

#### 権限エラー
```php
abort(403, 'この操作を実行する権限がありません');
```

#### 404エラー
```php
$user = User::findOrFail($id); // 存在しない場合は自動的に404
```

#### カスタムエラー（例: プロフィールで使用中の動画削除）
```php
return redirect()->route('videos.index')
    ->with('error', 'この動画はプロフィールで使用中のため削除できません');
```

---

## ルートグループ構成

### routes/web.php 構成例

```php
<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dashboard\ProfileController as DashboardProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

// ===== 公開ページ（認証不要） =====
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/users/{id}', [PublicProfileController::class, 'show'])->name('users.show');

// ===== ダッシュボード（認証 + メール認証必須） =====
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ===== 一般ユーザーページ（認証必須） =====
Route::middleware('auth')->group(function () {
    // Breezeアカウント設定
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // プロフィール情報編集（Dashboard\ProfileController）
    Route::get('/dashboard/profile/edit', [DashboardProfileController::class, 'edit'])->name('dashboard.profile.edit');
    Route::put('/dashboard/profile', [DashboardProfileController::class, 'update'])->name('dashboard.profile.update');

    // プレビュー
    Route::get('/dashboard/preview', [PreviewController::class, 'show'])->name('preview');

    // 動画管理
    Route::get('/dashboard/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/dashboard/videos/upload', [VideoController::class, 'create'])->name('videos.create');
    Route::post('/dashboard/videos', [VideoController::class, 'store'])->name('videos.store');
    Route::delete('/dashboard/videos/{id}', [VideoController::class, 'destroy'])->name('videos.destroy');

    // チャットボット（レート制限付き）
    Route::prefix('chatbot')->name('chatbot.')->group(function () {
        Route::post('/message', [ChatbotController::class, 'message'])
            ->middleware('throttle:10,1')
            ->name('message');
        Route::get('/history', [ChatbotController::class, 'history'])->name('history');
    });
});

// ===== 管理者専用ページ（管理者のみ） =====
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});

// ===== 認証ページ（Laravel Breeze標準 — パスワードリセット含む） =====
require __DIR__.'/auth.php';
```

---

## 補足事項

### RESTfulな設計
- リソース操作は標準的なHTTPメソッドを使用
  - GET: リソース取得
  - POST: リソース作成
  - PUT: リソース更新
  - DELETE: リソース削除

### 名前付きルート
- 全てのルートに名前を付与（`name('route.name')`）
- ビューやコントローラーで `route('route.name')` でURL生成可能
- URLが変更されてもビュー側の修正が不要

### CSRF保護
- POST / PUT / DELETE リクエストには自動的にCSRF保護が適用
- Bladeテンプレートで `@csrf` ディレクティブを使用

### Phase 2での拡張予定
- 公開/非公開切り替え: `PUT /dashboard/profile/visibility` 追加
- アクセス解析: `GET /dashboard/analytics` 追加
- 独自ドメイン設定: `PUT /dashboard/domain` 追加

**注記**: パスワードリセット機能はLaravel Breeze標準として実装済み（`/forgot-password`, `/reset-password/{token}`）
