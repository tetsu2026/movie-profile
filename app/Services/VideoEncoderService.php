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
     * 動画をエンコードする（1回の試行）
     *
     * Phase 7 以降、リトライは Laravel Queue の $tries に任せる。
     * 例外が発生した場合はそのまま投げ、Job 側でエラーメッセージを保存する。
     *
     * @param Video $video
     * @return bool
     * @throws \Exception
     */
    public function encode(Video $video): bool
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
