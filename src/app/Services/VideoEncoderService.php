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
            $extension = pathinfo($video->original_path, PATHINFO_EXTENSION);
            $tmpInputPath = sys_get_temp_dir() . '/video_' . $video->id . '_input.' . $extension;
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
                '-y', // 既存ファイルを上書き
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
            if (file_exists($tmpInputPath)) {
                unlink($tmpInputPath);
            }
            if (file_exists($tmpOutputPath)) {
                unlink($tmpOutputPath);
            }

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
