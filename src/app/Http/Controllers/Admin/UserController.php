<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * ユーザー一覧を表示
     */
    public function index(): View
    {
        // Eager Loadingでプロフィール情報を取得（N+1問題対策）
        $users = User::with('profile')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    /**
     * ユーザー編集フォームを表示
     */
    public function edit(int $id): View
    {
        $user = User::with('profile')->findOrFail($id);

        // completed状態の動画を取得
        $videos = $user->videos()
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.users.edit', [
            'user' => $user,
            'profile' => $user->profile,
            'videos' => $videos,
        ]);
    }

    /**
     * ユーザー情報を更新
     */
    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $profile = $user->profile;

        // プロフィール更新
        $profile->update([
            'name' => $request->name,
            'biography' => $request->biography,
            'thumbnail_video_id' => $request->thumbnail_video_id,
        ]);

        // 権限更新
        $user->update([
            'role' => $request->role,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'ユーザー情報を更新しました');
    }

    /**
     * ユーザーを削除
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // S3から全動画ファイルを削除
        foreach ($user->videos as $video) {
            if ($video->encoded_path && Storage::disk('s3')->exists($video->encoded_path)) {
                Storage::disk('s3')->delete($video->encoded_path);
            }
            if ($video->original_path && Storage::disk('s3')->exists($video->original_path)) {
                Storage::disk('s3')->delete($video->original_path);
            }
        }

        // ソフトデリート（profiles/videosは外部キー制約でカスケード削除）
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'ユーザーを削除しました');
    }
}
