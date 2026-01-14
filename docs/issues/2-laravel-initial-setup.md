# Issue #2: Laravel 11.x 初期セットアップ

## 背景 / 目的

Laravel 11.xプロジェクトを作成し、Laravel Breeze（認証パッケージ）とTailwind CSSをインストールして、MVP開発の基盤を整える。

- **依存**: #1
- **ラベル**: backend, frontend

---

## スコープ / 作業項目

### 1. Laravelプロジェクト作成
```bash
docker-compose exec app composer create-project laravel/laravel:^11.0 .
```

### 2. Laravel Breezeインストール
```bash
docker-compose exec app composer require laravel/breeze --dev
docker-compose exec app php artisan breeze:install blade
```
- Blade + Tailwind CSS スタックを選択
- ダークモード: なし
- テストフレームワーク: Pest

### 3. 依存関係インストール
```bash
docker-compose exec app npm install
docker-compose exec app npm run build
```

### 4. 環境設定
- `.env` ファイルのDB接続情報を設定
  - `DB_HOST=db`
  - `DB_DATABASE=laravel`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=root`
- アプリケーションキー生成: `php artisan key:generate`

### 5. マイグレーション実行
```bash
docker-compose exec app php artisan migrate
```
- Breezeデフォルトのusersテーブル等が作成される

### 6. 動作確認
- http://localhost でLaravelウェルカムページが表示される
- http://localhost/login でログインページが表示される

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] Laravel 11.xプロジェクトが作成され、`composer install` が完了している
- [ ] Laravel Breeze（Blade + Tailwind CSS）がインストールされている
- [ ] `php artisan serve` でLaravelウェルカムページが表示される（Dockerコンテナ内で確認）
- [ ] `npm install && npm run build` でTailwind CSSがビルドされ、`public/build/` に成果物が生成される
- [ ] `.env` ファイルでDB接続設定が完了している
- [ ] `php artisan migrate` が成功し、usersテーブルが作成される
- [ ] http://localhost/register と http://localhost/login が表示される

---

## テスト観点

### 動作確認
- [ ] `docker-compose exec app php artisan --version` でLaravel 11.xが表示される
- [ ] `docker-compose exec app composer show laravel/breeze` でBreezeがインストールされている
- [ ] http://localhost でLaravelウェルカムページが表示される
- [ ] http://localhost/register でユーザー登録フォームが表示される
- [ ] http://localhost/login でログインフォームが表示される
- [ ] Tailwind CSSのスタイルが適用されている（Breezeのデフォルトデザイン）

### 検証方法
1. `docker-compose exec app bash` でコンテナに入る
2. `php artisan migrate:status` でマイグレーション状態を確認
3. ブラウザで http://localhost にアクセス
4. /register、/login ページを確認

---

## 課題確認事項

- **Laravelバージョン**: 11.0系の最新版（^11.0）でOK？
- **Breezeスタック**: Blade + Tailwind CSS で確定？（Inertia.jsは不要）
- **テストフレームワーク**: PestかPHPUnit？（推奨はPest）
- **npm run dev vs npm run build**: 開発中は `npm run dev` を常時実行？

---

## 参考資料

- アーキテクチャ設計書: `docs/02_architecture.md`
- Laravel公式ドキュメント: https://laravel.com/docs/11.x
- Laravel Breeze公式: https://laravel.com/docs/11.x/starter-kits#laravel-breeze
