<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Models\Profile;
use App\Models\Video;
use App\Services\VideoEncoderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VideoController extends Controller
{
    /**
     * 動画アップロードフォームを表示
     */
    public function create(): View
    {
        return view('videos.create');
    }

    /**
     * 動画をアップロード
     */
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

        } catch (\Exception $e) {
            // エラー時は動画レコードを削除
            $video->delete();

            return redirect()->route('videos.create')
                ->with('error', '動画のアップロードに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 動画一覧を表示
     */
    public function index(Request $request): View
    {
        // 認証ユーザーの動画を新しい順に取得
        $videos = $request->user()
            ->videos()
            ->orderBy('created_at', 'desc')
            ->get();

        // プロフィールで使用中の動画IDを取得
        $profile = $request->user()->profile;
        $thumbnailVideoId = $profile?->thumbnail_video_id;
        $popupVideoId = $profile?->popup_video_id;

        return view('videos.index', [
            'videos' => $videos,
            'thumbnailVideoId' => $thumbnailVideoId,
            'popupVideoId' => $popupVideoId,
        ]);
    }

    /**
     * 動画ステータス一覧をJSON形式で返す（ポーリング用）
     */
    public function statuses(Request $request): JsonResponse
    {
        $videos = $request->user()
            ->videos()
            ->select('id', 'status', 'retry_count', 'error_message')
            ->get()
            ->map(fn ($video) => [
                'id' => $video->id,
                'status' => $video->status,
                'retry_count' => $video->retry_count,
                'error_message' => $video->error_message,
            ]);

        return response()->json($videos);
    }

    /**
     * 動画を削除
     *
     * @param Request $request
     * @param int $id 動画ID
     * @return RedirectResponse
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $video = Video::findOrFail($id);

        // 所有権チェック
        if ($video->user_id !== $request->user()->id) {
            abort(403, '他のユーザーの動画は削除できません');
        }

        // プロフィールで使用中かチェック（サムネイルとポップアップ両方）
        $profile = $request->user()->profile;
        $isThumbnail = $profile && $profile->thumbnail_video_id === $video->id;
        $isPopup = $profile && $profile->popup_video_id === $video->id;
        $isUsed = $isThumbnail || $isPopup;

        if ($isUsed) {
            // 強制削除フラグがない場合はエラー
            if (!$request->boolean('force_delete')) {
                return redirect()->route('videos.index')
                    ->with('error', 'この動画はプロフィールで使用中のため削除できません');
            }

            // プロフィールからの参照を解除
            $updateData = [];
            if ($isThumbnail) {
                $updateData['thumbnail_video_id'] = null;
            }
            if ($isPopup) {
                $updateData['popup_video_id'] = null;
            }
            $profile->update($updateData);
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

        // 削除メッセージを生成
        if ($isThumbnail && $isPopup) {
            $message = '動画を削除し、プロフィールのサムネイル動画とポップアップ動画の設定を解除しました';
        } elseif ($isThumbnail) {
            $message = '動画を削除し、プロフィールのサムネイル動画の設定を解除しました';
        } elseif ($isPopup) {
            $message = '動画を削除し、プロフィールのポップアップ動画の設定を解除しました';
        } else {
            $message = '動画を削除しました';
        }

        return redirect()->route('videos.index')
            ->with('success', $message);
    }
}
