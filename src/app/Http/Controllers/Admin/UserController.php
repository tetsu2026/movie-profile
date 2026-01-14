<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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
}
