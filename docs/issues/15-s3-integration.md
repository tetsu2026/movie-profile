# Issue #15: S3連携実装（MinIO/本番S3）

## 背景 / 目的

S3クライアント設定と動画アップロード・削除機能を実装し、ローカル環境ではMinIO、本番環境ではAWS S3を使用できるようにする。動画ファイルをクラウドストレージに保存し、直接配信できる体制を整える。

- **依存**: #8
- **ラベル**: backend, infra

---

## スコープ / 作業項目

### 1. Laravel Filesystem設定
- `config/filesystems.php` にS3ディスク設定を追加
- 環境変数で切り替え可能にする（ローカル: MinIO、本番: AWS S3）

### 2. 環境変数設定
- `.env` ファイルにS3設定を追加:
  - `AWS_ACCESS_KEY_ID`
  - `AWS_SECRET_ACCESS_KEY`
  - `AWS_DEFAULT_REGION`
  - `AWS_BUCKET`
  - `AWS_ENDPOINT`（MinIO用）
  - `AWS_USE_PATH_STYLE_ENDPOINT`（MinIO用）

### 3. S3クライアントライブラリインストール
```bash
composer require league/flysystem-aws-s3-v3
```

### 4. 動画アップロード処理実装
- ファイルをS3にアップロード
- パス構造: `users/{user_id}/original/{video_id}.{ext}`
- original_path をvideosテーブルに保存

### 5. 動画削除処理実装（Issue #13で作成したdestroy()メソッドを更新）
- S3から動画ファイルを削除
- encoded_path と original_path の両方を削除

### 6. エラーハンドリング
- S3アップロード失敗時のリトライロジック（最大3回）
- タイムアウト設定

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] Laravel FilesystemでS3ドライバが設定される（ローカルはMinIO、本番はAWS S3）
- [ ] 動画アップロード時にS3へファイルがアップロードされる（パス: users/{user_id}/original/{video_id}.{ext}）
- [ ] 動画削除時にS3からファイルが削除される
- [ ] アップロード失敗時に適切なエラーハンドリングが実施される（最大3回リトライ）
- [ ] .envでS3設定（バケット名、リージョン、認証情報）が管理される
- [ ] MinIOでローカル開発環境の動作確認が完了

---

## テスト観点

### MinIO設定確認
- [ ] MinIO管理画面（http://localhost:9001）にログイン
- [ ] バケット「laravel」が作成されている（なければ手動作成）
- [ ] `.env` で MinIO 設定が正しく反映されている

### S3アップロード動作確認（後のIssueで実装するアップロード機能で確認）
- [ ] ダミーファイルをS3にアップロード
```php
use Illuminate\Support\Facades\Storage;

$path = Storage::disk('s3')->put('test/test.txt', 'Hello, S3!');
Storage::disk('s3')->exists($path); // true
```
- [ ] MinIO管理画面でファイルが確認できる

### S3削除動作確認
- [ ] Tinkerで動画削除処理をテスト
```php
$video = Video::factory()->create(['original_path' => 'users/1/original/test.mp4']);
// S3にダミーファイルをアップロード
Storage::disk('s3')->put($video->original_path, 'test content');
// 削除処理
$video->delete(); // destroy()メソッド内でS3削除も実行
// S3から削除されたことを確認
Storage::disk('s3')->exists($video->original_path); // false
```

### エラーハンドリング
- [ ] 不正な認証情報で接続を試行 → エラーが適切にハンドリングされる
- [ ] 存在しないバケット名を指定 → エラーが適切にハンドリングされる

### 検証方法
1. MinIOを起動し、管理画面でバケット作成
2. Tinkerでファイルアップロード・削除をテスト
3. MinIO管理画面でファイルが確認できることを確認

---

## 実装例

### config/filesystems.php（更新）
```php
'disks' => [
    // ... 他のディスク設定

    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'), // MinIO用
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false), // MinIO用
    ],
],
```

### .env（ローカル環境 - MinIO設定）
```env
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=laravel
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### .env.example（本番環境用テンプレート）
```env
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=your-bucket-name
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### VideoController.php（destroy()メソッド更新）
```php
use Illuminate\Support\Facades\Storage;

public function destroy(Request $request, int $id): RedirectResponse
{
    $video = Video::findOrFail($id);

    // 所有権チェック
    if ($video->user_id !== $request->user()->id) {
        abort(403, 'この操作を実行する権限がありません');
    }

    // プロフィールで使用中かチェック
    $profile = $request->user()->profile;
    if ($profile->thumbnail_video_id === $video->id || $profile->popup_video_id === $video->id) {
        return redirect()->route('videos.index')
            ->with('error', 'この動画はプロフィールで使用中のため削除できません');
    }

    // S3から動画ファイル削除
    if ($video->encoded_path && Storage::disk('s3')->exists($video->encoded_path)) {
        Storage::disk('s3')->delete($video->encoded_path);
    }

    if ($video->original_path && Storage::disk('s3')->exists($video->original_path)) {
        Storage::disk('s3')->delete($video->original_path);
    }

    // データベースから削除
    $video->delete();

    return redirect()->route('videos.index')
        ->with('success', '動画を削除しました');
}
```

### S3アップロード処理（後のIssueで実装）
```php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// ファイルをS3にアップロード
$extension = $request->file('video')->getClientOriginalExtension();
$videoId = $video->id;
$userId = auth()->id();
$path = "users/{$userId}/original/{$videoId}.{$extension}";

$uploaded = Storage::disk('s3')->putFileAs(
    dirname($path),
    $request->file('video'),
    basename($path)
);

if ($uploaded) {
    $video->update(['original_path' => $path]);
} else {
    throw new \Exception('S3へのアップロードに失敗しました');
}
```

---

## 課題確認事項

- **MinIOバケット名**: `laravel` でOK？別の名前が良い？
- **S3公開設定**: エンコード済み動画（encoded_path）はパブリック読み取り可能にする必要があるが、MinIOでも同様の設定が必要？
- **リトライロジック**: S3アップロード失敗時のリトライは何回？（3回で統一？）
- **タイムアウト**: S3アップロードのタイムアウト時間は？（5分？）

---

## 参考資料

- アーキテクチャ設計書: `docs/02_architecture.md`（S3構成）
- データフロー設計書: `docs/05_data_flow.md`（動画アップロード・削除フロー）
- Laravel Filesystem公式: https://laravel.com/docs/11.x/filesystem
