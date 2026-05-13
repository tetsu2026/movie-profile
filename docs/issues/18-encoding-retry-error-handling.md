# Issue #18: エンコードリトライ・エラーハンドリング実装

> **⚠️ Phase 7 で置換予定**: 本 issue で実装した独自リトライロジック（`retry_count` カラム＋3 回ループ）は、Phase 7 の Issue #30 で **Laravel Queue 標準の `$tries=3` と `failed_jobs` テーブル** に置き換える。エラーメッセージ保存ロジックは `EncodeVideoJob::failed()` メソッドへ移行する。詳細: `docs/plans/ecs_ecr_migration_phase1.md`

## 背景 / 目的

エンコード失敗時のリトライロジック（最大3回）とエラーメッセージ保存を実装し、エンコード処理の堅牢性を高める。失敗した動画には適切なエラーメッセージを表示し、ユーザーに次の行動を促す。

- **依存**: #17
- **後続**: #30（Queue Job 化で Laravel 標準機能に統合）
- **ラベル**: backend

---

## スコープ / 作業項目

### 1. VideoEncoderService の更新
- エンコード失敗時にretry_countをインクリメント
- retry_count < 3の場合は自動的に再エンコードを実行
- retry_count >= 3の場合はstatus='failed'に更新
- error_messageにエラー内容を保存

### 2. エラーハンドリング強化
- FFmpegエラーのキャッチと分類
- タイムアウトエラーの検出
- 不正フォーマットエラーの検出

### 3. 動画一覧ページの更新
- failed状態の動画にエラーメッセージを表示
- 「別の動画をお試しください」などの推奨アクション表示

### 4. ログ記録
- エンコード開始・成功・失敗・リトライをログに記録
- CloudWatch連携準備（Issue #22で実装）

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] エンコード失敗時にretry_countがインクリメントされる
- [ ] retry_count < 3の場合は自動的に再エンコードが実行される
- [ ] retry_count >= 3の場合はstatus='failed'に更新される
- [ ] エンコード失敗時にerror_messageにエラー内容が保存される
- [ ] エンコード失敗時にユーザーに「エンコード失敗」通知が表示される
- [ ] 失敗した動画は動画一覧ページで赤色のバッジ・エラーメッセージが表示される
- [ ] エンコード開始・成功・失敗がログに記録される

---

## テスト観点

### エンコード成功（通常）
- [ ] 有効な動画をアップロード
- [ ] エンコードが成功し、status='completed'になる
- [ ] retry_count = 0のまま

### エンコード失敗（1回目リトライ成功）
- [ ] 意図的にエンコードを失敗させる（FFmpegコマンドを一時的に変更）
- [ ] retry_count = 1になる
- [ ] 自動的に再エンコードが実行される
- [ ] 2回目のエンコードが成功し、status='completed'になる

### エンコード失敗（3回リトライ後にfailed）
- [ ] 意図的にエンコードを3回失敗させる
- [ ] retry_count = 3になる
- [ ] status='failed'になる
- [ ] error_messageにエラー内容が保存される
- [ ] 動画一覧ページで赤色バッジ・エラーメッセージが表示される

### タイムアウトエラー
- [ ] 意図的にエンコード処理を5分以上かかるように設定
- [ ] タイムアウトエラーが発生し、リトライが実行される
- [ ] 3回タイムアウト後、status='failed'になる

### ログ確認
- [ ] `storage/logs/laravel.log` でエンコード開始・成功・失敗・リトライが記録されている
```bash
tail -f storage/logs/laravel.log | grep "動画エンコード"
```

### 検証方法
1. 意図的にエンコードを失敗させる設定でテスト
2. retry_countとstatusの変化を確認
3. ログでリトライ処理が記録されていることを確認

---

## 実装例

### VideoEncoderService.php（更新）
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
     * 動画をエンコードする（リトライロジック付き）
     *
     * @param Video $video
     * @return bool
     */
    public function encodeWithRetry(Video $video): bool
    {
        $maxRetries = 3;

        while ($video->retry_count < $maxRetries) {
            Log::info("動画エンコード開始 (試行 {$video->retry_count}/{$maxRetries}): {$video->id}");

            try {
                $success = $this->encode($video);

                if ($success) {
                    Log::info("動画エンコード成功: {$video->id}");
                    return true;
                }

                // エンコード失敗時
                $video->increment('retry_count');
                Log::warning("動画エンコード失敗 (リトライ {$video->retry_count}/{$maxRetries}): {$video->id}");

            } catch (\Exception $e) {
                $video->increment('retry_count');
                $errorMessage = $e->getMessage();

                Log::error("動画エンコードエラー (リトライ {$video->retry_count}/{$maxRetries}): {$video->id}, エラー: {$errorMessage}");

                // エラーメッセージを保存
                $video->update(['error_message' => $errorMessage]);
            }
        }

        // 3回失敗後
        $video->update(['status' => 'failed']);
        Log::error("動画エンコード最終失敗: {$video->id}");

        return false;
    }

    /**
     * 動画をエンコードする（1回の試行）
     *
     * @param Video $video
     * @return bool
     * @throws \Exception
     */
    private function encode(Video $video): bool
    {
        // S3から元動画をダウンロード
        $tmpInputPath = sys_get_temp_dir() . '/video_' . $video->id . '_input.' . pathinfo($video->original_path, PATHINFO_EXTENSION);
        $tmpOutputPath = sys_get_temp_dir() . '/video_' . $video->id . '_output.mp4';

        try {
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
                // FFmpegエラーを取得
                $errorOutput = $process->getErrorOutput();
                throw new \Exception("FFmpegエラー: {$errorOutput}");
            }

            // エンコード済み動画をS3にアップロード
            $encodedPath = "users/{$video->user_id}/encoded/{$video->id}.mp4";
            Storage::disk('s3')->put($encodedPath, file_get_contents($tmpOutputPath));

            // 動画情報を更新
            $video->update([
                'encoded_path' => $encodedPath,
                'status' => 'completed',
                'error_message' => null, // エラーメッセージをクリア
            ]);

            // 元動画をS3から削除
            if ($video->original_path && Storage::disk('s3')->exists($video->original_path)) {
                Storage::disk('s3')->delete($video->original_path);
                $video->update(['original_path' => null]);
            }

            return true;

        } finally {
            // 一時ファイル削除
            if (isset($tmpInputPath) && file_exists($tmpInputPath)) {
                unlink($tmpInputPath);
            }
            if (isset($tmpOutputPath) && file_exists($tmpOutputPath)) {
                unlink($tmpOutputPath);
            }
        }
    }
}
```

### VideoController.php（store()メソッド更新）
```php
// エンコード処理を実行（リトライロジック付き）
$encoderService = new VideoEncoderService();
$success = $encoderService->encodeWithRetry($video);

if ($success) {
    return redirect()->route('videos.index')
        ->with('success', '動画のアップロードとエンコードが完了しました');
} else {
    return redirect()->route('videos.index')
        ->with('error', '動画のエンコードに失敗しました。別の動画をお試しください。');
}
```

### videos/index.blade.php（エラーメッセージ表示追加）
```blade
@foreach($videos as $video)
    <tr>
        <td class="px-6 py-4 whitespace-nowrap">{{ $video->original_filename }}</td>
        <td class="px-6 py-4 whitespace-nowrap">
            @if($video->status === 'uploading')
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                    アップロード中
                </span>
            @elseif($video->status === 'encoding')
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                    エンコード中 (試行 {{ $video->retry_count }}/3)
                </span>
            @elseif($video->status === 'completed')
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                    使用可能
                </span>
            @elseif($video->status === 'failed')
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                    エンコード失敗
                </span>
                @if($video->error_message)
                    <p class="text-sm text-red-600 mt-1">{{ Str::limit($video->error_message, 100) }}</p>
                @endif
                <p class="text-sm text-gray-600 mt-1">別の動画をお試しください</p>
            @endif
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ $video->created_at->format('Y-m-d H:i') }}
        </td>
        {{-- 削除ボタン等 --}}
    </tr>
@endforeach
```

---

## 課題確認事項

- **リトライ間隔**: 即座にリトライでOK？それとも数秒待機してからリトライ？
- **手動リトライ機能**: 失敗した動画を手動で再エンコードするボタンを追加する？（Phase 2で検討）
- **エラー分類**: FFmpegエラーを詳細に分類する？（フォーマットエラー、タイムアウト、リソース不足など）
- **通知機能**: エンコード失敗時にメール通知を送る？（Phase 2で検討）

---

## 参考資料

- データフロー設計書: `docs/05_data_flow.md`（動画エンコードフロー）
- 状態遷移設計書: `docs/08_state_machine_video.md`（エンコード状態遷移）
- データベース設計書: `docs/03_database.md`（retry_count、error_messageカラム）
