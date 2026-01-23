# Issue #27: 動画表示機能実装

## 背景 / 目的

公開プロフィールページおよびプレビューページで、実際に動画を再生表示する機能を実装する。Issue #10で作成したプレースホルダーを実際の動画プレイヤーに置き換え、サムネイル動画（自動再生・ループ・ミュート）とポップアップ動画（モーダル表示・音声あり）を実装する。

- **依存**: #10, #14
- **推奨実装タイミング**: #17（動画エンコード機能）完了後
- **ラベル**: frontend, backend

> **備考**: 本Issueの依存は#10（プレースホルダー実装）と#14（動画選択機能）ですが、実際に動画を表示するには`status='completed'`の動画がS3に存在する必要があります。テストデータを手動で挿入することで検証可能ですが、現実的には#17（動画エンコード機能）完了後に実装することを推奨します。

---

## スコープ / 作業項目

### 1. サムネイル動画の実装

#### 表示仕様
- **位置**: 画面右下に固定配置（`position: fixed`）
- **形状**: 円形（`border-radius: 50%`）
- **サイズ**: 128px × 128px（デスクトップ）、96px × 96px（モバイル）
- **再生方式**: 自動再生、ループ、ミュート
- **クリック時**: ポップアップ動画をモーダルで表示

#### 技術仕様
- HTML5 `<video>` タグを使用
- 属性: `autoplay`, `loop`, `muted`, `playsinline`
- S3から直接配信（`encoded_path`のURL）
- `object-fit: cover` で円形にクロップ表示

### 2. ポップアップ動画（モーダル）の実装

#### 表示仕様
- **トリガー**: サムネイル動画クリック
- **表示形式**: フルスクリーンに近いモーダルウィンドウ
- **動画サイズ**: 最大幅 90vw、最大高さ 80vh、アスペクト比維持
- **再生方式**: 手動再生、音声あり
- **コントロール**: 再生/一時停止、音量、シークバー、フルスクリーン

#### モーダル仕様
- **背景**: 半透明の黒オーバーレイ（`bg-black/70`）
- **閉じる方法**:
  - オーバーレイクリック
  - 右上の×ボタン
  - Escapeキー
- **アニメーション**: フェードイン/フェードアウト

### 3. Bladeコンポーネント作成

#### video-thumbnail コンポーネント
- `resources/views/components/video-thumbnail.blade.php`
- Props: `$video`, `$popupVideo`, `$size`

#### video-modal コンポーネント
- `resources/views/components/video-modal.blade.php`
- Props: `$video`, `$id`
- Alpine.js または vanilla JavaScript でモーダル制御

### 4. プレビューページへの適用
- `resources/views/dashboard/preview.blade.php` で同じコンポーネントを使用

### 5. 動画未設定時の表示
- プレースホルダー表示を維持
- サムネイル動画未設定: 円形のグレー背景 + 「動画未設定」テキスト
- ポップアップ動画未設定: サムネイルクリック時にモーダルを開かない

---

## ゴール / 完了条件（Acceptance Criteria）

### サムネイル動画
- [ ] サムネイル動画が円形で画面右下に固定表示される
- [ ] サムネイル動画が自動再生、ループ、ミュートで再生される
- [ ] モバイル表示時にサイズが適切に調整される
- [ ] 動画未設定時はプレースホルダーが表示される

### ポップアップ動画
- [ ] サムネイル動画クリックでモーダルが開く
- [ ] ポップアップ動画が音声ありで再生される
- [ ] 再生/一時停止、音量、シークバーのコントロールが表示される
- [ ] オーバーレイクリック、×ボタン、Escapeキーでモーダルが閉じる
- [ ] モーダルを閉じると動画が一時停止する
- [ ] ポップアップ動画未設定時はサムネイルクリックで何も起こらない

### レスポンシブ対応
- [ ] デスクトップ（1024px以上）: サムネイル128px、モーダル最大幅90vw
- [ ] タブレット（768px〜1023px）: サムネイル112px
- [ ] モバイル（767px以下）: サムネイル96px、モーダルほぼフルスクリーン

### プレビューページ
- [ ] プレビューページでも同じ動画表示機能が動作する

---

## 技術仕様

### 動画配信方式

```
ブラウザ ─→ S3 (encoded_path)
         ← 動画ストリーミング
```

- S3から直接配信（CloudFrontは将来実装）
- プログレッシブダウンロード方式
- `Content-Type: video/mp4`

### 動画URL生成

```php
// Videoモデルにアクセサを追加
public function getEncodedUrlAttribute(): ?string
{
    if (!$this->encoded_path) {
        return null;
    }

    return Storage::disk('s3')->url($this->encoded_path);
}
```

### Bladeテンプレートでの使用

```blade
{{-- サムネイル動画 --}}
@if($profile->thumbnailVideo && $profile->thumbnailVideo->status === 'completed')
    <x-video-thumbnail
        :video="$profile->thumbnailVideo"
        :popup-video="$profile->popupVideo"
    />
@else
    <x-video-thumbnail-placeholder />
@endif
```

---

## 実装例

### video-thumbnail.blade.php

```blade
@props([
    'video',
    'popupVideo' => null,
    'size' => 'md'
])

@php
    $sizeClasses = [
        'sm' => 'w-24 h-24',
        'md' => 'w-32 h-32 md:w-28 lg:w-32',
        'lg' => 'w-40 h-40',
    ];
@endphp

<div class="fixed bottom-8 right-8 z-50">
    @if($popupVideo && $popupVideo->status === 'completed')
        {{-- クリック可能なサムネイル --}}
        <button
            type="button"
            class="{{ $sizeClasses[$size] }} rounded-full overflow-hidden shadow-lg hover:scale-105 transition-transform cursor-pointer"
            x-data
            @click="$dispatch('open-video-modal', { videoId: '{{ $popupVideo->id }}' })"
            aria-label="動画を再生"
        >
            <video
                class="w-full h-full object-cover"
                autoplay
                loop
                muted
                playsinline
            >
                <source src="{{ $video->encoded_url }}" type="video/mp4">
            </video>
        </button>
    @else
        {{-- クリック不可のサムネイル（ポップアップ動画なし） --}}
        <div class="{{ $sizeClasses[$size] }} rounded-full overflow-hidden shadow-lg">
            <video
                class="w-full h-full object-cover"
                autoplay
                loop
                muted
                playsinline
            >
                <source src="{{ $video->encoded_url }}" type="video/mp4">
            </video>
        </div>
    @endif
</div>
```

### video-thumbnail-placeholder.blade.php

```blade
@props([
    'size' => 'md'
])

@php
    $sizeClasses = [
        'sm' => 'w-24 h-24',
        'md' => 'w-32 h-32 md:w-28 lg:w-32',
        'lg' => 'w-40 h-40',
    ];
@endphp

<div class="fixed bottom-8 right-8 z-50">
    <div class="{{ $sizeClasses[$size] }} bg-gray-200 rounded-full flex items-center justify-center shadow-lg">
        <span class="text-gray-500 text-xs text-center px-2">動画未設定</span>
    </div>
</div>
```

### video-modal.blade.php

```blade
@props([
    'video',
    'id'
])

<div
    x-data="{ open: false }"
    x-show="open"
    x-on:open-video-modal.window="if ($event.detail.videoId === '{{ $id }}') open = true"
    x-on:keydown.escape.window="open = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center"
    style="display: none;"
>
    {{-- オーバーレイ --}}
    <div
        class="absolute inset-0 bg-black/70"
        @click="open = false; $refs.video.pause()"
    ></div>

    {{-- モーダルコンテンツ --}}
    <div class="relative z-10 w-full max-w-4xl mx-4">
        {{-- 閉じるボタン --}}
        <button
            type="button"
            class="absolute -top-10 right-0 text-white hover:text-gray-300 transition"
            @click="open = false; $refs.video.pause()"
            aria-label="閉じる"
        >
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        {{-- 動画プレイヤー --}}
        <video
            x-ref="video"
            class="w-full rounded-lg shadow-2xl"
            controls
            playsinline
        >
            <source src="{{ $video->encoded_url }}" type="video/mp4">
            <p>お使いのブラウザは動画再生に対応していません。</p>
        </video>
    </div>
</div>
```

### users/show.blade.php（更新）

```blade
<x-guest-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            {{-- メインコンテンツ --}}
            <h1 class="text-3xl font-bold mb-4">{{ $profile->name }}</h1>

            {{-- 経歴 --}}
            @if($profile->biography)
                <p class="text-gray-700 whitespace-pre-wrap mb-8">{{ $profile->biography }}</p>
            @else
                <p class="text-gray-500 mb-8">経歴が設定されていません</p>
            @endif
        </div>
    </div>

    {{-- サムネイル動画 --}}
    @if($profile->thumbnailVideo && $profile->thumbnailVideo->status === 'completed')
        <x-video-thumbnail
            :video="$profile->thumbnailVideo"
            :popup-video="$profile->popupVideo"
        />
    @else
        <x-video-thumbnail-placeholder />
    @endif

    {{-- ポップアップ動画モーダル --}}
    @if($profile->popupVideo && $profile->popupVideo->status === 'completed')
        <x-video-modal
            :video="$profile->popupVideo"
            :id="$profile->popupVideo->id"
        />
    @endif
</x-guest-layout>
```

---

## テスト観点

### 動画再生テスト

#### サムネイル動画
- [ ] ページロード時にサムネイル動画が自動再生される
- [ ] 動画がループ再生される（最後まで再生後、最初から再生）
- [ ] 音声がミュートになっている
- [ ] 円形にクロップされて表示される

#### ポップアップ動画
- [ ] サムネイルクリックでモーダルが開く
- [ ] モーダル内の動画は自動再生されない（ユーザー操作を待つ）
- [ ] 再生ボタンで動画が再生される
- [ ] 音声が再生される（ミュートではない）
- [ ] コントロール（再生/一時停止、音量、シークバー）が操作できる
- [ ] フルスクリーンボタンが動作する

#### モーダル操作
- [ ] オーバーレイクリックでモーダルが閉じる
- [ ] ×ボタンクリックでモーダルが閉じる
- [ ] Escapeキー押下でモーダルが閉じる
- [ ] モーダルを閉じると動画が一時停止する

### レスポンシブテスト
- [ ] Chrome DevToolsでモバイル表示（375px幅）をテスト
- [ ] サムネイル動画のサイズが適切に調整される
- [ ] モーダルがほぼフルスクリーンで表示される

### エッジケーステスト
- [ ] サムネイル動画のみ設定（ポップアップなし）の場合、クリックしても何も起こらない
- [ ] ポップアップ動画のみ設定（サムネイルなし）の場合、プレースホルダーが表示される
- [ ] 両方とも未設定の場合、プレースホルダーが表示される
- [ ] status='encoding'の動画は表示されない
- [ ] status='failed'の動画は表示されない

### 検証方法
1. Tinkerでテストユーザーの動画を設定:
```php
$video = Video::where('status', 'completed')->first();
$profile = User::find(1)->profile;
$profile->update([
    'thumbnail_video_id' => $video->id,
    'popup_video_id' => $video->id
]);
```
2. `/users/1` にアクセス
3. サムネイル動画が自動再生されることを確認
4. サムネイルクリックでモーダルが開くことを確認
5. 各種操作テストを実施

---

## ブラウザ対応

### 対応ブラウザ
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### モバイルブラウザ
- iOS Safari 14+
- Android Chrome 90+

### 注意点
- iOS Safariでは`autoplay`は`muted`属性がないと動作しない（対応済み）
- `playsinline`属性がないとiOSでフルスクリーン再生になる（対応済み）

---

## パフォーマンス考慮

### 動画読み込み最適化
- `preload="metadata"`: メタデータのみ先読み（サムネイル動画）
- `preload="none"`: ポップアップ動画は開くまで読み込まない

```blade
{{-- サムネイル動画 --}}
<video preload="metadata" autoplay loop muted playsinline>

{{-- ポップアップ動画 --}}
<video preload="none" controls playsinline>
```

### 将来の改善案（Phase 2以降）
- CloudFront導入による配信高速化
- HLS/DASHによるアダプティブビットレートストリーミング
- 動画サムネイル画像の自動生成

---

## 事前条件

### Alpine.jsのインストール

本Issueの実装例ではAlpine.jsを使用しています。Laravel Breezeのデフォルトには含まれていないため、事前にインストールが必要です。

**インストール方法**:
```bash
npm install alpinejs
```

**resources/js/app.js への追加**:
```javascript
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

**代替案**: Alpine.jsを使用しない場合は、vanilla JavaScriptでモーダル制御を実装可能です。

---

## 課題確認事項

- **Alpine.js使用**: 上記の事前条件を満たしているか確認。未インストールの場合は先にインストールするか、vanilla JavaScriptで実装
- **動画フォーマット**: mp4以外のフォーマット（WebM等）のフォールバックは必要か？
- **アクセシビリティ**: 動画のキャプション/字幕対応は必要か？（Phase 2で検討）

---

## 参考資料

- 要件定義書: `docs/requirments/01_requirements.md` - 動画再生仕様
- 画面設計書: `docs/design-docs/07_screen_design.md` - 公開プロフィールページ仕様
- 状態遷移設計書: `docs/design-docs/08_state_machine_video.md` - 動画ステータス管理
- Issue #10: `docs/issues/10-public-profile-detail.md` - 公開プロフィールページのプレースホルダー実装
