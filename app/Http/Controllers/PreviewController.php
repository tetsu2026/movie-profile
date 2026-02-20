<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PreviewController extends Controller
{
    /**
     * プロフィールのプレビューを表示
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile;

        return view('preview', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }
}
