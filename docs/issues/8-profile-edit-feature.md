# Issue #8: プロフィール編集機能実装

## 背景 / 目的

プロフィール編集フォームとバリデーション、更新処理を実装する。ユーザーが自分の名前・経歴を編集できるようにし、動画選択機能は後のIssue（#14）で実装する。

- **依存**: #7
- **ラベル**: backend, frontend

---

## スコープ / 作業項目

### 1. ProfileController作成
```bash
php artisan make:controller ProfileController
```
- `edit()` メソッド: プロフィール編集フォーム表示
- `update()` メソッド: プロフィール更新処理

### 2. FormRequest作成
```bash
php artisan make:request UpdateProfileRequest
```
- バリデーションルール:
  - `name`: required, string, max:50
  - `biography`: nullable, string, max:1000
  - `thumbnail_video_id`: nullable, exists:videos,id（この段階では未実装でOK）
  - `popup_video_id`: nullable, exists:videos,id（この段階では未実装でOK）
- エラーメッセージを日本語化

### 3. ルート定義
- `routes/web.php`
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/dashboard/profile', [ProfileController::class, 'update'])->name('profile.update');
});
```

### 4. Bladeテンプレート作成
- `resources/views/profile/edit.blade.php`
- フォーム項目:
  - 名前（text input、必須、50文字以内）
  - 経歴（textarea、任意、1000文字以内）
  - 保存ボタン
  - キャンセルボタン（ダッシュボードへ戻る）
- 動画選択ドロップダウンは後のIssue (#14) で追加

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] ProfileController@edit/updateが実装される
- [ ] UpdateProfileRequestでバリデーション（名前必須50文字以内、経歴1000文字以内）が動作
- [ ] プロフィール編集フォームで現在の情報が表示され、更新できる
- [ ] 動画選択ドロップダウンは後のIssueで実装するため、この段階では空でOK
- [ ] 更新成功時に「プロフィールを更新しました」メッセージが表示され、ダッシュボードへリダイレクト
- [ ] バリデーションエラーが日本語で表示される

---

## テスト観点

### プロフィール編集フォーム表示
- [ ] /dashboard/profile/edit にアクセスして編集フォームが表示される
- [ ] 名前フィールドに現在の名前が表示される
- [ ] 経歴フィールドに現在の経歴が表示される（未設定の場合は空）
- [ ] 保存ボタンとキャンセルボタンが表示される

### プロフィール更新
- [ ] 名前を「新しい名前」に変更して保存
- [ ] 「プロフィールを更新しました」メッセージが表示される
- [ ] ダッシュボードへリダイレクトされる
- [ ] データベースで `SELECT * FROM profiles WHERE user_id = ?;` を確認し、名前が更新されている

### バリデーションエラー
- [ ] 名前を空にして保存 → 「名前は必須です」エラーが表示される
- [ ] 名前を51文字以上入力 → 「名前は50文字以内で入力してください」エラーが表示される
- [ ] 経歴を1001文字以上入力 → 「経歴は1000文字以内で入力してください」エラーが表示される

### 検証方法
1. テストユーザーでログイン
2. ダッシュボードから「プロフィール編集」をクリック
3. 名前・経歴を編集して保存
4. ダッシュボードに戻り、更新されたことを確認

---

## 実装例

### ProfileController.php
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->profile;

        return view('profile.edit', [
            'profile' => $profile,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $profile = $request->user()->profile;

        $profile->update($request->validated());

        return redirect()->route('dashboard')
            ->with('success', 'プロフィールを更新しました');
    }
}
```

### UpdateProfileRequest.php
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authミドルウェアで認証済み
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'biography' => 'nullable|string|max:1000',
            // 動画選択は後のIssueで追加
            // 'thumbnail_video_id' => 'nullable|exists:videos,id',
            // 'popup_video_id' => 'nullable|exists:videos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '名前は必須です',
            'name.max' => '名前は50文字以内で入力してください',
            'biography.max' => '経歴は1000文字以内で入力してください',
        ];
    }
}
```

### profile/edit.blade.php
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            プロフィール編集
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        {{-- 名前 --}}
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">名前 <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $profile->name) }}"
                                required
                                maxlength="50"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                maxlength="1000"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >{{ old('biography', $profile->biography) }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">残り: <span id="biography-count">{{ 1000 - mb_strlen($profile->biography ?? '') }}</span> 文字</p>
                            @error('biography')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ボタン --}}
                        <div class="flex justify-end gap-4">
                            <a href="{{ route('dashboard') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
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

    <script>
        // 文字数カウント
        document.getElementById('biography').addEventListener('input', function() {
            const maxLength = 1000;
            const currentLength = this.value.length;
            document.getElementById('biography-count').textContent = maxLength - currentLength;
        });
    </script>
</x-app-layout>
```

---

## 課題確認事項

- **文字数カウント**: 経歴入力フィールドにリアルタイム文字数カウントを表示する？
- **プレビュー機能**: プロフィール編集画面からプレビューへ直接遷移できるボタンを追加する？

---

## 参考資料

- データフロー設計書: `docs/05_data_flow.md`（プロフィール編集フロー）
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
