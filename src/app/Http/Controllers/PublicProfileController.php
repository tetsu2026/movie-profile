<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    /**
     * キャッシュ有効期間（分）
     */
    private const CACHE_TTL = 5;

    /**
     * 公開プロフィールページを表示
     */
    public function show(int $id): View
    {
        // キャッシュを利用してプロフィール情報を取得（5分間キャッシュ）
        $user = Cache::remember(
            "user_profile_{$id}",
            now()->addMinutes(self::CACHE_TTL),
            function () use ($id) {
                // プロフィールと動画情報をEager Loadingで取得（N+1問題対策）
                return User::with(['profile', 'profile.thumbnailVideo'])->findOrFail($id);
            }
        );

        return view('users.show', [
            'user' => $user,
            'profile' => $user->profile,
        ]);
    }
}
