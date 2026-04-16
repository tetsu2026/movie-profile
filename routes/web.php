<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dashboard\ProfileController as DashboardProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

// トップページ
Route::get('/', [HomeController::class, 'index'])->name('home');

// 公開プロフィールページ
Route::get('/users/{id}', [PublicProfileController::class, 'show'])->name('users.show');

// ダッシュボード（認証必須）
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
Route::get('/dashboard/video-stats', [DashboardController::class, 'videoStats'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.video-stats');

Route::middleware(['auth', 'verified'])->group(function () {
    // アカウント設定（Breeze）
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // プロフィール情報編集
    Route::get('/dashboard/profile/edit', [DashboardProfileController::class, 'edit'])->name('dashboard.profile.edit');
    Route::put('/dashboard/profile', [DashboardProfileController::class, 'update'])->name('dashboard.profile.update');

    // プレビュー
    Route::get('/dashboard/preview', [PreviewController::class, 'show'])->name('preview');

    // 動画管理
    Route::get('/dashboard/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/dashboard/videos/statuses', [VideoController::class, 'statuses'])->name('videos.statuses');
    Route::get('/dashboard/videos/upload', [VideoController::class, 'create'])->name('videos.create');
    Route::post('/dashboard/videos', [VideoController::class, 'store'])->name('videos.store');
    Route::delete('/dashboard/videos/{id}', [VideoController::class, 'destroy'])->name('videos.destroy');
});

// チャットボット（認証必須）
Route::middleware('auth')->prefix('chatbot')->name('chatbot.')->group(function () {
    Route::post('/message', [ChatbotController::class, 'message'])
        ->middleware('throttle:10,1')
        ->name('message');
    Route::get('/history', [ChatbotController::class, 'history'])->name('history');
});

// 管理者専用ページ（管理者のみ）
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});

require __DIR__.'/auth.php';
