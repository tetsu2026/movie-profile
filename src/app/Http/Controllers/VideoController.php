<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        return view('videos.index', [
            'videos' => $videos,
        ]);
    }
}
