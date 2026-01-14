# Issue #11: FormRequestクラス整備

## 背景 / 目的

バリデーションロジックをFormRequestクラスに分離し、エラーメッセージを日本語化する。コントローラーのコードをシンプルに保ち、バリデーションルールの再利用性を高める。

- **依存**: #8
- **ラベル**: backend

---

## スコープ / 作業項目

### 1. 既存FormRequestの確認
- Laravel Breeze既存のFormRequest:
  - `LoginRequest`（ログイン）
  - Registerは直接コントローラーでバリデーション

### 2. UpdateProfileRequestの作成（既に #8で作成済み）
- バリデーションルールとメッセージが適切に設定されていることを確認

### 3. 日本語バリデーションメッセージ整備
- 各FormRequestの `messages()` メソッドで日本語メッセージを定義
- または `resources/lang/ja/validation.php` を作成して全体で日本語化

### 4. 既存バリデーションの日本語化
- Laravel Breeze標準の認証バリデーション:
  - RegisteredUserController の `store()` メソッド
  - LoginRequest

### 5. 将来のFormRequest準備（後のIssueで実装）
- StoreVideoRequest（動画アップロード用、Issue #16で作成）
- UpdateUserRequest（管理者用、Issue #21で作成）

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] UpdateProfileRequestクラスが作成され、バリデーションルールとメッセージが定義される
- [ ] RegisterRequest（Breeze既存）のエラーメッセージが日本語化される
- [ ] LoginRequest（Breeze既存）のエラーメッセージが日本語化される
- [ ] 全てのバリデーションエラーが設計書通りの日本語メッセージで表示される
- [ ] コントローラーでFormRequestが使用される

---

## テスト観点

### UpdateProfileRequest
- [ ] プロフィール編集で名前を空にして保存 → 「名前は必須です」
- [ ] 名前を51文字以上入力 → 「名前は50文字以内で入力してください」
- [ ] 経歴を1001文字以上入力 → 「経歴は1000文字以内で入力してください」

### ユーザー登録（RegisteredUserController）
- [ ] メールアドレスを空にして登録 → 「メールアドレスは必須です」
- [ ] メール形式不正で登録 → 「有効なメールアドレス形式で入力してください」
- [ ] 既存メールアドレスで登録 → 「このメールアドレスは既に登録されています」
- [ ] パスワードを7文字以下で登録 → 「パスワードは8文字以上で入力してください」
- [ ] パスワード確認が一致しない → 「パスワード確認が一致しません」

### ログイン（LoginRequest）
- [ ] メールアドレスを空にしてログイン → 「メールアドレスは必須です」
- [ ] パスワードを空にしてログイン → 「パスワードは必須です」
- [ ] 誤ったパスワードでログイン → 「メールアドレスまたはパスワードが正しくありません」

### 検証方法
1. 各フォームで意図的にバリデーションエラーを発生させる
2. エラーメッセージが日本語で表示されることを確認

---

## 実装例

### resources/lang/ja/validation.php（新規作成）
```php
<?php

return [
    'required' => ':attributeは必須です',
    'email' => '有効なメールアドレス形式で入力してください',
    'unique' => 'この:attributeは既に登録されています',
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください',
    ],
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください',
    ],
    'confirmed' => ':attributeの確認が一致しません',

    'attributes' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'name' => '名前',
        'biography' => '経歴',
    ],
];
```

### config/app.php（ロケール設定）
```php
'locale' => 'ja',
'fallback_locale' => 'en',
```

### RegisteredUserController.php（日本語メッセージ追加）
```php
public function store(Request $request): RedirectResponse
{
    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ], [
        'name.required' => '名前は必須です',
        'email.required' => 'メールアドレスは必須です',
        'email.email' => '有効なメールアドレス形式で入力してください',
        'email.unique' => 'このメールアドレスは既に登録されています',
        'password.required' => 'パスワードは必須です',
        'password.confirmed' => 'パスワード確認が一致しません',
    ]);

    // ... 以下省略
}
```

### LoginRequest.php（日本語メッセージ追加）
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスは必須です',
            'email.email' => '有効なメールアドレス形式で入力してください',
            'password.required' => 'パスワードは必須です',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    // ... 以下省略
}
```

---

## 課題確認事項

- **言語ファイルの場所**: `resources/lang/ja/` と `lang/ja/` どちらを使用する？（Laravel 11では`lang/`）
- **全体のロケール設定**: `config/app.php` で `'locale' => 'ja'` に変更する？
- **カスタムメッセージの範囲**: 全てのバリデーションを日本語化する？それとも主要なフォームのみ？

---

## 参考資料

- 画面設計書: `docs/07_screen_design.md`（FormRequestクラス一覧）
- Laravel Validation公式: https://laravel.com/docs/11.x/validation
