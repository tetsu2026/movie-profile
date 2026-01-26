# Claude Code プロジェクトルール設定

このドキュメントは、Claude Codeがこのプロジェクトで作業する際に従うべきルールと設定を定義します。

---

## 📋 プロジェクト概要

**プロジェクト名**: 動画付き自己紹介プラットフォーム
**技術スタック**: Laravel 11.x + PHP 8.2 + MySQL 8.0 + Blade + Tailwind CSS
**インフラ**: AWS (EC2, S3, RDS) + Docker (ローカル開発)
**設計書**: `docs/` 配下に要件定義・アーキテクチャ・DB設計などを配置

---

## 🌐 言語設定

**基本的に日本語で回答してください**

- コードのコメントは日本語で記述
- エラーメッセージや説明も日本語で提供
- ただし、変数名・関数名・クラス名は英語で命名（Laravel規約に準拠）
- ドキュメント生成時も日本語を優先

---

## 📝 コミットメッセージフォーマット

コミットメッセージは以下のフォーマットに従ってください。

### フォーマット

```
<type>: <subject>

<body (optional)>
```

### Type（種別）

| Type | 説明 | 例 |
|------|------|------|
| `feat` | 新機能の追加 | `feat: ユーザー登録機能を実装` |
| `fix` | バグ修正 | `fix: 動画アップロード時のタイムアウトエラーを修正` |
| `refactor` | リファクタリング（機能変更なし） | `refactor: VideoService のエンコード処理を整理` |
| `perf` | パフォーマンス改善 | `perf: 動画一覧取得クエリを最適化` |
| `style` | コードスタイル修正（機能影響なし） | `style: PSR-12 に準拠するよう整形` |
| `test` | テストの追加・修正 | `test: ProfileController の単体テストを追加` |
| `docs` | ドキュメント更新 | `docs: README にセットアップ手順を追記` |
| `chore` | ビルド・設定ファイルの変更 | `chore: Docker 設定を更新` |
| `security` | セキュリティ対策 | `security: XSS 対策を強化` |

### 例

```
feat: 動画エンコード機能を実装

FFmpegを使用したmp4エンコード処理を追加
- VideoEncoderServiceクラスを作成
- エンコードキューをRedisで管理
- 失敗時は最大3回リトライ
```

```
fix: プロフィール更新時の動画選択バリデーションエラーを修正

動画IDが存在しない場合のバリデーションルールを追加
```

---

## 🎨 コーディングスタイルルール

### 1. PHP / Laravel

#### 基本規約

- **PSR-12** コーディング標準に準拠
- インデント: **4スペース**
- 文字コード: **UTF-8**
- 改行コード: **LF**

#### 命名規則

| 対象 | ルール | 例 |
|------|--------|------|
| クラス名 | PascalCase | `VideoController`, `ProfileService` |
| メソッド名 | camelCase | `uploadVideo()`, `encodeVideo()` |
| 変数名 | camelCase | `$userId`, `$encodedPath` |
| 定数 | UPPER_SNAKE_CASE | `MAX_FILE_SIZE`, `ENCODING_TIMEOUT` |
| データベーステーブル | snake_case（複数形） | `users`, `profiles`, `videos` |
| データベースカラム | snake_case | `user_id`, `created_at`, `encoded_path` |

#### Laravel ベストプラクティス

- **Eloquent モデル**: テーブル名は複数形、モデル名は単数形（`User`, `Profile`, `Video`）
- **リレーション**: `belongsTo`, `hasOne`, `hasMany` を適切に定義
- **マスアサインメント**: `$fillable` または `$guarded` を必ず設定
- **バリデーション**: FormRequest クラスを使用（`StoreProfileRequest` など）
- **サービスクラス**: 複雑なビジネスロジックは `app/Services/` に分離
- **リポジトリパターン**: 必要に応じて `app/Repositories/` を使用

#### コメント

```php
/**
 * 動画をエンコードしてS3にアップロードする
 *
 * @param Video $video エンコード対象の動画モデル
 * @return bool エンコード成功時 true
 * @throws EncodingException エンコード失敗時
 */
public function encodeVideo(Video $video): bool
{
    // FFmpegでエンコード処理を実行
    $result = $this->ffmpeg->encode($video->original_path);

    return $result;
}
```

- DocBlock は公開メソッドに必ず記述
- 複雑なロジックには日本語コメントを追加
- `TODO`, `FIXME`, `NOTE` などのマーカーを活用

### 2. Blade テンプレート

#### 基本ルール

- インデント: **2スペース**
- Blade ディレクティブは `@` で始める（`@if`, `@foreach`, `@yield`）
- XSS対策: 必ず `{{ $variable }}` を使用（エスケープされる）
- HTMLタグは小文字で統一

#### ファイル構成

```
resources/views/
├── layouts/
│   ├── app.blade.php        # 共通レイアウト
│   └── guest.blade.php      # 未認証ユーザー用レイアウト
├── components/              # 再利用可能なコンポーネント
│   ├── video-player.blade.php
│   └── modal.blade.php
├── dashboard/               # 認証ユーザー向け画面
│   ├── index.blade.php
│   ├── profile/
│   └── videos/
├── users/                   # 公開プロフィールページ
│   └── show.blade.php
└── admin/                   # 管理者専用画面
    └── users/
```

#### 例

```blade
{{-- プロフィール表示コンポーネント --}}
<div class="profile-container">
  @if ($profile->thumbnail_video_id)
    <x-video-player
      :src="$profile->thumbnailVideo->encoded_path"
      :autoplay="true"
      :muted="true"
    />
  @else
    <p class="text-gray-500">動画が設定されていません</p>
  @endif

  <h1 class="text-2xl font-bold">{{ $profile->name }}</h1>
  <p class="whitespace-pre-wrap">{{ $profile->biography }}</p>
</div>
```

### 3. Tailwind CSS

#### 基本ルール

- クラス名はアルファベット順に並べる
- レスポンシブ対応は `sm:`, `md:`, `lg:` プレフィックスを使用
- カスタムCSSは最小限に抑え、Tailwind ユーティリティクラスを優先

#### 例

```html
<button class="bg-blue-500 hover:bg-blue-700 rounded px-4 py-2 text-white font-bold">
  アップロード
</button>
```

#### CSS再ビルドのルール

Tailwind CSSはJIT（Just-In-Time）モードで動作しているため、新しいクラスを使用した場合はCSSの再ビルドが必要です。

**再ビルドが必要なケース**:
- 今まで使用していなかったTailwindクラスを追加した場合（例: `bg-purple-100`, `text-purple-600`など）
- カスタムクラスを追加した場合

**再ビルドコマンド**:
```bash
# Dockerコンテナ内で実行
docker compose exec app npm run build
```

**再ビルドが不要なケース**:
- 既に他のファイルで使用されているクラスを使う場合
- HTMLの構造変更のみの場合

**確認方法**:
- ブラウザで表示を確認し、スタイルが適用されていない場合は再ビルドを実行
- 開発中は `npm run dev` でホットリロードを有効にしておくと自動反映される

### 4. データベース

#### マイグレーション

- ファイル名: `YYYY_MM_DD_HHMMSS_create_xxxx_table.php`
- テーブル作成時は必ず `timestamps()` を追加
- 外部キー制約は `constrained()->onDelete('cascade')` で適切に設定

```php
Schema::create('videos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('original_filename');
    $table->enum('status', ['uploading', 'encoding', 'completed', 'failed'])->default('uploading');
    $table->timestamps();
});
```

#### シーダー

- 開発用ダミーデータは `database/seeders/` に配置
- 本番環境では実行しない設定を徹底

---

## 🎯 設計原則とベストプラクティス

### 1. デフォルトの標準設定を優先

このプロジェクトでは、**フレームワークやライブラリのデフォルト設定を最大限活用**してください。

#### 適用例

**推奨される実装 ✅**
- **Tailwind CSS**: ユーティリティクラスのみで実装し、カスタムCSSファイルは作成しない
- **Laravel Breeze**: 認証画面のデフォルトレイアウト・スタイルをそのまま使用
- **Eloquent ORM**: 標準の命名規則（`created_at`, `updated_at`）に従う
- **PSR-12**: PHPの標準コーディング規約に準拠
- **Laravel の規約**: ディレクトリ構成、ファイル命名、クラス配置をLaravel標準に従う

**避けるべき実装 ❌**
- カスタムCSSファイル（`custom.css`, `style.css`）の作成
- Tailwind の設定を過度にカスタマイズ（色やサイズの独自定義）
- Laravel の規約を無視した独自のディレクトリ構造
- フレームワークの機能を再実装（車輪の再発明）

#### 理由

- **学習コストの削減**: 標準に従うことで、他の開発者が参加しやすい
- **メンテナンス性**: フレームワークのアップデートに追従しやすい
- **バグの削減**: 実績のあるデフォルト設定は安定している
- **開発速度**: ゼロから作るより既存機能を活用する方が早い

### 2. コードとコミットメッセージの統一性

プロジェクト全体で**一貫性のあるスタイル**を維持してください。

#### コードの統一性

**命名規則の一貫性**
- 変数名: `$userId`, `$videoPath` のように統一（`$user_id` と `$userId` の混在を避ける）
- メソッド名: 動詞で始める（`getUser()`, `createVideo()`, `updateProfile()`）
- クラス名: 単数形で統一（`Video`, `Profile`, `User`）

**コーディングパターンの統一**
```php
// ✅ 推奨: プロジェクト全体でこのパターンを統一
public function store(Request $request)
{
    $validated = $request->validated();

    $video = Video::create($validated);

    return redirect()->route('videos.index')
        ->with('success', '動画をアップロードしました');
}

// ❌ 避ける: 別の場所で異なるパターンを使わない
public function save(Request $request)
{
    $video = new Video();
    $video->fill($request->all());
    $video->save();

    return back()->with('message', 'アップロード完了');
}
```

**エラーハンドリングの統一**
```php
// プロジェクト全体で同じエラーハンドリングパターンを使用
try {
    $this->videoService->encode($video);
} catch (EncodingException $e) {
    Log::error('動画エンコード失敗', [
        'video_id' => $video->id,
        'error' => $e->getMessage()
    ]);

    return back()->with('error', 'エンコード処理に失敗しました');
}
```

#### コミットメッセージの統一性

**Type の使い分けを統一**
- 新しいファイル追加 → `feat:`
- 既存機能の修正 → `fix:` または `refactor:`
- テストコード → `test:`
- ドキュメント → `docs:`

**Subject（件名）のスタイル統一**
```bash
# ✅ 推奨: 体言止めで統一
feat: ユーザー登録機能を実装
fix: 動画アップロードのバリデーションエラーを修正
refactor: VideoService のエンコード処理を整理

# ❌ 避ける: 「〜する」「〜した」などの混在
feat: ユーザー登録機能を実装する
fix: 動画アップロードのバリデーションエラーを修正した
refactor: VideoService を整理
```

**Body（本文）の粒度を統一**
```bash
# ✅ 推奨: 箇条書きで変更内容を明記
feat: 動画エンコード機能を実装

- VideoEncoderService クラスを作成
- FFmpeg を使用した mp4 エンコード処理を追加
- エンコード失敗時は最大3回リトライ
- エンコード状態を videos テーブルで管理

# ❌ 避ける: 曖昧な説明
feat: 動画エンコード機能を実装

動画のエンコード処理を追加しました。
```

#### 統一性チェックポイント

Claude がコードを提案する際は、以下の統一性を確認してください：

- [ ] 既存コードと同じ命名規則を使用しているか
- [ ] 既存コードと同じインデントスタイル（PHP: 4スペース、Blade: 2スペース）を使用しているか
- [ ] 既存コードと同じエラーハンドリングパターンを使用しているか
- [ ] 既存コードと同じレスポンス形式（`redirect()`, `back()`, `view()`）を使用しているか
- [ ] コミットメッセージが既存のコミット履歴と同じフォーマットになっているか
- [ ] 同じ機能を実装する際に、別の実装方法を提案していないか

---

## 🔍 Claude に指摘してほしい観点

### 1. セキュリティ

以下のセキュリティ問題を必ずチェックしてください。

- **XSS (クロスサイトスクリプティング)**: Blade で `{!! !!}` の使用を避け、`{{ }}` でエスケープ
- **CSRF**: フォーム送信時に `@csrf` ディレクティブを必ず含める
- **SQLインジェクション**: Eloquent またはクエリビルダを使用し、生SQLは避ける
- **パスワード**: 必ず `bcrypt` または `Hash::make()` でハッシュ化
- **ファイルアップロード**: MIME タイプとファイルサイズを厳格にバリデーション
- **権限チェック**: ミドルウェア（`auth`, `role:admin`）やポリシーで認可を徹底
- **S3バケット**: パブリック読み取りは必要最小限に、書き込みは不可に設定

### 2. パフォーマンス

- **N+1問題**: `with()` で Eager Loading を実施
- **不要なクエリ**: `select()` で必要なカラムのみ取得
- **インデックス**: 検索・結合条件のカラムにインデックスを設定
- **キャッシュ**: 頻繁にアクセスするデータは `Cache::remember()` を活用
- **動画エンコード**: 同期処理は避け、キューで非同期実行

### 3. 冗長なコードの削減

- **DRY原則**: 同じ処理を複数箇所に書かない（サービスクラスやヘルパー関数に集約）
- **マジックナンバー**: 定数として定義（`MAX_FILE_SIZE = 100 * 1024 * 1024`）
- **条件分岐の簡潔化**: Early Return を活用
- **不要な変数**: 1回しか使わない変数は省略

### 4. エラーハンドリング

- **try-catch**: 外部API呼び出しやファイル操作は必ず try-catch で囲む
- **ログ記録**: `Log::error()` でエラー内容を記録
- **ユーザー通知**: エラー発生時は適切なメッセージを表示
- **リトライロジック**: 動画エンコード失敗時は最大3回リトライ

### 5. テスタビリティ

- **依存性注入**: コンストラクタインジェクションを活用
- **モック化**: 外部サービス（S3, FFmpeg）はモック可能な設計に
- **単体テスト**: 重要なロジックは必ずテストを記述（`tests/Unit/`）
- **機能テスト**: エンドツーエンドのテストも実装（`tests/Feature/`）

---

## 🚀 プロジェクト固有のルール

### 1. 動画処理

#### エンコード仕様

- **出力形式**: mp4 (H.264/AAC)
- **最大解像度**: 1080p
- **タイムアウト**: 5分
- **リトライ**: 最大3回
- **ステータス**: `uploading` → `encoding` → `completed` / `failed`

#### ストレージ

- **S3バケット構成**: `videos/{user_id}/{video_id}/original.mp4`, `encoded.mp4`
- **削除ポリシー**: エンコード完了後は元動画を削除
- **アクセス権限**: エンコード済み動画のみパブリック読み取り可能

### 2. ユーザー権限

- **一般ユーザー**: 自分のプロフィール・動画のみ編集可能
- **管理者**: 全ユーザーのデータを閲覧・編集・削除可能
- **ミドルウェア**: `role:admin` で管理者機能を保護

### 3. バリデーションルール

| 項目 | ルール |
|------|--------|
| 名前 | 必須、50文字以内 |
| 経歴 | 任意、1000文字以内 |
| 動画ファイルサイズ | 100MB以内 |
| 動画の長さ | 1分以内 |
| 対応フォーマット | mp4, mov, avi, wmv |

### 4. データベース命名規則

設計書（`docs/03_database.md`）に従ってください。

- テーブル: `users`, `profiles`, `videos`
- 外部キー: `{関連テーブル名}_id`（例: `user_id`, `thumbnail_video_id`）
- ソフトデリート: `deleted_at` カラムを使用

---

## 📚 参考ドキュメント

プロジェクトの詳細は以下のドキュメントを参照してください。

| ドキュメント | パス | 内容 |
|------------|------|------|
| 要件定義書 | `docs/01_requirements.md` | プロジェクト概要、機能要件、非機能要件 |
| アーキテクチャ設計 | `docs/02_architecture.md` | 技術スタック、システム構成、コスト試算 |
| データベース設計 | `docs/03_database.md` | テーブル定義、ER図、インデックス設計 |
| サイトマップ | `docs/04_sitemap.md` | ページ構成、ユーザーフロー |
| データフロー | `docs/05_data_flow.md` | データの流れ、処理フロー |
| ルーティング | `docs/06_routing.md` | URL設計、エンドポイント一覧 |
| 画面設計 | `docs/07_screen_design.md` | UI/UXデザイン仕様 |
| ステートマシン | `docs/08_state_machine_video.md` | 動画エンコード状態遷移 |
| ER図 | `docs/09_er.md` | データベースER図 |

**重要**: 新機能実装やDB変更時は、必ず設計書を確認してください。

---

## 🛠 開発環境

### ローカル開発

- **Docker Compose** を使用
- MinIO でS3をエミュレート
- MySQL 8.0 コンテナ
- Nginx + PHP 8.2 コンテナ

### コマンド例

```bash
# コンテナ起動
docker-compose up -d

# マイグレーション実行
docker-compose exec app php artisan migrate

# シーダー実行（開発環境のみ）
docker-compose exec app php artisan db:seed

# テスト実行
docker-compose exec app php artisan test
```

---

## ✅ コードレビュー時のチェックリスト

Claude がコードを提案する際は、以下を確認してください。

### 基本品質
- [ ] PSR-12 に準拠しているか
- [ ] XSS, CSRF, SQLインジェクション対策が適切か
- [ ] N+1問題が発生していないか
- [ ] バリデーションルールが設計書通りか
- [ ] エラーハンドリングとログ記録が適切か
- [ ] コメントが日本語で記述されているか
- [ ] マジックナンバーが定数化されているか
- [ ] 不要な変数や冗長な処理がないか
- [ ] テストが必要な箇所にテストコードがあるか
- [ ] 設計書（`docs/`）との整合性が取れているか

### 統一性・標準設定
- [ ] **デフォルト設定を優先**: カスタムCSSやフレームワークの再実装を避けているか
- [ ] **命名規則の統一**: 既存コードと同じ命名パターン（camelCase, PascalCase）を使用しているか
- [ ] **コーディングパターンの統一**: 既存コードと同じ実装パターンを使用しているか
- [ ] **エラーハンドリングの統一**: プロジェクト全体で同じエラー処理パターンを使用しているか
- [ ] **コミットメッセージの統一**: 既存のコミット履歴と同じフォーマット（type: subject）を使用しているか

---

## 📌 その他の注意事項

- **絵文字**: ドキュメント内では使用可能ですが、コードやコミットメッセージには基本的に使用しない
- **後方互換性**: 本プロジェクトはMVP開発中のため、破壊的変更も許容されます（Phase 2以降は注意）
- **コスト意識**: AWS リソースは最小限に（月額 $30 以下を目標）
- **スケーラビリティ**: 初期は100ユーザー想定だが、将来の拡張を考慮した設計を心がける

---

## 🔄 このドキュメントの更新

プロジェクトの進行に伴い、ルールが変更される場合があります。
変更時は必ずこのドキュメントを更新してください。

**最終更新**: 2026-01-13
**更新内容**: デフォルト設定の優先とコード統一性のルールを追加


## Playwright MCP使用ルール

### 絶対的な禁止事項

1. **いかなる形式のコード実行も禁止**

   - Python、JavaScript、Bash等でのブラウザ操作
   - MCPツールを調査するためのコード実行
   - subprocessやコマンド実行によるアプローチ

2. **利用可能なのはMCPツールの直接呼び出しのみ**

   - playwright:browser_navigate
   - playwright:browser_screenshot
   - 他のPlaywright MCPツール

3. **エラー時は即座に報告**
   - 回避策を探さない
   - 代替手段を実行しない
   - エラーメッセージをそのまま伝える