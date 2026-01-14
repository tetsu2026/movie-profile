# Issue #5: 認証機能実装（Laravel Breeze）

## 背景 / 目的

Laravel Breezeの認証機能をカスタマイズし、ユーザー登録時にプロフィールレコードを自動作成する。エラーメッセージを日本語化し、ユーザー体験を向上させる。

- **依存**: #4
- **ラベル**: backend, frontend

---

## スコープ / 作業項目

### 1. ユーザー登録処理のカスタマイズ
- ファイル: `app/Http/Controllers/Auth/RegisteredUserController.php`
- `store()` メソッドを修正:
  - ユーザー作成後、プロフィールレコードを自動作成
  - トランザクション処理で両方のレコードを作成

### 2. バリデーションメッセージの日本語化
- Breezeのバリデーションエラーメッセージを日本語化
- ファイル:
  - `resources/lang/ja/validation.php`（言語ファイル作成）
  - または `app/Http/Requests/Auth/LoginRequest.php` 等で個別にメッセージ定義

### 3. 認証フロー動作確認
- ユーザー登録（/register）
- ログイン（/login）
- ログアウト（POST /logout）
- 未認証ユーザーの保護ルートアクセス（/dashboard）

### 4. 認証後のリダイレクト先設定
- デフォルトの `/dashboard` へリダイレクト
- ファイル: `app/Providers/RouteServiceProvider.php` または Breeze設定

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] ユーザー登録フォーム（/register）が動作し、バリデーションエラーが日本語で表示される
- [ ] ユーザー登録時にusersとprofilesテーブルに同時レコード作成される（トランザクション）
- [ ] ログインフォーム（/login）が動作し、認証成功時にダッシュボードへリダイレクト
- [ ] ログアウト機能（POST /logout）が動作し、トップページへリダイレクト
- [ ] 未認証ユーザーが/dashboardにアクセスするとログインページへリダイレクト
- [ ] 認証済みユーザーが/loginや/registerにアクセスするとダッシュボードへリダイレクト
- [ ] バリデーションエラーメッセージが日本語で表示される

---

## テスト観点

### ユーザー登録フロー
- [ ] /register にアクセスして登録フォームが表示される
- [ ] メールアドレス・パスワードを入力して登録ボタンをクリック
- [ ] usersテーブルとprofilesテーブルに同時にレコードが作成される
- [ ] 登録成功後、自動ログインされてダッシュボードへリダイレクト
- [ ] バリデーションエラー時（メール形式不正、パスワード8文字未満）に日本語エラーが表示される

### ログインフロー
- [ ] /login にアクセスしてログインフォームが表示される
- [ ] 登録済みのメールアドレス・パスワードを入力してログイン
- [ ] 認証成功後、ダッシュボードへリダイレクト
- [ ] 誤ったパスワードで「メールアドレスまたはパスワードが正しくありません」エラーが表示される

### ログアウトフロー
- [ ] ログイン状態で POST /logout にアクセス
- [ ] セッションが破棄され、トップページへリダイレクト
- [ ] ログアウト後、/dashboard にアクセスするとログインページへリダイレクト

### 未認証保護
- [ ] 未認証状態で /dashboard にアクセス
- [ ] /login へリダイレクトされる

### 検証方法
1. ブラウザで http://localhost/register にアクセス
2. テストユーザーを登録（例: test@example.com / password123）
3. データベースで `SELECT * FROM users;` と `SELECT * FROM profiles;` を確認
4. ログアウト後、ログイン動作を確認

---

## 実装例

### RegisteredUserController.php（store()メソッド）
```php
use Illuminate\Support\Facades\DB;

public function store(Request $request): RedirectResponse
{
    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    DB::transaction(function () use ($request) {
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // デフォルト権限
        ]);

        // プロフィールレコードを自動作成
        $user->profile()->create([
            'name' => $request->name ?? '', // 名前が未入力の場合は空文字
        ]);

        event(new Registered($user));

        Auth::login($user);
    });

    return redirect(route('dashboard', absolute: false));
}
```

### 日本語バリデーションメッセージ
- `resources/lang/ja/validation.php` を作成
- または各FormRequestの `messages()` メソッドで定義

---

## 課題確認事項

- **名前フィールド**: 登録フォームに「名前」フィールドを追加する？（Breezeデフォルトはメール・パスワードのみ）
  - 追加する場合: `name` フィールドをprofiles.nameに保存
  - 追加しない場合: プロフィール編集画面で後から入力
- **メール認証**: Phase 1では不要だが、将来的にメール認証（email_verified_at）を実装する？
- **Remember Me機能**: ログインフォームの「ログイン状態を保持」チェックボックスは表示する？

---

## 参考資料

- データフロー設計書: `docs/05_data_flow.md`（ユーザー登録フロー）
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
