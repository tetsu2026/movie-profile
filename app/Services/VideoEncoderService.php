<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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
            Log::info("動画エンコード開始 (試行 " . ($video->retry_count + 1) . "/{$maxRetries}): {$video->id}");

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
        $extension = pathinfo($video->original_path, PATHINFO_EXTENSION);
        $tmpInputPath = sys_get_temp_dir() . '/video_' . $video->id . '_input.' . $extension;
        $tmpOutputPath = sys_get_temp_dir() . '/video_' . $video->id . '_output.mp4';

        try {
            $contents = Storage::disk('s3')->get($video->original_path);
            file_put_contents($tmpInputPath, $contents);

            // FFmpegコマンド実行
            // FFmpegの絶対パスを使用（環境によってPATHが異なるため）
            $ffmpegPath = env('FFMPEG_PATH', '/usr/local/bin/ffmpeg');
            $command = [
                $ffmpegPath,
                '-i', $tmpInputPath,
                '-c:v', 'libx264',
                '-c:a', 'aac',
                '-vf', 'scale=-2:min(ih\,1080)',
                '-preset', 'medium',
                '-crf', '23',
                '-y', // 既存ファイルを上書き
                $tmpOutputPath
            ];

            $process = new Process($command);
            $process->setTimeout(300); // 5分タイムアウト
            $process->run();

            if (!$process->isSuccessful()) {
                // FFmpegエラーを取得
                $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
                $exitCode = $process->getExitCode();
                $commandLine = $process->getCommandLine();
                throw new \Exception("FFmpegエラー (exit code: {$exitCode}): {$errorOutput} [command: {$commandLine}]");
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
