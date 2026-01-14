# 画面設計書（入力/バリデーション/遷移）

## 画面一覧

| 画面名 | URL | 入力項目数 | 遷移先 |
|--------|-----|------------|--------|
| トップページ | GET / | なし | ログイン/登録 |
| ユーザー登録 | POST /register | 3項目 | ダッシュボード |
| ログイン | POST /login | 3項目 | ダッシュボード |
| ダッシュボード | GET /dashboard | なし | 各機能へ |
| プロフィール編集 | PUT /dashboard/profile | 4項目 | ダッシュボード |
| 動画一覧 | GET /dashboard/videos | なし | 動画アップロード |
| 動画アップロード | POST /dashboard/videos | 1項目 | 動画一覧 |
| プレビュー | GET /dashboard/preview | なし | プロフィール編集 |
| 公開プロフィール | GET /users/{id} | なし | - |
| 管理者: ユーザー一覧 | GET /admin/users | なし | ユーザー編集 |
| 管理者: ユーザー編集 | PUT /admin/users/{id} | 5項目 | ユーザー一覧 |

---

## 画面詳細

### ユーザー登録フォーム (POST /register)

#### 入力項目

| 項目名 | フィールド名 | 型 | 必須 | 制約 |
|--------|-------------|-----|------|------|
| メールアドレス | email | text | ○ | メール形式、一意性 |
| パスワード | password | password | ○ | 8文字以上 |
| パスワード確認 | password_confirmation | password | ○ | passwordと一致 |

#### バリデーションルール
```php
[
    'email' => 'required|email|max:255|unique:users,email',
    'password' => 'required|string|min:8|confirmed',
]
```

#### エラーメッセージ
- **email required**: メールアドレスは必須です
- **email email**: 有効なメールアドレス形式で入力してください
- **email unique**: このメールアドレスは既に登録されています
- **password required**: パスワードは必須です
- **password min**: パスワードは8文字以上で入力してください
- **password confirmed**: パスワード確認が一致しません

#### 成功時/失敗時の挙動
- **成功時**:
  1. usersテーブルにレコード作成（role: user）
  2. profilesテーブルに空レコード作成
  3. 自動ログイン
  4. ダッシュボード (`/dashboard`) へリダイレクト
- **失敗時**: バリデーションエラーを表示して登録フォームへ戻る

---

### ログインフォーム (POST /login)

#### 入力項目

| 項目名 | フィールド名 | 型 | 必須 | 制約 |
|--------|-------------|-----|------|------|
| メールアドレス | email | text | ○ | メール形式 |
| パスワード | password | password | ○ | - |
| ログイン状態を保持 | remember | checkbox | × | - |

#### バリデーションルール
```php
[
    'email' => 'required|email',
    'password' => 'required|string',
]
```

#### エラーメッセージ
- **email required**: メールアドレスは必須です
- **email email**: 有効なメールアドレス形式で入力してください
- **password required**: パスワードは必須です
- **認証失敗**: メールアドレスまたはパスワードが正しくありません

#### 成功時/失敗時の挙動
- **成功時**:
  1. セッション作成
  2. remember がチェックされていればトークン保存
  3. ダッシュボード (`/dashboard`) へリダイレクト
- **失敗時**: 認証失敗メッセージを表示してログインフォームへ戻る

---

### プロフィール編集フォーム (PUT /dashboard/profile)

#### 入力項目

| 項目名 | フィールド名 | 型 | 必須 | 制約 |
|--------|-------------|-----|------|------|
| 名前 | name | text | ○ | 50文字以内 |
| 経歴 | biography | textarea | × | 1000文字以内 |
| サムネイル用動画 | thumbnail_video_id | select | × | 自分の動画IDのみ選択可 |
| ポップアップ用動画 | popup_video_id | select | × | 自分の動画IDのみ選択可 |

#### バリデーションルール
```php
[
    'name' => 'required|string|max:50',
    'biography' => 'nullable|string|max:1000',
    'thumbnail_video_id' => 'nullable|exists:videos,id',
    'popup_video_id' => 'nullable|exists:videos,id',
]
```

**追加チェック（コントローラー側）**:
- 選択された動画が自分の動画かどうかを確認

#### エラーメッセージ
- **name required**: 名前は必須です
- **name max**: 名前は50文字以内で入力してください
- **biography max**: 経歴は1000文字以内で入力してください
- **thumbnail_video_id exists**: 選択された動画が見つかりません
- **popup_video_id exists**: 選択された動画が見つかりません
- **動画所有権エラー**: 他のユーザーの動画は選択できません

#### 成功時/失敗時の挙動
- **成功時**:
  1. profilesテーブルを更新
  2. 成功メッセージ「プロフィールを更新しました」
  3. ダッシュボード (`/dashboard`) へリダイレクト
- **失敗時**: バリデーションエラーを表示してプロフィール編集フォームへ戻る

---

### 動画アップロードフォーム (POST /dashboard/videos)

#### 入力項目

| 項目名 | フィールド名 | 型 | 必須 | 制約 |
|--------|-------------|-----|------|------|
| 動画ファイル | video | file | ○ | mp4/mov/avi/wmv、100MB以内、1分以内 |

#### バリデーションルール
```php
[
    'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:102400', // 100MB = 102400KB
]
```

**追加チェック（コントローラー側）**:
- 動画の長さが60秒以内かをFFmpegまたはgetID3ライブラリで確認

#### エラーメッセージ
- **video required**: 動画ファイルを選択してください
- **video file**: ファイルをアップロードしてください
- **video mimes**: 対応している動画形式はmp4, mov, avi, wmvです
- **video max**: ファイルサイズは100MB以内にしてください
- **動画の長さエラー**: 動画の長さは1分以内にしてください

#### 成功時/失敗時の挙動
- **成功時**:
  1. videosテーブルにレコード作成（status: uploading）
  2. S3に元動画アップロード
  3. エンコードジョブ開始（同期処理）
  4. エンコード完了後、status: completed
  5. 成功メッセージ「動画のアップロードが完了しました」
  6. 動画一覧 (`/dashboard/videos`) へリダイレクト
- **失敗時**:
  - バリデーションエラー: エラーメッセージを表示してアップロードフォームへ戻る
  - S3アップロード失敗: エラーメッセージ「動画のアップロードに失敗しました」
  - エンコード失敗: 3回リトライ後、status: failed、エラー通知

---

### 管理者: ユーザー編集フォーム (PUT /admin/users/{id})

#### 入力項目

| 項目名 | フィールド名 | 型 | 必須 | 制約 |
|--------|-------------|-----|------|------|
| 名前 | name | text | ○ | 50文字以内 |
| 経歴 | biography | textarea | × | 1000文字以内 |
| サムネイル用動画 | thumbnail_video_id | select | × | 対象ユーザーの動画のみ |
| ポップアップ用動画 | popup_video_id | select | × | 対象ユーザーの動画のみ |
| 権限 | role | select | ○ | admin / user |

#### バリデーションルール
```php
[
    'name' => 'required|string|max:50',
    'biography' => 'nullable|string|max:1000',
    'thumbnail_video_id' => 'nullable|exists:videos,id',
    'popup_video_id' => 'nullable|exists:videos,id',
    'role' => 'required|in:admin,user',
]
```

#### エラーメッセージ
- **name required**: 名前は必須です
- **name max**: 名前は50文字以内で入力してください
- **biography max**: 経歴は1000文字以内で入力してください
- **role required**: 権限を選択してください
- **role in**: 権限はadminまたはuserから選択してください

#### 成功時/失敗時の挙動
- **成功時**:
  1. profilesテーブルを更新
  2. usersテーブルのroleを更新
  3. 成功メッセージ「ユーザー情報を更新しました」
  4. ユーザー一覧 (`/admin/users`) へリダイレクト
- **失敗時**: バリデーションエラーを表示してユーザー編集フォームへ戻る

---

## 公開プロフィールページ (GET /users/{id})

### 表示項目

| 項目名 | データソース | 表示条件 |
|--------|------------|---------|
| ユーザー名 | profiles.name | 常時表示 |
| 経歴 | profiles.biography | 設定されている場合のみ |
| サムネイル動画 | videos (thumbnail_video_id) | 設定されている場合のみ |
| ポップアップ動画 | videos (popup_video_id) | サムネイルクリック時のモーダル |

### 動画再生仕様
- **サムネイル動画**:
  - 円形枠で表示
  - 画面右下に配置
  - 自動ループ再生
  - ミュート（音声なし）
  - プレースホルダー: 動画未設定時は灰色の円形アイコン表示
- **ポップアップ動画**:
  - サムネイルクリック時にモーダルで表示
  - 音声あり
  - 再生/一時停止コントロール表示
  - モーダル外クリックで閉じる

### エラーケース
- **ユーザーが存在しない**: 404エラーページ表示

---

## ダッシュボード (GET /dashboard)

### 表示項目

| 項目名 | データソース | 説明 |
|--------|------------|------|
| プロフィール完成度 | profiles | 名前・経歴・動画の入力状況 |
| 動画アップロード数 | videos (count) | ステータス別カウント |
| 公開ページへのリンク | users.id | `/users/{id}` |
| プロフィール編集リンク | - | `/dashboard/profile/edit` |
| 動画管理リンク | - | `/dashboard/videos` |
| プレビューリンク | - | `/dashboard/preview` |
| ログアウトボタン | - | POST `/logout` |

### 管理者のみ表示
- ユーザー管理リンク: `/admin/users`

---

## FormRequestクラス

| クラス名 | 対応ルート | 主要バリデーション |
|----------|-----------|-------------------|
| RegisterRequest | POST /register | email (unique), password (min:8, confirmed) |
| LoginRequest | POST /login | email, password |
| UpdateProfileRequest | PUT /dashboard/profile | name (required, max:50), biography (max:1000) |
| StoreVideoRequest | POST /dashboard/videos | video (required, file, mimes, max:102400) |
| UpdateUserRequest | PUT /admin/users/{id} | name, biography, role (in:admin,user) |

### FormRequestクラス実装例

#### UpdateProfileRequest.php
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true; // authミドルウェアで認証済み
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:50',
            'biography' => 'nullable|string|max:1000',
            'thumbnail_video_id' => 'nullable|exists:videos,id',
            'popup_video_id' => 'nullable|exists:videos,id',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '名前は必須です',
            'name.max' => '名前は50文字以内で入力してください',
            'biography.max' => '経歴は1000文字以内で入力してください',
            'thumbnail_video_id.exists' => '選択された動画が見つかりません',
            'popup_video_id.exists' => '選択された動画が見つかりません',
        ];
    }
}
```

#### StoreVideoRequest.php
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:102400', // 100MB
        ];
    }

    public function messages()
    {
        return [
            'video.required' => '動画ファイルを選択してください',
            'video.file' => 'ファイルをアップロードしてください',
            'video.mimes' => '対応している動画形式はmp4, mov, avi, wmvです',
            'video.max' => 'ファイルサイズは100MB以内にしてください',
        ];
    }
}
```

---

## 補足事項

### エラー表示の統一
全てのフォームで以下のBladeディレクティブを使用してエラー表示を統一:
```blade
@error('field_name')
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
@enderror
```

### 成功メッセージの表示
セッションフラッシュメッセージを使用:
```blade
@if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {{ session('error') }}
    </div>
@endif
```

### CSRF保護
全てのPOST/PUT/DELETEフォームに `@csrf` ディレクティブを追加:
```blade
<form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('PUT')
    <!-- フォーム内容 -->
</form>
```

### レスポンシブデザイン
- Tailwind CSSでモバイルファーストなレスポンシブデザイン
- フォーム要素は画面幅に応じて最適化

### アクセシビリティ
- 全ての入力フィールドに `<label>` を設定
- エラーメッセージは `aria-describedby` で関連付け
- フォーカス状態を視覚的に明示
