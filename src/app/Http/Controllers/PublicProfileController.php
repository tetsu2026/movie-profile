<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    /**
     * 公開プロフィールページを表示
     */
    public function show(int $id): View
    {
        // プロフィールと動画情報をEager Loadingで取得（N+1問題対策）
        $user = User::with(['profile', 'profile.thumbnailVideo'])->findOrFail($id);

        return view('users.show', [
            'user' => $user,
            'profile' => $user->profile,
        ]);
    }
}
