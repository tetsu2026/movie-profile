<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * ダッシュボードを表示
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile;

        // 動画数のカウント（ステータス別）
        $videoStats = [
            'total' => $user->videos()->count(),
            'completed' => $user->videos()->where('status', 'completed')->count(),
            'encoding' => $user->videos()->where('status', 'encoding')->count(),
            'failed' => $user->videos()->where('status', 'failed')->count(),
        ];

        // プロフィール完成度
        $completionStatus = [
            'name' => !empty($profile->name),
            'biography' => !empty($profile->biography),
            'thumbnail_video' => !empty($profile->thumbnail_video_id),
        ];

        return view('dashboard', [
            'profile' => $profile,
            'videoStats' => $videoStats,
            'completionStatus' => $completionStatus,
        ]);
    }
}
