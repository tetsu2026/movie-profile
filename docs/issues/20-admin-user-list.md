# Issue #20: 管理者ユーザー一覧ページ実装

## 背景 / 目的

管理者専用のユーザー一覧ページを実装し、全ユーザーの情報を確認・管理できるようにする。各ユーザーの編集・削除ボタンを配置し、ページネーションで大量のユーザーにも対応する。

- **依存**: #19
- **ラベル**: frontend, backend

---

## スコープ / 作業項目

### 1. Admin\UserController作成
```bash
php artisan make:controller Admin/UserController
```
- `index()` メソッド: 全ユーザーの一覧を取得して表示

### 2. ルート定義
- `routes/web.php`
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
});
```

### 3. Bladeテンプレート作成
- `resources/views/admin/users/index.blade.php`
- 表示内容:
  - ユーザー一覧テーブル（ID, メール, 名前, 権限, 作成日時）
  - 各ユーザーに編集・削除ボタン
  - ページネーション（10件/ページ）

### 4. ページネーション実装
- Laravel標準のページネーション機能を使用
- 10件/ページで表示

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] Admin\UserController@indexが実装される
- [ ] ユーザー一覧ページで全ユーザーのID/メール/名前/権限/作成日時が表示される
- [ ] 各ユーザーに編集・削除ボタンが配置される
- [ ] ページネーション（10件/ページ）が実装される
- [ ] 管理者以外がアクセスすると403エラーが表示される
- [ ] プロフィール情報（名前）がEager Loadingで取得される（N+1問題対策）

---

## テスト観点

### ユーザー一覧表示
- [ ] Tinkerで複数のテストユーザーを作成
```php
User::factory()->count(15)->create()->each(function ($user) {
    $user->profile()->create(['name' => "ユーザー{$user->id}"]);
});
```
- [ ] 管理者でログイン
- [ ] /admin/users にアクセス
- [ ] ユーザー一覧テーブルが表示される
- [ ] ID, メール, 名前, 権限, 作成日時が正しく表示される
- [ ] 編集・削除ボタンが表示される

### ページネーション
- [ ] 11人以上のユーザーが存在する場合、ページネーションリンクが表示される
- [ ] 「次へ」ボタンをクリックして2ページ目が表示される
- [ ] 「前へ」ボタンをクリックして1ページ目に戻る

### 権限チェック
- [ ] 一般ユーザーでログイン
- [ ] /admin/users にアクセス
- [ ] 403エラーが表示される

### N+1問題確認
- [ ] Laravel Debugbarでクエリ数を確認
- [ ] Eager Loading使用により、クエリ数が最小限に抑えられている

### 検証方法
1. Tinkerで15人のユーザーを作成
2. 管理者でログインして /admin/users にアクセス
3. ユーザー一覧とページネーションが表示されることを確認

---

## 実装例

### app/Http/Controllers/Admin/UserController.php
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
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
```

### resources/views/admin/users/index.blade.php
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ユーザー管理
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">メールアドレス</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名前</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">権限</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作成日時</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $user->id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $user->profile->name ?? '未設定' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($user->role === 'admin')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                管理者
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                一般ユーザー
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->created_at->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            編集
                                        </a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="inline" onsubmit="return confirm('本当に削除しますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                削除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- ページネーション --}}
                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 課題確認事項

- **ソフトデリートユーザーの表示**: deleted_atがNULLでないユーザーは表示しない？
- **検索機能**: メールアドレスや名前で検索する機能は必要か？（Phase 2で検討）
- **ソート機能**: ID、メール、作成日時でソートする機能は必要か？（Phase 2で検討）
- **バルク操作**: 複数ユーザーを一括削除する機能は必要か？（Phase 2で検討）

---

## 参考資料

- サイトマップ: `docs/04_sitemap.md`（管理者: ユーザー管理）
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
