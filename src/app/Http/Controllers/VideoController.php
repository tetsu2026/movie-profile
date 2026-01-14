<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Models\Profile;
use App\Models\Video;
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
                'status' => 'encoding', // Issue #17でエンコード処理を実装予定
            ]);

            // TODO: Issue #17でエンコード処理を実装

            return redirect()->route('videos.index')
                ->with('success', '動画のアップロードが完了しました');

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

        // プロフィールで使用中の動画ID
        $usedVideoIds = [];
        $profile = $request->user()->profile;
        if ($profile && $profile->thumbnail_video_id) {
            $usedVideoIds[] = $profile->thumbnail_video_id;
        }

        return view('videos.index', [
            'videos' => $videos,
            'usedVideoIds' => $usedVideoIds,
        ]);
    }

    /**
     * 動画を削除
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $video = Video::findOrFail($id);

        // 所有権チェック
        if ($video->user_id !== $request->user()->id) {
            abort(403, '他のユーザーの動画は削除できません');
        }

        // プロフィールで使用中かチェック
        $isUsed = Profile::where('thumbnail_video_id', $video->id)->exists();

        if ($isUsed) {
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
}
