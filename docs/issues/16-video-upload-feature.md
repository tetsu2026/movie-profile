# Issue #16: 動画アップロード機能実装

## 背景 / 目的

動画アップロードフォームとバリデーション、S3アップロード処理を実装する。Phase 1は同期処理で最小実装し、エンコード処理は次のIssue（#17）で実装する。

- **依存**: #15
- **ラベル**: backend, frontend

---

## スコープ / 作業項目

### 1. VideoController に create()/store() メソッド追加
- `create()`: 動画アップロードフォーム表示
- `store()`: 動画アップロード処理
  - バリデーション
  - videosテーブルにレコード作成（status: uploading）
  - S3へアップロード
  - status更新: uploading → encoding

### 2. StoreVideoRequest作成
```bash
php artisan make:request StoreVideoRequest
```
- バリデーションルール:
  - `video`: required, file, mimes:mp4,mov,avi,wmv, max:102400（100MB）
- カスタムバリデーション: 動画の長さが60秒以内かチェック

### 3. ルート定義
- `routes/web.php`
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard/videos/upload', [VideoController::class, 'create'])->name('videos.create');
    Route::post('/dashboard/videos', [VideoController::class, 'store'])->name('videos.store');
});
```

### 4. Bladeテンプレート作成
- `resources/views/videos/create.blade.php`
- ファイル選択フォーム
- アップロード制限の表示（100MB以内、1分以内、対応形式）
- アップロードボタン

### 5. 動画の長さチェック実装
- getID3ライブラリまたはFFprobeで動画の長さを取得
- 60秒以内かバリデーション

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] VideoController@create/storeが実装される
- [ ] StoreVideoRequestでバリデーション（100MB以内、mp4/mov/avi/wmv形式）が動作
- [ ] 動画アップロード時にvideosテーブルにレコード作成（status: uploading）
- [ ] S3へ動画がアップロードされ、original_pathが保存される
- [ ] アップロード成功時にstatus='encoding'に更新される
- [ ] 動画の長さが60秒以内かチェックされる（getID3ライブラリまたはFFprobe使用）
- [ ] バリデーションエラーが日本語で表示される

---

## テスト観点

### 動画アップロードフォーム
- [ ] /dashboard/videos/upload にアクセスしてアップロードフォームが表示される
- [ ] ファイル選択ボタンが表示される
- [ ] アップロード制限（100MB以内、1分以内、対応形式）が表示される
- [ ] アップロードボタンが表示される

### 動画アップロード（成功）
- [ ] 有効な動画ファイル（mp4、60秒以内、100MB以内）を選択
- [ ] アップロードボタンをクリック
- [ ] 「動画のアップロードが完了しました」メッセージが表示される
- [ ] 動画一覧ページへリダイレクト
- [ ] データベースで `SELECT * FROM videos WHERE user_id = ?;` を確認
  - status = 'encoding'
  - original_path にS3パスが保存されている
- [ ] MinIO管理画面でファイルが確認できる

### バリデーションエラー
- [ ] ファイルを選択せずにアップロード → 「動画ファイルを選択してください」
- [ ] 101MBのファイルをアップロード → 「ファイルサイズは100MB以内にしてください」
- [ ] 対応外の形式（.txt, .jpg）をアップロード → 「対応している動画形式はmp4, mov, avi, wmvです」
- [ ] 61秒の動画をアップロード → 「動画の長さは1分以内にしてください」

### 検証方法
1. 有効な動画ファイルを準備（mp4、30秒、50MB程度）
2. /dashboard/videos/upload でアップロード
3. データベースとMinIOでファイルが確認できることを確認

---

## 実装例

### StoreVideoRequest.php
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use getID3;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:102400', // 100MB
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('video')) {
                $file = $this->file('video');

                // 動画の長さをチェック（getID3使用）
                $getID3 = new getID3;
                $fileInfo = $getID3->analyze($file->getRealPath());

                if (isset($fileInfo['playtime_seconds'])) {
                    $duration = $fileInfo['playtime_seconds'];
                    if ($duration > 60) {
                        $validator->errors()->add('video', '動画の長さは1分以内にしてください');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'video.required' => '動画ファイルを選択してください',
            'video.file' => 'ファイルをアップロードしてください',
            'video.mimes' => '対応している動画形式はmp4, mov, avi, wmvです',
            'video.max' => 'ファイルサイズは100MB以内にしてください',
        ];
    }
}
```

### VideoController.php（create/storeメソッド追加）
```php
use App\Http\Requests\StoreVideoRequest;
use Illuminate\Support\Facades\Storage;

public function create(): View
{
    return view('videos.create');
}

public function store(StoreVideoRequest $request): RedirectResponse
{
    $user = $request->user();

    // 動画レコード作成（status: uploading）
    $video = $user->videos()->create([
        'original_filename' => $request->file('video')->getClientOriginalName(),
        'status' => 'uploading',
    ]);

    try {
        // S3にアップロード
        $extension = $request->file('video')->getClientOriginalExtension();
        $path = "users/{$user->id}/original/{$video->id}.{$extension}";

        $uploaded = Storage::disk('s3')->putFileAs(
            dirname($path),
            $request->file('video'),
            basename($path)
        );

        if (!$uploaded) {
            throw new \Exception('S3へのアップロードに失敗しました');
        }

        // 動画情報を更新
        $video->update([
            'original_path' => $path,
            'file_size' => $request->file('video')->getSize(),
            'status' => 'encoding', // 次のIssueでエンコード処理を実装
        ]);

        // TODO: Issue #17でエンコード処理を実装

        return redirect()->route('videos.index')
            ->with('success', '動画のアップロードが完了しました');

    } catch (\Exception $e) {
        // エラー時は動画レコードを削除
        $video->delete();

        return redirect()->route('videos.create')
            ->with('error', '動画のアップロードに失敗しました: ' . $e->getMessage());
    }
}
```

### videos/create.blade.php
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            動画アップロード
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- アップロード制限 --}}
                    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
                        <p class="font-bold">アップロード制限</p>
                        <ul class="list-disc list-inside mt-2">
                            <li>ファイルサイズ: 100MB以内</li>
                            <li>動画の長さ: 1分以内</li>
                            <li>対応形式: mp4, mov, avi, wmv</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="video" class="block text-sm font-medium text-gray-700">動画ファイル</label>
                            <input
                                type="file"
                                name="video"
                                id="video"
                                accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv"
                                required
                                class="mt-1 block w-full"
                            >
                            @error('video')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-4">
                            <a href="{{ route('videos.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                キャンセル
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                アップロード
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### composer.json（getID3ライブラリ追加）
```bash
composer require james-heinrich/getid3
```

---

## 課題確認事項

- **getID3 vs FFprobe**: 動画の長さチェックにgetID3とFFprobeどちらを使用する？（getID3の方がPHP nativeで簡単）
- **アップロード進捗表示**: アップロード中の進捗バーは必要か？（Phase 2で検討）
- **ファイル名のサニタイズ**: original_filenameにユーザー入力のファイル名をそのまま保存する？（サニタイズが必要？）

---

## 参考資料

- データフロー設計書: `docs/05_data_flow.md`（動画アップロードフロー）
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
