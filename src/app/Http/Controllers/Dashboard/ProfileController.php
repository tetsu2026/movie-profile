<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * プロフィール編集フォームを表示
     */
    public function edit(Request $request): View
    {
        $profile = $request->user()->profile;

        return view('dashboard.profile.edit', [
            'profile' => $profile,
        ]);
    }

    /**
     * プロフィールを更新
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $profile = $request->user()->profile;

        $profile->update($request->validated());

        return redirect()->route('dashboard')
            ->with('success', 'プロフィールを更新しました');
    }
}
