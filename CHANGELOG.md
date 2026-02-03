# Changelog

このプロジェクトの全ての変更履歴を記録します。

フォーマットは [Keep a Changelog](https://keepachangelog.com/ja/1.0.0/) に基づいています。

---

## [1.0.0] - 2026-02-03

動画付き自己紹介プラットフォームの初回リリース。

### Added（新機能）

#### コア機能
- **Docker開発環境構築** - PHP 8.2 + MySQL 8.0 + Nginx + MinIO構成
- **Laravel 11.x初期セットアップ** - Breeze認証実装
- **データベーススキーマ** - users, profiles, videosテーブルのマイグレーション
- **Eloquentモデル** - User, Profile, Videoモデルとリレーション定義

#### 認証・認可
- **Laravel Breeze認証** - ログイン/登録/パスワードリセット機能
- **認証メッセージ日本語化** - 全認証メッセージを日本語対応
- **管理者ミドルウェア** - role:adminによる管理者機能保護

#### ユーザー向け機能
- **トップページ** - ユーザー一覧表示（サムネイル動画付き）
- **公開プロフィールページ** - 一般公開用のプロフィール表示
- **ダッシュボード** - ログインユーザー向けホーム画面
- **プロフィール編集** - 名前・経歴・動画選択機能
- **プレビュー機能** - 公開前のプロフィール確認
- **動画表示機能** - サムネイル動画とポップアップ動画の再生
- **ポップアップ動画選択** - プロフィールにポップアップ動画を設定

#### 動画管理
- **動画一覧ページ** - アップロード済み動画の管理
- **動画アップロード** - mp4, mov, avi, wmv対応（100MB以内）
- **動画エンコード** - FFmpegによるmp4(H.264/AAC)変換
- **エンコードリトライ** - 失敗時最大3回リトライ
- **動画削除機能** - S3連携した動画削除
- **S3連携** - MinIO（ローカル）/ AWS S3（本番）対応

#### 管理者機能
- **ユーザー一覧** - 全ユーザーの閲覧
- **ユーザー編集** - 管理者によるユーザー情報編集
- **ユーザー削除** - 関連動画を含む完全削除

#### インフラ・デプロイ
- **AWS CloudFormation構築** - EC2, S3, RDS, VPCのIaC化
- **SSH Agent Forwarding** - EC2へのgit clone対応

### Fixed（バグ修正）

- 動画アップロード時のoriginal_pathカラムエラーを修正
- トップページのレイアウト崩れを修正
- トップページのヘッダーロゴ表示問題を修正
- セキュリティ改善とFFmpegパス修正
- EC2 UserDataのPHPパッケージ名とFFmpegインストール方法を修正

### Security（セキュリティ）

- **セキュリティヘッダーミドルウェア** - X-Frame-Options, X-Content-Type-Options等を追加
- XSS対策（Bladeエスケープ）
- CSRF対策（全フォーム）
- SQLインジェクション対策（Eloquent使用）

### Performance（パフォーマンス改善）

- **N+1問題解決** - Eager Loadingの適用
- **キャッシュ導入** - 頻繁アクセスデータのキャッシュ化
- 公開プロフィールページのEager Loading最適化

### Changed（変更）

- Laravelロゴをテキストロゴに変更
- 公開ページ専用レイアウトを追加し、プレビューとの表示を統一
- 動画管理画面でサムネイル/ポップアップを区別表示
- .env.exampleを統一テンプレートに整理

### Documentation（ドキュメント）

- プロジェクト設計書とissue定義を追加
- 動画表示機能のドキュメントを追加
- AWS本番環境の構成図を追加
- Issue #7,#9,#10,#27の更新履歴を更新

### Tests（テスト）

- **単体テスト** - Model/Serviceの単体テスト実装
- **機能テスト** - Controller/Routeの機能テスト実装
- Playwrightテストライブラリを追加

### Chores（その他）

- 動画アップロード用PHP設定を追加
- .gitignoreにClaude Code設定ディレクトリを追加
- Playwright MCPの使用ルールをCLAUDE.mdに追記
- Claude Code Actionでインラインコメントツールを許可
- Claude PR Assistant / Code Reviewワークフローを追加

---

## 技術スタック

| カテゴリ | 技術 |
|---------|------|
| バックエンド | Laravel 11.x, PHP 8.2 |
| フロントエンド | Blade, Tailwind CSS |
| データベース | MySQL 8.0 |
| ストレージ | AWS S3 / MinIO |
| 動画処理 | FFmpeg |
| インフラ | AWS (EC2, S3, RDS), Docker |
| CI/CD | GitHub Actions |

---

## コントリビューター

- 開発チーム

---

[1.0.0]: https://github.com/tetsu2026/movie_prf_pj/releases/tag/v1.0.0
