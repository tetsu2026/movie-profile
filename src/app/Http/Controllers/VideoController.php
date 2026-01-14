<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VideoController extends Controller
{
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
