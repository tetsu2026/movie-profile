# Issue #17: 動画エンコード機能実装（FFmpeg同期処理）

## 背景 / 目的

FFmpegを使用した動画エンコード処理を実装する。Phase 1は同期処理で最小実装し、mp4（H.264/AAC）形式にエンコードしてS3にアップロードする。エンコード中はユーザーに処理中を表示する。

- **依存**: #16
- **ラベル**: backend

---

## スコープ / 作業項目

### 1. VideoEncoderServiceクラス作成
```bash
php artisan make:service VideoEncoderService
```
- FFmpegコマンド実行
- エンコード処理:
  - 入力: S3の元動画（original_path）
  - 出力: mp4（H.264/AAC、最大1080p）
- エンコード済み動画をS3にアップロード
- 元動画をS3から削除

### 2. VideoController の store() メソッド更新
- S3アップロード後、VideoEncoderServiceを呼び出してエンコード実行
- エンコード成功時:
  - encoded_path にS3パス保存
  - status更新: encoding → completed
- エンコード失敗時:
  - 次のIssue（#18）でリトライロジック実装

### 3. FFmpegコマンド設定
- 出力形式: mp4
- 動画コーデック: H.264
- 音声コーデック: AAC
- 最大解像度: 1080p
- タイムアウト: 5分

### 4. 一時ファイル管理
- S3から元動画をダウンロード（EC2のtmpディレクトリ）
- エンコード実行
- エンコード済み動画をS3にアップロード
- 一時ファイルを削除

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] VideoEncoderServiceクラスが作成される
- [ ] FFmpegで動画をmp4（H.264/AAC）にエンコードする処理が実装される
- [ ] エンコード成功時にencoded_pathへS3アップロードされる
- [ ] エンコード成功時にoriginal_pathがS3から削除される
- [ ] エンコード成功時にstatus='completed'に更新される
- [ ] エンコードタイムアウト（5分）が設定される
- [ ] エンコード中はユーザーに「エンコード中」と表示される

---

## テスト観点

### エンコード処理（成功）
- [ ] 有効な動画ファイル（mp4、30秒）をアップロード
- [ ] 動画一覧ページでステータスが「エンコード中」になる
- [ ] エンコードが完了するまで待機（数秒〜数分）
- [ ] ステータスが「使用可能」（completed）に変わる
- [ ] データベースで `SELECT * FROM videos WHERE id = ?;` を確認
  - status = 'completed'
  - encoded_path にS3パスが保存されている
  - original_path が NULL（削除済み）
- [ ] MinIO管理画面でencoded_pathのファイルが確認できる
- [ ] MinIO管理画面でoriginal_pathのファイルが削除されている

### FFmpegコマンド確認
- [ ] エンコードされた動画がmp4形式になっている
- [ ] 動画コーデックがH.264になっている
- [ ] 音声コーデックがAACになっている
- [ ] 解像度が1080p以下になっている

### 一時ファイル管理
- [ ] エンコード完了後、tmpディレクトリの一時ファイルが削除されている
```bash
ls /tmp | grep video
```

### 検証方法
1. 動画をアップロード
2. ログで

 エンコード処理の進行を確認
```bash
tail -f storage/logs/laravel.log
```
3. エンコード完了後、MinIOとデータベースを確認

---

## 実装例

### app/Services/VideoEncoderService.php
```php
<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class VideoEncoderService
{
    /**
     * 動画をエンコードする
     *
     * @param Video $video
     * @return bool
     */
    public function encode(Video $video): bool
    {
        try {
            Log::info("動画エンコード開始: {$video->id}");

            // S3から元動画をダウンロード
            $tmpInputPath = sys_get_temp_dir() . '/video_' . $video->id . '_input.' . pathinfo($video->original_path, PATHINFO_EXTENSION);
            $tmpOutputPath = sys_get_temp_dir() . '/video_' . $video->id . '_output.mp4';

            $contents = Storage::disk('s3')->get($video->original_path);
            file_put_contents($tmpInputPath, $contents);

            // FFmpegコマンド実行
            $command = [
                'ffmpeg',
                '-i', $tmpInputPath,
                '-c:v', 'libx264',
                '-c:a', 'aac',
                '-vf', 'scale=-2:min(ih\,1080)',
                '-preset', 'medium',
                '-crf', '23',
                $tmpOutputPath
            ];

            $process = new Process($command);
            $process->setTimeout(300); // 5分タイムアウト
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // エンコード済み動画をS3にアップロード
            $encodedPath = "users/{$video->user_id}/encoded/{$video->id}.mp4";
            Storage::disk('s3')->put($encodedPath, file_get_contents($tmpOutputPath));

            // 動画情報を更新
            $video->update([
                'encoded_path' => $encodedPath,
                'status' => 'completed',
            ]);

            // 元動画をS3から削除
            if ($video->original_path && Storage::disk('s3')->exists($video->original_path)) {
                Storage::disk('s3')->delete($video->original_path);
                $video->update(['original_path' => null]);
            }

            // 一時ファイル削除
            unlink($tmpInputPath);
            unlink($tmpOutputPath);

            Log::info("動画エンコード完了: {$video->id}");

            return true;

        } catch (\Exception $e) {
            Log::error("動画エンコード失敗: {$video->id}, エラー: {$e->getMessage()}");

            // 一時ファイル削除（存在する場合）
            if (isset($tmpInputPath) && file_exists($tmpInputPath)) {
                unlink($tmpInputPath);
            }
            if (isset($tmpOutputPath) && file_exists($tmpOutputPath)) {
                unlink($tmpOutputPath);
            }

            return false;
        }
    }
}
```

### VideoController.php（store()メソッド更新）
```php
use App\Services\VideoEncoderService;

public function store(StoreVideoRequest $request): RedirectResponse
{
    $user = $request->user();

    // 動画レコード作成（status: uploading）
    $video = $user->videos()->create([
        'original_filename' => $request->file('video')->getClientOriginalName(),
        'status' => 'uploading',
    ]);

    try {
        // S3にアップロード
        $extension = $request->file('video')->getClientOriginalExtension();
        $path = "users/{$user->id}/original/{$video->id}.{$extension}";

        $uploaded = Storage::disk('s3')->putFileAs(
            dirname($path),
            $request->file('video'),
            basename($path)
        );

        if (!$uploaded) {
            throw new \Exception('S3へのアップロードに失敗しました');
        }

        // 動画情報を更新
        $video->update([
            'original_path' => $path,
            'file_size' => $request->file('video')->getSize(),
            'status' => 'encoding',
        ]);

        // エンコード処理を実行（同期処理）
        $encoderService = new VideoEncoderService();
        $success = $encoderService->encode($video);

        if ($success) {
            return redirect()->route('videos.index')
                ->with('success', '動画のアップロードとエンコードが完了しました');
        } else {
            // エンコード失敗時は次のIssueでリトライロジック実装
            return redirect()->route('videos.index')
                ->with('error', '動画のエンコードに失敗しました');
        }

    } catch (\Exception $e) {
        // エラー時は動画レコードを削除
        $video->delete();

        return redirect()->route('videos.create')
            ->with('error', '動画のアップロードに失敗しました: ' . $e->getMessage());
    }
}
```

### Dockerfile（FFmpegインストール）
Issue #1で作成したDockerfileにFFmpegを追加:
```dockerfile
RUN apt-get update && apt-get install -y ffmpeg
```

---

## 課題確認事項

- **同期処理の問題**: エンコード中にユーザーが待たされる問題（Phase 2でキュー化を検討）
- **複数ユーザーの同時エンコード**: 同時に複数ユーザーがアップロードした場合の挙動（EC2リソース不足の可能性）
- **FFmpegプリセット**: `medium` プリセットでOK？速度重視なら `fast`、品質重視なら `slow`
- **CRF値**: 23でOK？（低いほど高品質だが容量増加、18-28が一般的）

---

## 参考資料

- データフロー設計書: `docs/05_data_flow.md`（動画エンコードフロー）
- 状態遷移設計書: `docs/08_state_machine_video.md`（エンコード状態遷移）
- FFmpeg公式ドキュメント: https://ffmpeg.org/ffmpeg.html
