# 画面設計書（入力/バリデーション/遷移）

## 画面一覧

| 画面名 | URL | 入力項目数 | 遷移先 |
|--------|-----|------------|--------|
| トップページ | GET / | なし | ログイン/登録 |
| ユーザー登録 | POST /register | 4項目 | ダッシュボード |
| ログイン | POST /login | 3項目 | ダッシュボード |
| ダッシュボード | GET /dashboard | なし | 各機能へ |
| プロフィール編集 | PUT /dashboard/profile | 5項目 | ダッシュボード |
| 動画一覧 | GET /dashboard/videos | なし | 動画アップロード |
| 動画アップロード | POST /dashboard/videos | 1項目 | 動画一覧 |
| プレビュー | GET /dashboard/preview | なし | プロフィール編集 |
| 公開プロフィール | GET /users/{id} | なし | - |
| 管理者: ユーザー一覧 | GET /admin/users | なし | ユーザー編集 |
| 管理者: ユーザー編集 | PUT /admin/users/{id} | 5項目 | ユーザー一覧 |
| チャットボットウィジェット | POST /chatbot/message (非ページ・全認証画面共通) | 1項目 | 同ウィジェット内 |

---

## 画面詳細

### ユーザー登録フォーム (POST /register)

#### 入力項目

| 項目名 | フィールド名 | 型 | 必須 | 制約 |
|--------|-------------|-----|------|------|
| 名前 | name | text | ○ | 255文字以内 |
| メールアドレス | email | text | ○ | メール形式、一意性 |
| パスワード | password | password | ○ | 8文字以上 |
| パスワード確認 | password_confirmation | password | ○ | passwordと一致 |

#### バリデーションルール
```php
[
    'name' => 'required|string|max:255',
    'email' => 'required|email|max:255|unique:users,email',
    'password' => 'required|string|min:8|confirmed',
]
```

#### エラーメッセージ
- **name required**: 名前は必須です
- **name max**: 名前は255文字以内で入力してください
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
| テーマカラー | theme_color | hidden（プリセット選択UI） | ○ | HEX形式（#RRGGBB）、プリセット8色から選択 |

**テーマカラープリセット（8色）**: インディゴ(`#667eea`)・ローズ(`#f5576c`)・スカイ(`#4facfe`)・ブルー(`#2563EB`)・エメラルド(`#059669`)・パープル(`#a855f7`)・オレンジ(`#f97316`)・ブラック(`#1D1D1F`)

#### バリデーションルール
```php
[
    'name' => 'required|string|max:50',
    'biography' => 'nullable|string|max:1000',
    'thumbnail_video_id' => 'nullable|integer|exists:videos,id',
    'popup_video_id' => 'nullable|integer|exists:videos,id',
    'theme_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
]
```

**追加チェック（FormRequest側）**:
- 選択された動画が自分の動画かどうかを確認
- 選択された動画のstatus が `completed` かどうかを確認

#### エラーメッセージ
- **name required**: 名前は必須です
- **name max**: 名前は50文字以内で入力してください
- **biography max**: 経歴は1000文字以内で入力してください
- **thumbnail_video_id exists**: 選択された動画が見つかりません
- **popup_video_id exists**: 選択された動画が見つかりません
- **動画所有権エラー**: 他のユーザーの動画は選択できません
- **theme_color required**: テーマカラーを選択してください
- **theme_color regex**: テーマカラーの形式が正しくありません

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
| 経歴 | profiles.biography | 設定されている場合のみ（min-heightで表示） |
| サムネイル動画 | videos (thumbnail_video_id) | 設定されている場合のみ（カード内右寄せ） |
| 管理ページへリンク | - | サムネイル動画の下（右寄せ）、公開ページのみ表示 |
| ポップアップ動画 | videos (popup_video_id) | サムネイルクリック時にカード内フルカード展開で自動再生 |

### レイアウト構造（ベントーグリッド）

ページ全体に `#F5F5F7`（Apple風ライトグレー）の背景を使用し、カードは角丸・テーマカラーのボーダーで表示する。

```
┌──────────────────────────────────────────────┐  ← 背景: #F5F5F7
│  ╔═══════════════════════════════════════╗   │
│  ║  [氏名]                               ║   │  ← カード: rounded-3xl, border-2（テーマカラー）
│  ║  ─────────────────────────────────── ║   │    max-w-2xl mx-auto
│  ║  [自己紹介文]                         ║   │
│  ║                                       ║   │
│  ║  （min-height: 30vh）                 ║   │
│  ║  ─────────────────────────────────── ║   │
│  ║                         ○ サムネ動画  ║   │
│  ║                          [管理ページ] ║   │
│  ╚═══════════════════════════════════════╝   │
└──────────────────────────────────────────────┘

【フルカード展開時】
│  ╔═══════════════════════════════════════╗   │
│  ║  ████████████████████████████████████║   │  ← カード内を黒背景の動画プレーヤーで全面覆う
│  ║  ████████   ▶（テーマカラー）  ████████║   │    通常コンテンツは opacity-0 で高さを維持
│  ║  ████████████████████████████████████║   │
│  ╚═══════════════════════════════════════╝   │
```

- カードは画面中央揃え（`max-w-2xl mx-auto`）
- カードのボーダーカラーはユーザーが設定した `theme_color` を適用
- サムネイル動画はカード内の右寄せ配置
- 「管理ページへ」リンクは公開ページのみに表示（プレビューページには表示しない）

### 動画再生仕様

#### サムネイル動画（詳細）

**表示仕様**:
- **位置**: プロフィールボックス内の下部・右寄せ（通常フロー配置）
- **形状**: 円形（`border-radius: 50%`）
- **サイズ**:
  - デスクトップ（1024px以上）: 128px × 128px
  - タブレット（768px〜1023px）: 112px × 112px
  - モバイル（767px以下）: 96px × 96px
- **影**: `box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1)`

**再生仕様**:
- 自動再生: ページ読み込み時に自動で再生開始
- ループ: 動画終了時に最初から再生
- ミュート: 音声は再生しない（iOSでの自動再生対応）
- インライン再生: `playsinline`属性でiOSでのフルスクリーン回避

**クロップ表示**:
- `object-fit: cover`で動画を円形にクロップ
- アスペクト比を維持しつつ、円形領域に収まるよう中央でトリミング

**インタラクション**:
- ホバー時: スケール拡大（`transform: scale(1.05)`）
- クリック時: ポップアップ動画をモーダルで表示

**プレースホルダー**:
- 動画未設定時: グレー背景の円形 + 「動画未設定」テキスト
- 背景色: `bg-gray-200`
- テキスト色: `text-gray-500`

#### ポップアップ動画（詳細）— カード内フルカード展開

**展開仕様**:
- **表示方法**: プロフィールカードの `position: absolute; inset: 0` で全面オーバーレイ（モーダルではなくカード内展開）
- **背景**: 黒（`bg-black`）
- **動画サイズ**: カード全体を覆う（`w-full h-full object-contain`）

**動画プレイヤー仕様**:
- **再生方式**: 展開時に自動再生
- **音声**: あり
- **コントロール**: HTML5標準コントロールなし（カスタム操作ボタンのみ）
  - 再生/一時停止: 動画エリアをクリックでトグル
  - 再生中: 下部グラデーションヒント表示
  - 停止中: テーマカラーの円形再生ボタンを中央表示（サイズ: 64px）

**閉じるボタン**:
- **位置**: カード内右上（`absolute top-3 right-3`）
- **アイコン**: × マーク（SVG）
- **色**: 白（`text-white`）、ホバー時: グレー

**閉じる方法**:
1. 閉じるボタン（×）クリック
2. Escapeキー押下

**閉じる時の動作**:
- 動画を一時停止・リセット（`pause()` + `load()`）
- フェードアウト後に通常コンテンツを再表示

**アニメーション**:
- 開く時: サムネイルをフェードアウト（160ms後）→ フルカードをフェードイン（`duration: 200ms`）
- 閉じる時: フルカードをフェードアウト（`duration: 200ms`）→ サムネイルをフェードイン（220ms後）

#### 動画表示条件

| 条件 | サムネイル表示 | ポップアップ動画展開 |
|------|---------------|---------------------|
| サムネイル動画あり & ポップアップ動画あり | 動画再生（クリック可能） | カード内フルカード展開で再生 |
| サムネイル動画あり & ポップアップ動画なし | 動画再生（クリック不可） | - |
| サムネイル動画なし & ポップアップ動画あり | プレースホルダー | - |
| サムネイル動画なし & ポップアップ動画なし | プレースホルダー | - |
| 動画status='encoding' | プレースホルダー | - |
| 動画status='failed' | プレースホルダー | - |

**注意**: `status='completed'`の動画のみ表示可能。エンコード中・失敗した動画はプレースホルダー扱い。

#### 技術実装（HTML5 Video）

```html
<!-- サムネイル動画 -->
<video
  class="w-full h-full object-cover rounded-full"
  autoplay
  loop
  muted
  playsinline
  preload="metadata"
>
  <source src="{{ S3_URL }}" type="video/mp4">
</video>

<!-- ポップアップ動画（自動再生） -->
<video
  class="w-full rounded-lg"
  controls
  autoplay
  playsinline
  preload="none"
>
  <source src="{{ S3_URL }}" type="video/mp4">
  <p>お使いのブラウザは動画再生に対応していません。</p>
</video>
```

#### ブラウザ対応

| ブラウザ | 対応バージョン | 備考 |
|---------|---------------|------|
| Chrome | 90+ | 完全対応 |
| Firefox | 88+ | 完全対応 |
| Safari | 14+ | `playsinline`必須 |
| Edge | 90+ | 完全対応 |
| iOS Safari | 14+ | `muted`+`playsinline`で自動再生可能 |
| Android Chrome | 90+ | 完全対応 |

### エラーケース
- **ユーザーが存在しない**: 404エラーページ表示

---

## 動画一覧ページ (GET /dashboard/videos)

### プロフィール設定欄の表示仕様

動画一覧テーブルの「プロフィール設定」欄では、各動画がサムネイル動画またはポップアップ動画として設定されているかを表示する。

| 設定状況 | 表示 |
|----------|------|
| サムネイル動画に設定中 | オレンジ色バッジ「サムネイル動画設定中」（bg-orange-100 text-orange-600） |
| ポップアップ動画に設定中 | オレンジ色バッジ「ポップアップ動画設定中」（bg-orange-100 text-orange-600） |
| 両方に設定中 | 両方のバッジを縦に表示（flex flex-col gap-1） |
| 未設定 | 「-」（text-gray-400） |

### 削除時の警告メッセージ

| 設定状況 | 警告メッセージ |
|----------|---------------|
| サムネイル動画のみ | 「この動画はプロフィールのサムネイル動画に設定されています。削除すると、サムネイル動画の設定も解除されます。本当に削除しますか？」 |
| ポップアップ動画のみ | 「この動画はプロフィールのポップアップ動画に設定されています。削除すると、ポップアップ動画の設定も解除されます。本当に削除しますか？」 |
| 両方に設定中 | 「この動画はプロフィールのサムネイル動画とポップアップ動画に設定されています。削除すると、両方の設定が解除されます。本当に削除しますか？」 |
| 未設定 | 「本当に削除しますか？この操作は取り消せません。」 |

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

## プレビューページ (GET /dashboard/preview)

### 表示項目

| 項目名 | データソース | 説明 |
|--------|------------|------|
| ナビゲーションボタン | - | 編集ページへ戻る、ダッシュボードへ戻る、公開ページを見る |
| 注意書きバナー | - | 「プレビュー表示中」警告メッセージ |
| プロフィール内容 | profiles | 公開ページと同じレイアウト |
| サムネイル動画 | videos | 公開ページと同じレイアウト（ボックス内右寄せ） |
| ポップアップ動画 | videos | サムネイルクリック時のモーダル（自動再生） |

### レイアウト構造

```
┌──────────────────────────────────────────────┐
│ [ナビゲーションボタン]                        │
│   編集ページへ戻る / ダッシュボードへ戻る / 公開ページを見る │
│ ─────────────────────────────────────────────│
│ ⚠️ プレビュー表示中                           │
│ ─────────────────────────────────────────────│
│ [氏名]                                        │
│ ─────────────────────────────────────────────│
│ [自己紹介文]                                  │
│                                              │
│（min-heightで画面下まで表示）                  │
│ ─────────────────────────────────────────────│
│                               ○ サムネ動画    │
└──────────────────────────────────────────────┘
```

- ナビゲーションボタンは「プレビュー表示中」バナーの**上**に配置
- 「管理ページへ」リンクは表示しない（公開ページのみ）

---

## チャットボットウィジェット (POST /chatbot/message)

### 概要
ログイン後の全画面右下にフローティング表示される独立UIコンポーネント。`layouts/app.blade.php` に組み込む Blade コンポーネント。

### 入力項目

| 項目 | 型 | 必須 | バリデーション |
|------|-----|------|---------------|
| message | text | ✅ | required, string, max:2000 |

### UI構成

**未展開時（全画面共通）**:
```
                                    ┌─────────┐
                                    │   💬    │ ← 右下固定ボタン (56×56px)
                                    └─────────┘
```

**展開時**:
```
                ┌──────────────────────────┐
                │ チャットボット       [×] │  ← ヘッダー
                ├──────────────────────────┤
                │                          │
                │  [ユーザー]              │  ← 会話履歴エリア
                │  動画のアップロード方法 │     (スクロール可・直近20件表示)
                │                          │
                │             [ボット]     │
                │  ダッシュボード右上...   │
                │                          │
                │  [ユーザー]              │
                │  (ローディング...)       │
                │                          │
                ├──────────────────────────┤
                │ ┌──────────────────────┐ │
                │ │ 質問を入力(2000文字)  │ │  ← 入力エリア
                │ └──────────────────────┘ │
                │                  [送信]  │
                └──────────────────────────┘
                  400px × 600px
```

### バリデーション

| エラー状態 | 表示メッセージ |
|-----------|--------------|
| 空送信 | 「メッセージを入力してください」(クライアント側) |
| 2000文字超 | 「2000文字以内で入力してください」 |
| レート制限 | 「送信頻度が高すぎます。しばらくお待ちください」 |
| 認証切れ | ログインページへリダイレクト |
| Bedrockエラー | 「一時的にエラーが発生しました。もう一度お試しください」 |

### 状態管理（Alpine.js）

```javascript
x-data="{
    open: false,          // ウィジェット展開状態
    messages: [],         // 会話履歴
    input: '',            // 現在入力中
    loading: false,       // 送信中フラグ
    error: null           // エラーメッセージ
}"
```

### 遷移・動作

1. **初回展開時**: `GET /chatbot/history?limit=20` で履歴取得
2. **送信時**: `POST /chatbot/message` で応答取得、`messages` に追加
3. **送信中**: 入力欄disable、ローディング表示
4. **エラー時**: エラーメッセージ表示、再送可能
5. **閉じる時**: 状態は保持（次回展開時に履歴復元）

### アクセシビリティ

- チャットボタンに `aria-label="チャットボットを開く"`
- メッセージリストは `role="log" aria-live="polite"` で新着読み上げ
- キーボード操作: Escで閉じる、Enterで送信（Shift+Enterで改行）

---

## FormRequestクラス

| クラス名 | 対応ルート | 主要バリデーション |
|----------|-----------|-------------------|
| RegisterRequest | POST /register | email (unique), password (min:8, confirmed) |
| LoginRequest | POST /login | email, password |
| UpdateProfileRequest | PUT /dashboard/profile | name (required, max:50), biography (max:1000), theme_color (required, HEX) |
| StoreVideoRequest | POST /dashboard/videos | video (required, file, mimes, max:102400) |
| UpdateUserRequest | PUT /admin/users/{id} | name, biography, role (in:admin,user) |

### FormRequestクラス実装例

#### UpdateProfileRequest.php
```php
<?php

namespace App\Http\Requests;

use App\Models\Video;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authミドルウェアで認証済み
    }

    public function rules(): array
    {
        $videoValidation = function ($attribute, $value, $fail) {
            if ($value) {
                $video = Video::find($value);
                // 所有権チェック
                if (!$video || $video->user_id !== $this->user()->id) {
                    $fail('選択された動画が無効です');
                }
                // エンコード完了チェック
                if ($video && $video->status !== 'completed') {
                    $fail('エンコードが完了していない動画は選択できません');
                }
            }
        };

        return [
            'name' => 'required|string|max:50',
            'biography' => 'nullable|string|max:1000',
            'thumbnail_video_id' => ['nullable', 'integer', 'exists:videos,id', $videoValidation],
            'popup_video_id' => ['nullable', 'integer', 'exists:videos,id', $videoValidation],
            'theme_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '名前は必須です',
            'name.max' => '名前は50文字以内で入力してください',
            'biography.max' => '経歴は1000文字以内で入力してください',
            'thumbnail_video_id.exists' => '選択された動画が見つかりません',
            'popup_video_id.exists' => '選択された動画が見つかりません',
            'theme_color.required' => 'テーマカラーを選択してください',
            'theme_color.regex' => 'テーマカラーの形式が正しくありません',
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
