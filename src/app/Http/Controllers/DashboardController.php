<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // 動画数のカウント（ステータス別）- 1クエリで全て取得
        $statusCounts = $user->videos()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $videoStats = [
            'total' => array_sum($statusCounts),
            'completed' => $statusCounts['completed'] ?? 0,
            'encoding' => $statusCounts['encoding'] ?? 0,
            'failed' => $statusCounts['failed'] ?? 0,
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
