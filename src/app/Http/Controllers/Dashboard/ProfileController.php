<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * プロフィール編集フォームを表示
     */
    public function edit(Request $request): View
    {
        $profile = $request->user()->profile;

        // エンコード完了済みの動画のみ取得
        $completedVideos = $request->user()
            ->videos()
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard.profile.edit', [
            'profile' => $profile,
            'completedVideos' => $completedVideos,
        ]);
    }

    /**
     * プロフィールを更新
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        $profile->update($request->validated());

        // プロフィールキャッシュをクリア
        Cache::forget("user_profile_{$user->id}");

        return redirect()->route('dashboard')
            ->with('success', 'プロフィールを更新しました');
    }
}
