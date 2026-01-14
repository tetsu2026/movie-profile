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
        // プロフィールとの関連データをEager Loadingで取得
        $user = User::with('profile')->findOrFail($id);

        return view('users.show', [
            'user' => $user,
            'profile' => $user->profile,
        ]);
    }
}
