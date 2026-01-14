# Issue #3: データベーススキーマ作成（マイグレーション）

## 背景 / 目的

設計書（`docs/03_database.md`）に従い、users/profiles/videosテーブルのマイグレーションファイルを作成し、データベーススキーマを構築する。

- **依存**: #2
- **ラベル**: backend

---

## スコープ / 作業項目

### 1. usersテーブルの拡張
- Laravel Breezeのデフォルトusersテーブルを拡張
- マイグレーションファイル作成: `database/migrations/XXXX_add_role_to_users_table.php`
- 追加カラム:
  - `role` ENUM('admin', 'user') DEFAULT 'user'
  - `deleted_at` TIMESTAMP NULL（ソフトデリート用）

### 2. profilesテーブル作成
- マイグレーションファイル: `database/migrations/XXXX_create_profiles_table.php`
- カラム:
  - `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id` BIGINT UNSIGNED NOT NULL UNIQUE（外部キー → users.id）
  - `name` VARCHAR(50) NOT NULL
  - `biography` TEXT NULL
  - `thumbnail_video_id` BIGINT UNSIGNED NULL（外部キー → videos.id）
  - `popup_video_id` BIGINT UNSIGNED NULL（外部キー → videos.id）
  - `created_at`, `updated_at` TIMESTAMP
- 外部キー制約:
  - `user_id` → `users(id)` ON DELETE CASCADE
  - `thumbnail_video_id` → `videos(id)` ON DELETE SET NULL
  - `popup_video_id` → `videos(id)` ON DELETE SET NULL

### 3. videosテーブル作成
- マイグレーションファイル: `database/migrations/XXXX_create_videos_table.php`
- カラム:
  - `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id` BIGINT UNSIGNED NOT NULL（外部キー → users.id）
  - `original_filename` VARCHAR(255) NOT NULL
  - `original_path` VARCHAR(500) NULL
  - `encoded_path` VARCHAR(500) NULL
  - `file_size` BIGINT UNSIGNED NULL
  - `duration` INT UNSIGNED NULL
  - `status` ENUM('uploading', 'encoding', 'completed', 'failed') DEFAULT 'uploading'
  - `error_message` TEXT NULL
  - `retry_count` TINYINT UNSIGNED DEFAULT 0
  - `created_at`, `updated_at` TIMESTAMP
- 外部キー制約:
  - `user_id` → `users(id)` ON DELETE CASCADE
- インデックス:
  - INDEX `idx_user_id` (user_id)
  - INDEX `idx_status` (status)

### 4. マイグレーション実行
```bash
php artisan migrate
```

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] usersテーブルに `role`、`deleted_at` カラムが追加されるマイグレーションが作成される
- [ ] profilesテーブルのマイグレーションが作成され、外部キー制約が正しく設定される
- [ ] videosテーブルのマイグレーションが作成され、status ENUM/retry_countカラムが含まれる
- [ ] `php artisan migrate` でエラーなく全テーブルが作成される
- [ ] 外部キー制約（CASCADE/SET NULL）が設計書通りに動作する
- [ ] `php artisan migrate:rollback` でロールバックが成功する
- [ ] データベース内でテーブル構造を確認し、設計書と一致する

---

## テスト観点

### マイグレーション動作確認
- [ ] `php artisan migrate` が成功する
- [ ] `php artisan migrate:status` で全マイグレーションが `Ran` 状態になる
- [ ] MySQLに接続し、`SHOW TABLES;` で users/profiles/videos が存在する
- [ ] `DESCRIBE users;` で role/deleted_at カラムが存在する
- [ ] `DESCRIBE profiles;` で全カラムが設計書通りに存在する
- [ ] `DESCRIBE videos;` で全カラムが設計書通りに存在する

### 外部キー制約確認
- [ ] `SHOW CREATE TABLE profiles;` で外部キー制約が確認できる
- [ ] ユーザー削除時にプロフィールがカスケード削除されることを手動確認（後のIssueでテストコード追加）

### 検証方法
```bash
docker-compose exec db mysql -u root -proot laravel -e "SHOW TABLES;"
docker-compose exec db mysql -u root -proot laravel -e "DESCRIBE users;"
docker-compose exec db mysql -u root -proot laravel -e "DESCRIBE profiles;"
docker-compose exec db mysql -u root -proot laravel -e "DESCRIBE videos;"
docker-compose exec db mysql -u root -proot laravel -e "SHOW CREATE TABLE profiles\G"
```

---

## 課題確認事項

- **profilesとvideosの外部キー順序**: profilesがvideosを参照するため、videosマイグレーションを先に実行する必要がある（マイグレーションファイル名のタイムスタンプで制御）
- **インデックス追加**: deleted_at にインデックスが必要か？（ソフトデリート検索の最適化）
- **文字コード**: utf8mb4_unicode_ci で統一でOK？

---

## 参考資料

- データベース設計書: `docs/03_database.md`
- ER図: `docs/09_er.md`
