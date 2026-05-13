# Issue #33: EC2 ホスト nginx をリバプロ専用に再構成

## 背景 / 目的

EC2 ホスト上の nginx を、PHP-FPM への直接プロキシから ECS タスクへのリバースプロキシに切替する。既存の Let's Encrypt 証明書はそのまま利用し、ホスト nginx は SSL 終端＋リバプロのみを担う。

切替後、ホスト側の PHP-FPM サービスは停止し、すべての HTTP 処理は ECS タスク内の nginx + php-fpm が担当する。

- **依存**: #32
- **ラベル**: infra
- **関連プラン**: `docs/plans/ecs_ecr_migration_phase1.md`

---

## スコープ / 作業項目

### 1. 切替前のバックアップ
```bash
sudo cp /etc/nginx/conf.d/movie-prf.conf /etc/nginx/conf.d/movie-prf.conf.bak
sudo cp -r /etc/php /etc/php.bak
```

### 2. 新 nginx 設定
- `/etc/nginx/conf.d/hozu.click.conf` を編集
- 旧 `location ~ \.php$` ブロックを削除
- 全リクエストを `proxy_pass http://127.0.0.1:8080` に転送
- 既存の `client_max_body_size 100M`、`proxy_read_timeout 300s` は維持
- SSL 設定（Let's Encrypt）はそのまま

### 3. 設定例
```nginx
server {
    listen 443 ssl http2;
    server_name hozu.click;
    ssl_certificate     /etc/letsencrypt/live/hozu.click/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/hozu.click/privkey.pem;
    client_max_body_size 100M;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_read_timeout 300s;
    }
}

server {
    listen 80;
    server_name hozu.click;
    return 301 https://$host$request_uri;
}
```

### 4. nginx 設定テスト & リロード
```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 5. PHP-FPM サービス停止
```bash
sudo systemctl disable --now php-fpm
```

### 6. 動作確認
```bash
curl -I https://hozu.click/up   # 200 OK
curl -I https://hozu.click/     # 200 OK
```

### 7. ロールバック手順整備
- 旧 conf を復元 → PHP-FPM 再起動の手順を `docs/operations/rollback_phase7.md` に記載

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] `/etc/nginx/conf.d/hozu.click.conf` が `proxy_pass http://127.0.0.1:8080` に書き換えられる
- [ ] `client_max_body_size 100M`、`proxy_read_timeout 300s` が維持される
- [ ] 旧 `location ~ \.php$` ブロックは `.bak` で残し、PHP-FPM サービスは `systemctl disable --now php-fpm`
- [ ] `nginx -t` が合格し、`systemctl reload nginx` でリロードできる
- [ ] `curl https://hozu.click/up` が 200 を返す
- [ ] ロールバック手順（nginx 旧 conf 復元 + php-fpm 再起動）が動作確認される

---

## テスト観点

### nginx 設定
- [ ] `nginx -t` がエラーなし
- [ ] `systemctl reload nginx` で再読込
- [ ] `systemctl status nginx` が active

### 疎通
- [ ] `curl -I https://hozu.click/up` で 200
- [ ] ブラウザで `https://hozu.click/` を開いてログイン画面が表示
- [ ] ログイン後ダッシュボードが表示
- [ ] 動画アップロード → Queue dispatch → Worker でエンコード成功

### ヘッダー転送
- [ ] Laravel 側で `$request->ip()` が正しい（X-Forwarded-For）
- [ ] Laravel 側で `https://` のスキームが正しい（X-Forwarded-Proto）
- [ ] `app/Http/Middleware/TrustProxies.php` で proxy 信頼設定済み

### 大容量アップロード
- [ ] 100MB の動画ファイルがアップロード成功
- [ ] エンコード完了まで 5 分以上かかってもタイムアウトしない

### ロールバック
- [ ] 旧 conf 復元 + `systemctl start php-fpm` で旧構成に戻り、`https://hozu.click/` が動作

---

## 課題確認事項

- **TrustProxies 設定**: Laravel 側で `$proxies = '*'` 等の設定が必要（既に設定済みか確認）
- **PHP-FPM の物理削除**: Phase 7 完了後の運用が安定したら、`/etc/php`、`/var/www/movie-prf` 配置を削除して EC2 ディスク容量を確保
- **ホスト nginx と コンテナ内 nginx の二重構成**: 当面はこのまま運用、Phase 8（ALB 導入）でホスト nginx を撤去

---

## 参考資料

- 移行プラン: `docs/plans/ecs_ecr_migration_phase1.md`
- アーキテクチャ設計書: `docs/design-docs/02_architecture.md`
