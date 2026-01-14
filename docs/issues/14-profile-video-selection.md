# Issue #14: プロフィール編集での動画選択機能追加

## 背景 / 目的

プロフィール編集フォームに動画選択ドロップダウンを追加し、completed状態の動画のみを選択可能にする。これによりユーザーがサムネイル動画とポップアップ動画を設定できるようになる。

- **依存**: #13
- **ラベル**: frontend, backend

---

## スコープ / 作業項目

### 1. ProfileController の edit() メソッド更新
- 動画一覧を取得してビューに渡す
- `status='completed'` の動画のみ選択肢として表示

### 2. UpdateProfileRequest の更新
- バリデーションルールに動画選択フィールドを追加:
  - `thumbnail_video_id`: nullable, exists:videos,id
  - `popup_video_id`: nullable, exists:videos,id
- カスタムバリデーション: 選択された動画が自分の動画かチェック

### 3. Bladeテンプレート更新
- `resources/views/profile/edit.blade.php`
- サムネイル用動画選択ドロップダウン追加
- ポップアップ用動画選択ドロップダウン追加
- 「選択しない」オプション追加

### 4. プロフィール更新処理
- 選択された動画IDをprofilesテーブルに保存
- 所有権チェック（他人の動画は選択不可）

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] プロフィール編集フォームにサムネイル用動画/ポップアップ用動画の選択ドロップダウンが表示される
- [ ] ドロップダウンにはstatus='completed'の動画のみ表示される
- [ ] 動画が未選択の場合は「選択しない」オプションが表示される
- [ ] プロフィール更新時にthumbnail_video_id/popup_video_idが正しく保存される
- [ ] 選択された動画が自分の動画かどうかチェックされる
- [ ] 他人の動画を選択しようとするとバリデーションエラーが表示される

---

## テスト観点

### 動画選択ドロップダウン表示
- [ ] Tinkerでテスト動画を作成（completed状態）
```php
$user = User::find(1);
$user->videos()->create(['original_filename' => 'video1.mp4', 'status' => 'completed']);
$user->videos()->create(['original_filename' => 'video2.mp4', 'status' => 'completed']);
$user->videos()->create(['original_filename' => 'video3.mp4', 'status' => 'encoding']); // 表示されないはず
```
- [ ] プロフィール編集ページ（/dashboard/profile/edit）にアクセス
- [ ] サムネイル用動画ドロップダウンに「選択しない」と2本の動画が表示される
- [ ] `encoding`状態の動画は表示されない

### プロフィール更新（動画選択）
- [ ] サムネイル用動画で「video1.mp4」を選択
- [ ] ポップアップ用動画で「video2.mp4」を選択
- [ ] 保存ボタンをクリック
- [ ] 「プロフィールを更新しました」メッセージが表示される
- [ ] データベースで `SELECT * FROM profiles WHERE user_id = 1;` を確認
- [ ] thumbnail_video_id と popup_video_id が正しく保存されている

### 動画選択解除
- [ ] プロフィール編集ページでサムネイル用動画を「選択しない」に変更
- [ ] 保存ボタンをクリック
- [ ] データベースで thumbnail_video_id が NULL になっている

### 所有権チェック
- [ ] Tinkerで他のユーザーの動画IDを取得
```php
$otherUserVideo = User::find(2)->videos()->where('status', 'completed')->first();
```
- [ ] ブラウザのDevToolsでHTMLを編集し、他人の動画IDを選択肢に追加
- [ ] その動画を選択して保存
- [ ] バリデーションエラー「選択された動画が見つかりません」または「他のユーザーの動画は選択できません」が表示される

### 検証方法
1. Tinkerでcompleted状態の動画を作成
2. プロフィール編集ページで動画を選択
3. 保存後、プレビューページや公開ページで動画が設定されていることを確認（動画表示は後のIssue）

---

## 実装例

### ProfileController.php（edit()メソッド更新）
```php
public function edit(Request $request): View
{
    $profile = $request->user()->profile;

    // completed状態の動画のみ取得
    $videos = $request->user()
        ->videos()
        ->where('status', 'completed')
        ->orderBy('created_at', 'desc')
        ->get();

    return view('profile.edit', [
        'profile' => $profile,
        'videos' => $videos,
    ]);
}
```

### UpdateProfileRequest.php（更新）
```php
public function rules(): array
{
    return [
        'name' => 'required|string|max:50',
        'biography' => 'nullable|string|max:1000',
        'thumbnail_video_id' => 'nullable|exists:videos,id',
        'popup_video_id' => 'nullable|exists:videos,id',
    ];
}

public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // 選択された動画が自分の動画かチェック
        if ($this->thumbnail_video_id) {
            $video = Video::find($this->thumbnail_video_id);
            if ($video && $video->user_id !== auth()->id()) {
                $validator->errors()->add('thumbnail_video_id', '他のユーザーの動画は選択できません');
            }
        }

        if ($this->popup_video_id) {
            $video = Video::find($this->popup_video_id);
            if ($video && $video->user_id !== auth()->id()) {
                $validator->errors()->add('popup_video_id', '他のユーザーの動画は選択できません');
            }
        }
    });
}

public function messages(): array
{
    return [
        'name.required' => '名前は必須です',
        'name.max' => '名前は50文字以内で入力してください',
        'biography.max' => '経歴は1000文字以内で入力してください',
        'thumbnail_video_id.exists' => '選択された動画が見つかりません',
        'popup_video_id.exists' => '選択された動画が見つかりません',
    ];
}
```

### profile/edit.blade.php（動画選択追加）
```blade
{{-- サムネイル用動画 --}}
<div class="mb-4">
    <label for="thumbnail_video_id" class="block text-sm font-medium text-gray-700">サムネイル用動画</label>
    <select
        name="thumbnail_video_id"
        id="thumbnail_video_id"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
    >
        <option value="">選択しない</option>
        @foreach($videos as $video)
            <option
                value="{{ $video->id }}"
                {{ old('thumbnail_video_id', $profile->thumbnail_video_id) == $video->id ? 'selected' : '' }}
            >
                {{ $video->original_filename }}
            </option>
        @endforeach
    </select>
    @error('thumbnail_video_id')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

{{-- ポップアップ用動画 --}}
<div class="mb-4">
    <label for="popup_video_id" class="block text-sm font-medium text-gray-700">ポップアップ用動画</label>
    <select
        name="popup_video_id"
        id="popup_video_id"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
    >
        <option value="">選択しない</option>
        @foreach($videos as $video)
            <option
                value="{{ $video->id }}"
                {{ old('popup_video_id', $profile->popup_video_id) == $video->id ? 'selected' : '' }}
            >
                {{ $video->original_filename }}
            </option>
        @endforeach
    </select>
    @error('popup_video_id')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@if($videos->count() === 0)
    <p class="text-sm text-gray-500 mb-4">※ 動画を選択するには、まず動画をアップロードしてください。</p>
@endif
```

---

## 課題確認事項

- **動画プレビュー**: ドロップダウンで動画を選択した時、プレビューサムネイルを表示する？（Phase 2で検討）
- **同じ動画の選択**: サムネイルとポップアップで同じ動画を選択可能にする？それとも別々の動画を強制する？
- **動画削除時の処理**: プロフィールで使用中の動画が削除された場合の挙動（Issue #13でSET NULLに設定済み）

---

## 参考資料

- データフロー設計書: `docs/05_data_flow.md`（プロフィール編集フロー）
- ルーティング設計書: `docs/06_routing.md`
- 画面設計書: `docs/07_screen_design.md`
