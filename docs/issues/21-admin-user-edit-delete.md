# Issue #21: 管理者ユーザー編集・削除機能実装

## 背景 / 目的

管理者が任意のユーザーのプロフィールを編集・削除できる機能を実装する。ユーザー削除時はソフトデリートを実行し、プロフィールと動画もカスケード削除する。S3の動画ファイルも自動的に削除する。

- **依存**: #20
- **ラベル**: backend, frontend

---

## スコープ / 作業項目

### 1. Admin\UserController に edit/update/destroy メソッド追加
- `edit()`: ユーザー編集フォーム表示
- `update()`: ユーザー情報更新（プロフィール + 権限）
- `destroy()`: ユーザー削除（ソフトデリート + カスケード削除）

### 2. UpdateUserRequest作成
```bash
php artisan make:request UpdateUserRequest
```
- バリデーションルール:
  - `name`: required, string, max:50
  - `biography`: nullable, string, max:1000
  - `role`: required, in:admin,user

### 3. ルート定義
- `routes/web.php`
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users/{id}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
});
```

### 4. Bladeテンプレート作成
- `resources/views/admin/users/edit.blade.php`
- プロフィール編集フォーム + 権限変更ドロップダウン

### 5. ユーザー削除時のS3クリーンアップ
- ユーザーに紐づく全動画のS3ファイルを削除
- カスケード削除でprofiles/videosテーブルも自動削除

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] Admin\UserController@edit/update/destroyが実装される
- [ ] ユーザー編集フォームでプロフィール情報と権限（admin/user）を変更できる
- [ ] ユーザー削除時にソフトデリート（deleted_at）が実行される
- [ ] ユーザー削除時にプロフィールと動画がカスケード削除される
- [ ] ユーザー削除時にS3の動画ファイルも削除される
- [ ] 削除成功時に「ユーザーを削除しました」メッセージが表示される
- [ ] 管理者以外がアクセスすると403エラーが表示される

---

## テスト観点

### ユーザー編集（成功）
- [ ] 管理者でログイン
- [ ] /admin/users にアクセス
- [ ] 任意のユーザーの「編集」ボタンをクリック
- [ ] 編集フォームが表示される
- [ ] 名前を「新しい名前」に変更
- [ ] 権限を「管理者」に変更
- [ ] 保存ボタンをクリック
- [ ] 「ユーザー情報を更新しました」メッセージが表示される
- [ ] ユーザー一覧ページへリダイレクト
- [ ] データベースで変更が反映されている

### ユーザー削除（成功）
- [ ] Tinkerでテストユーザーと動画を作成
```php
$user = User::factory()->create();
$user->profile()->create(['name' => 'テストユーザー']);
$user->videos()->create(['original_filename' => 'test.mp4', 'status' => 'completed', 'encoded_path' => 'users/X/encoded/Y.mp4']);
// S3にダミーファイルをアップロード
Storage::disk('s3')->put('users/X/encoded/Y.mp4', 'test content');
```
- [ ] 管理者でログイン
- [ ] /admin/users でテストユーザーの「削除」ボタンをクリック
- [ ] 確認ダイアログで「OK」をクリック
- [ ] 「ユーザーを削除しました」メッセージが表示される
- [ ] データベースで `SELECT * FROM users WHERE id = ? AND deleted_at IS NOT NULL;` を確認（ソフトデリート）
- [ ] profilesテーブルからプロフィールが削除されている（カスケード削除）
- [ ] videosテーブルから動画が削除されている（カスケード削除）
- [ ] S3から動画ファイルが削除されている

### 権限チェック
- [ ] 一般ユーザーでログイン
- [ ] /admin/users/{id}/edit にアクセス
- [ ] 403エラーが表示される

### 検証方法
1. Tinkerでテストユーザーを作成
2. 管理者でログインして編集・削除を実行
3. データベースとS3で変更が反映されていることを確認

---

## 実装例

### app/Http/Controllers/Admin/UserController.php（edit/update/destroyメソッド追加）
```php
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;

public function edit(int $id): View
{
    $user = User::with('profile')->findOrFail($id);

    // completed状態の動画を取得（プロフィール編集用）
    $videos = $user->videos()
        ->where('status', 'completed')
        ->orderBy('created_at', 'desc')
        ->get();

    return view('admin.users.edit', [
        'user' => $user,
        'profile' => $user->profile,
        'videos' => $videos,
    ]);
}

public function update(UpdateUserRequest $request, int $id): RedirectResponse
{
    $user = User::findOrFail($id);
    $profile = $user->profile;

    // プロフィール更新
    $profile->update([
        'name' => $request->name,
        'biography' => $request->biography,
        'thumbnail_video_id' => $request->thumbnail_video_id,
        'popup_video_id' => $request->popup_video_id,
    ]);

    // 権限更新
    $user->update([
        'role' => $request->role,
    ]);

    return redirect()->route('admin.users.index')
        ->with('success', 'ユーザー情報を更新しました');
}

public function destroy(int $id): RedirectResponse
{
    $user = User::findOrFail($id);

    // S3から全動画ファイルを削除
    foreach ($user->videos as $video) {
        if ($video->encoded_path && Storage::disk('s3')->exists($video->encoded_path)) {
            Storage::disk('s3')->delete($video->encoded_path);
        }
        if ($video->original_path && Storage::disk('s3')->exists($video->original_path)) {
            Storage::disk('s3')->delete($video->original_path);
        }
    }

    // ソフトデリート（profiles/videosは外部キー制約でカスケード削除）
    $user->delete();

    return redirect()->route('admin.users.index')
        ->with('success', 'ユーザーを削除しました');
}
```

### app/Http/Requests/UpdateUserRequest.php
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 管理者のみ使用（adminミドルウェアで保護済み）
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'biography' => 'nullable|string|max:1000',
            'thumbnail_video_id' => 'nullable|exists:videos,id',
            'popup_video_id' => 'nullable|exists:videos,id',
            'role' => 'required|in:admin,user',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '名前は必須です',
            'name.max' => '名前は50文字以内で入力してください',
            'biography.max' => '経歴は1000文字以内で入力してください',
            'role.required' => '権限を選択してください',
            'role.in' => '権限はadminまたはuserから選択してください',
        ];
    }
}
```

### resources/views/admin/users/edit.blade.php
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ユーザー編集: {{ $user->email }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                        @csrf
                        @method('PUT')

                        {{-- 名前 --}}
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">名前</label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $profile->name) }}"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 経歴 --}}
                        <div class="mb-4">
                            <label for="biography" class="block text-sm font-medium text-gray-700">経歴</label>
                            <textarea
                                name="biography"
                                id="biography"
                                rows="6"
                                class="mt-1 block w-full rounded-md border-gray-300"
                            >{{ old('biography', $profile->biography) }}</textarea>
                            @error('biography')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- サムネイル用動画 --}}
                        <div class="mb-4">
                            <label for="thumbnail_video_id" class="block text-sm font-medium text-gray-700">サムネイル用動画</label>
                            <select name="thumbnail_video_id" id="thumbnail_video_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">選択しない</option>
                                @foreach($videos as $video)
                                    <option value="{{ $video->id }}" {{ old('thumbnail_video_id', $profile->thumbnail_video_id) == $video->id ? 'selected' : '' }}>
                                        {{ $video->original_filename }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- ポップアップ用動画 --}}
                        <div class="mb-4">
                            <label for="popup_video_id" class="block text-sm font-medium text-gray-700">ポップアップ用動画</label>
                            <select name="popup_video_id" id="popup_video_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">選択しない</option>
                                @foreach($videos as $video)
                                    <option value="{{ $video->id }}" {{ old('popup_video_id', $profile->popup_video_id) == $video->id ? 'selected' : '' }}>
                                        {{ $video->original_filename }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 権限 --}}
                        <div class="mb-4">
                            <label for="role" class="block text-sm font-medium text-gray-700">権限</label>
                            <select name="role" id="role" required class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>一般ユーザー</option>
                                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>管理者</option>
                            </select>
                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-4">
                            <a href="{{ route('admin.users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                キャンセル
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                保存
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 課題確認事項

- **自分自身の削除**: 管理者が自分自身を削除できないようにする？（誤操作防止）
- **最後の管理者**: 管理者が1人しかいない場合、削除や権限変更を禁止する？
- **物理削除**: 30日後に自動的に物理削除するバッチ処理を実装する？（Issue #22で検討）

---

## 参考資料

- サイトマップ: `docs/04_sitemap.md`（管理者: ユーザー詳細編集）
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
