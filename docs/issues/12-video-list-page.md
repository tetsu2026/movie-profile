# Issue #12: 動画一覧ページ実装（表示のみ）

## 背景 / 目的

動画一覧ページを実装し、自分の動画リストとステータス（uploading/encoding/completed/failed）を表示する。アップロード機能は次のIssueで実装するため、この段階では表示とナビゲーションのみ実装する。

- **依存**: #9
- **ラベル**: frontend, backend

---

## スコープ / 作業項目

### 1. VideoController作成
```bash
php artisan make:controller VideoController
```
- `index()` メソッド: 自分の動画一覧を取得して表示

### 2. ルート定義
- `routes/web.php`
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/videos', [VideoController::class, 'index'])->name('videos.index');
});
```

### 3. Bladeテンプレート作成
- `resources/views/videos/index.blade.php`
- 表示内容:
  - 動画一覧テーブル（ファイル名、ステータス、作成日時）
  - ステータスバッジ（青/黄/緑/赤）
  - 「動画アップロード」ボタン
  - 動画が0件の場合「まだ動画がありません」メッセージ

### 4. ステータスバッジのスタイル
- `uploading`: 青色背景（bg-blue-500）
- `encoding`: 黄色背景（bg-yellow-500）
- `completed`: 緑色背景（bg-green-500）
- `failed`: 赤色背景（bg-red-500）

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] VideoController@indexが実装され、認証ユーザーの動画リストを取得
- [ ] 動画一覧ページで動画ファイル名・ステータス（uploading/encoding/completed/failed）が表示される
- [ ] ステータスに応じたバッジ（青/黄/緑/赤）が表示される
- [ ] 「動画アップロード」ボタンが配置される（遷移先は後のIssueで実装）
- [ ] 動画が0件の場合「まだ動画がありません」と表示される
- [ ] 動画リストが作成日時の降順（新しい順）で表示される

---

## テスト観点

### 動画一覧表示
- [ ] Tinkerでテスト動画を作成:
```php
$user = User::find(1);
$user->videos()->create(['original_filename' => 'test1.mp4', 'status' => 'uploading']);
$user->videos()->create(['original_filename' => 'test2.mp4', 'status' => 'encoding']);
$user->videos()->create(['original_filename' => 'test3.mp4', 'status' => 'completed']);
$user->videos()->create(['original_filename' => 'test4.mp4', 'status' => 'failed', 'error_message' => 'エンコード失敗']);
```
- [ ] /dashboard/videos にアクセスして動画一覧が表示される
- [ ] 4本の動画がそれぞれ正しいステータスバッジで表示される
- [ ] 「動画アップロード」ボタンが表示される

### ステータスバッジ
- [ ] `uploading` の動画が青色バッジで表示される
- [ ] `encoding` の動画が黄色バッジで表示される
- [ ] `completed` の動画が緑色バッジで表示される
- [ ] `failed` の動画が赤色バッジで表示される

### 空の状態
- [ ] 動画が0件のユーザーでログイン
- [ ] /dashboard/videos にアクセス
- [ ] 「まだ動画がありません」メッセージが表示される
- [ ] 「動画アップロード」ボタンが表示される

### 検証方法
1. Tinkerでテスト動画を作成
2. /dashboard/videos にアクセス
3. 動画一覧とステータスバッジが表示されることを確認

---

## 実装例

### VideoController.php
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function index(Request $request): View
    {
        $videos = $request->user()
            ->videos()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('videos.index', [
            'videos' => $videos,
        ]);
    }
}
```

### videos/index.blade.php
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            動画管理
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- アップロードボタン --}}
            <div class="mb-6">
                <a href="{{ route('videos.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    動画アップロード
                </a>
            </div>

            {{-- 動画一覧 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($videos->count() > 0)
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ファイル名</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作成日時</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($videos as $video)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $video->original_filename }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($video->status === 'uploading')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    アップロード中
                                                </span>
                                            @elseif($video->status === 'encoding')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    エンコード中
                                                </span>
                                            @elseif($video->status === 'completed')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    使用可能
                                                </span>
                                            @elseif($video->status === 'failed')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    エンコード失敗
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $video->created_at->format('Y-m-d H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-gray-500 text-center py-8">まだ動画がありません</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## 課題確認事項

- **ページネーション**: 動画が多い場合、ページネーションは必要か？（Phase 2で検討）
- **ソート機能**: ファイル名やステータスでソートする機能は必要か？
- **検索機能**: ファイル名で検索する機能は必要か？（Phase 2で検討）

---

## 参考資料

- サイトマップ: `docs/04_sitemap.md`
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
- 状態遷移設計書: `docs/08_state_machine_video.md`（ステータス定義）

---

## 更新履歴

### 2026-01-26: プロフィール設定欄の表示改善

**変更内容**: プロフィール設定欄で、サムネイル動画とポップアップ動画の設定状況を区別して表示するよう改善。

**変更前**:
- 「設定中」としか表示されない
- サムネイル動画のみ追跡（ポップアップ動画は表示されない）

**変更後**:
- サムネイル動画に設定中 → オレンジ色バッジ「サムネイル動画設定中」
- ポップアップ動画に設定中 → オレンジ色バッジ「ポップアップ動画設定中」
- 両方に設定中 → 両方のバッジを縦に表示
- 未設定 → 「-」

**追加の受け入れ基準**:
- [x] プロフィール設定欄でサムネイル動画に設定されている場合、オレンジ色バッジで「サムネイル動画設定中」と表示される
- [x] プロフィール設定欄でポップアップ動画に設定されている場合、オレンジ色バッジで「ポップアップ動画設定中」と表示される
- [x] 両方に設定されている場合、両方のバッジが縦に並んで表示される
