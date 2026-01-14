# Issue #13: 動画削除機能実装

## 背景 / 目的

動画削除処理を実装し、プロフィールで使用中の動画は削除不可とする安全機構を追加する。S3からの動画ファイル削除は後のIssue（#15）で実装するため、この段階ではデータベースからの削除のみ実装する。

- **依存**: #12
- **ラベル**: backend, frontend

---

## スコープ / 作業項目

### 1. VideoController に destroy() メソッド追加
- 動画削除処理の実装:
  - 所有権チェック（他人の動画は削除不可）
  - プロフィールで使用中かチェック
  - 使用中の場合はエラーメッセージ
  - 使用されていない場合はvideosテーブルから削除

### 2. ルート定義
- `routes/web.php`
```php
Route::middleware('auth')->group(function () {
    Route::delete('/dashboard/videos/{id}', [VideoController::class, 'destroy'])->name('videos.destroy');
});
```

### 3. Bladeテンプレート更新
- `resources/views/videos/index.blade.php` に削除ボタン追加
- フォーム形式で DELETE リクエストを送信
- プロフィールで使用中の動画は削除ボタンを無効化（グレーアウト）

### 4. 削除確認ダイアログ（JavaScript）
- 削除ボタンクリック時に確認ダイアログを表示
- 「本当に削除しますか？」メッセージ

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] VideoController@destroyが実装される
- [ ] 動画削除時に所有権チェック（他人の動画は削除不可）が実施される
- [ ] プロフィールで使用中（thumbnail_video_id/popup_video_id）の動画は削除不可
- [ ] 削除可能な動画はvideosテーブルから削除される（S3削除は後のIssue）
- [ ] 削除成功時に「動画を削除しました」メッセージが表示される
- [ ] 使用中の動画削除時に「この動画はプロフィールで使用中のため削除できません」エラーが表示される
- [ ] 削除ボタンクリック時に確認ダイアログが表示される

---

## テスト観点

### 動画削除（成功）
- [ ] Tinkerでテスト動画を作成（プロフィールで未使用）
```php
$user = User::find(1);
$video = $user->videos()->create(['original_filename' => 'delete_test.mp4', 'status' => 'completed']);
```
- [ ] 動画一覧ページで削除ボタンをクリック
- [ ] 確認ダイアログで「OK」をクリック
- [ ] 「動画を削除しました」メッセージが表示される
- [ ] 動画一覧から削除した動画が消える
- [ ] データベースで `SELECT * FROM videos WHERE id = ?;` を確認し、削除されている

### プロフィールで使用中の動画削除（失敗）
- [ ] Tinkerでプロフィールに動画を設定
```php
$profile = User::find(1)->profile;
$video = User::find(1)->videos()->create(['original_filename' => 'profile_video.mp4', 'status' => 'completed']);
$profile->update(['thumbnail_video_id' => $video->id]);
```
- [ ] 動画一覧ページでその動画の削除ボタンをクリック（またはボタンが無効化されている）
- [ ] 削除ボタンが無効化されているか、クリック時にエラーメッセージが表示される
- [ ] 「この動画はプロフィールで使用中のため削除できません」エラーが表示される

### 所有権チェック
- [ ] Tinkerで他のユーザーの動画IDを取得
```php
$otherUserVideo = User::find(2)->videos()->first();
```
- [ ] URLを直接編集して他人の動画削除を試行:
```
DELETE /dashboard/videos/{他人の動画ID}
```
- [ ] 403エラーまたはエラーメッセージが表示される

### 確認ダイアログ
- [ ] 削除ボタンをクリック
- [ ] 「本当に削除しますか？」確認ダイアログが表示される
- [ ] 「キャンセル」をクリックすると削除されない
- [ ] 「OK」をクリックすると削除処理が実行される

### 検証方法
1. Tinkerでテスト動画を作成
2. 動画一覧ページで削除ボタンをクリック
3. 削除処理が正常に動作することを確認

---

## 実装例

### VideoController.php（destroy()メソッド追加）
```php
public function destroy(Request $request, int $id): RedirectResponse
{
    $video = Video::findOrFail($id);

    // 所有権チェック
    if ($video->user_id !== $request->user()->id) {
        abort(403, 'この操作を実行する権限がありません');
    }

    // プロフィールで使用中かチェック
    $profile = $request->user()->profile;
    if ($profile->thumbnail_video_id === $video->id || $profile->popup_video_id === $video->id) {
        return redirect()->route('videos.index')
            ->with('error', 'この動画はプロフィールで使用中のため削除できません');
    }

    // S3からの削除は後のIssueで実装
    // TODO: Issue #15でS3削除処理を追加

    // データベースから削除
    $video->delete();

    return redirect()->route('videos.index')
        ->with('success', '動画を削除しました');
}
```

### videos/index.blade.php（削除ボタン追加）
```blade
<tbody class="bg-white divide-y divide-gray-200">
    @foreach($videos as $video)
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">{{ $video->original_filename }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                {{-- ステータスバッジ --}}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ $video->created_at->format('Y-m-d H:i') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                @php
                    $profile = Auth::user()->profile;
                    $inUse = $profile->thumbnail_video_id === $video->id || $profile->popup_video_id === $video->id;
                @endphp

                @if($inUse)
                    <button disabled class="bg-gray-300 text-gray-500 font-bold py-1 px-3 rounded cursor-not-allowed">
                        削除（使用中）
                    </button>
                @else
                    <form method="POST" action="{{ route('videos.destroy', $video->id) }}" onsubmit="return confirm('本当に削除しますか？');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">
                            削除
                        </button>
                    </form>
                @endif
            </td>
        </tr>
    @endforeach
</tbody>
```

### layouts/app.blade.php（フラッシュメッセージ表示）
```blade
{{-- 成功メッセージ --}}
@if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

{{-- エラーメッセージ --}}
@if (session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ session('error') }}
    </div>
@endif
```

---

## 課題確認事項

- **ソフトデリート**: 動画の論理削除（soft delete）は必要か？（物理削除でOK？）
- **削除確認ダイアログのUI**: JavaScriptの`confirm()`でOK？より洗練されたモーダルが必要？
- **S3削除**: Issue #15で実装する予定だが、削除処理の順序は？（DB削除 → S3削除 or S3削除 → DB削除）

---

## 参考資料

- データフロー設計書: `docs/05_data_flow.md`（動画削除フロー）
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
