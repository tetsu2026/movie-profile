# Issue #26: パフォーマンス最適化（N+1問題/キャッシュ）

## 概要
N+1クエリ問題を解消し、頻繁にアクセスするデータをキャッシュすることで、ページ読み込み速度を向上させます。設計書の非機能要件「ページ読み込み時間: 3秒以内」を達成します。

## 依存関係
- **前提Issue**: #25（セキュリティ強化完了）

## タスク領域
- backend

## 受け入れ基準（AC）

### N+1問題の解消
- [ ] 公開プロフィールページでN+1問題が発生しない
  - ユーザー → プロフィール → 動画のリレーション取得を最適化
  - Eager Loading（`with()`）を使用
- [ ] 動画一覧ページでN+1問題が発生しない
  - 動画 → ユーザーのリレーション取得を最適化
- [ ] ダッシュボードでN+1問題が発生しない
  - ユーザー → プロフィール → 動画のリレーション取得を最適化
- [ ] 管理者ユーザー一覧でN+1問題が発生しない
  - ユーザー → プロフィールのリレーション取得を最適化

### キャッシュの実装
- [ ] 公開プロフィールページでプロフィール情報をキャッシュ
  - `Cache::remember()`を使用
  - キャッシュキー: `profile:{user_id}`
  - キャッシュ有効期限: 1時間
- [ ] ダッシュボードでユーザー情報をキャッシュ
  - キャッシュキー: `user:{user_id}:profile`
  - プロフィール更新時にキャッシュをクリア
- [ ] 動画一覧でエンコード中の動画数をキャッシュ
  - キャッシュキー: `user:{user_id}:encoding_videos_count`
  - キャッシュ有効期限: 5分

### データベースクエリの最適化
- [ ] 必要なカラムのみ取得（`select()`使用）
- [ ] ページネーション実装（管理者ユーザー一覧: 10件/ページ）
- [ ] 不要なクエリの削除
- [ ] インデックスが適切に使用される

### パフォーマンス計測
- [ ] Laravel Debugbarで不要なクエリが検出されない
- [ ] 公開プロフィールページのクエリ数が5個以下
- [ ] 動画一覧ページのクエリ数が3個以下
- [ ] ページ読み込み時間が3秒以内（設計書要件）

## 実装タスク

### 1. Laravel Debugbarのインストール
```bash
composer require barryvdh/laravel-debugbar --dev
```

**config/app.php**:
```php
'providers' => [
    // ...
    Barryvdh\Debugbar\ServiceProvider::class,
],
```

### 2. 公開プロフィールページのN+1問題解消
**PublicProfileController.php（修正前）**:
```php
public function show($id)
{
    $user = User::findOrFail($id);
    $profile = $user->profile; // N+1問題: ここで追加クエリ
    $thumbnailVideo = $profile->thumbnailVideo; // N+1問題: ここで追加クエリ
    $popupVideo = $profile->popupVideo; // N+1問題: ここで追加クエリ

    return view('users.show', compact('profile', 'thumbnailVideo', 'popupVideo'));
}
```

**PublicProfileController.php（修正後）**:
```php
public function show($id)
{
    // Eager Loadingで一度に全データ取得
    $user = User::with([
        'profile.thumbnailVideo',
        'profile.popupVideo',
    ])->findOrFail($id);

    $profile = $user->profile;

    return view('users.show', compact('profile'));
}
```

### 3. キャッシュ実装（公開プロフィールページ）
**PublicProfileController.php（キャッシュ追加）**:
```php
use Illuminate\Support\Facades\Cache;

public function show($id)
{
    // キャッシュから取得（1時間有効）
    $user = Cache::remember("profile:{$id}", 3600, function () use ($id) {
        return User::with([
            'profile.thumbnailVideo',
            'profile.popupVideo',
        ])->findOrFail($id);
    });

    $profile = $user->profile;

    return view('users.show', compact('profile'));
}
```

### 4. プロフィール更新時のキャッシュクリア
**ProfileController.php**:
```php
use Illuminate\Support\Facades\Cache;

public function update(UpdateProfileRequest $request)
{
    $profile = auth()->user()->profile;
    $profile->update($request->validated());

    // キャッシュをクリア
    Cache::forget("profile:{$profile->user_id}");
    Cache::forget("user:{$profile->user_id}:profile");

    return redirect()->route('dashboard')->with('success', 'プロフィールを更新しました');
}
```

### 5. 動画一覧ページのN+1問題解消
**VideoController.php（修正前）**:
```php
public function index()
{
    $videos = Video::where('user_id', auth()->id())->get();

    return view('videos.index', compact('videos'));
}
```

**VideoController.php（修正後）**:
```php
public function index()
{
    // 必要なカラムのみ取得（ファイルパスは不要）
    $videos = Video::where('user_id', auth()->id())
        ->select(['id', 'original_filename', 'status', 'error_message', 'created_at'])
        ->orderBy('created_at', 'desc')
        ->get();

    return view('videos.index', compact('videos'));
}
```

### 6. ダッシュボードのN+1問題解消とキャッシュ
**DashboardController.php（修正前）**:
```php
public function index()
{
    $user = auth()->user();
    $profile = $user->profile;
    $videosCount = $user->videos()->count();
    $completedVideosCount = $user->videos()->where('status', 'completed')->count();

    return view('dashboard', compact('profile', 'videosCount', 'completedVideosCount'));
}
```

**DashboardController.php（修正後）**:
```php
use Illuminate\Support\Facades\Cache;

public function index()
{
    $userId = auth()->id();

    // ユーザー情報をキャッシュ（1時間有効）
    $user = Cache::remember("user:{$userId}:profile", 3600, function () use ($userId) {
        return User::with([
            'profile.thumbnailVideo',
            'profile.popupVideo',
        ])->findOrFail($userId);
    });

    // 動画数は頻繁に変わる可能性があるため、リアルタイム取得
    $videosCount = $user->videos()->count();
    $completedVideosCount = $user->videos()->where('status', 'completed')->count();

    // エンコード中の動画数をキャッシュ（5分有効）
    $encodingVideosCount = Cache::remember("user:{$userId}:encoding_videos_count", 300, function () use ($user) {
        return $user->videos()->where('status', 'encoding')->count();
    });

    return view('dashboard', compact('user', 'videosCount', 'completedVideosCount', 'encodingVideosCount'));
}
```

### 7. 管理者ユーザー一覧のN+1問題解消とページネーション
**Admin/UserController.php（修正前）**:
```php
public function index()
{
    $users = User::all();

    return view('admin.users.index', compact('users'));
}
```

**Admin/UserController.php（修正後）**:
```php
public function index()
{
    // Eager Loadingとページネーション
    $users = User::with('profile')
        ->select(['id', 'email', 'role', 'created_at'])
        ->paginate(10);

    return view('admin.users.index', compact('users'));
}
```

### 8. 動画削除時のキャッシュクリア
**VideoController.php**:
```php
public function destroy($id)
{
    $video = Video::findOrFail($id);

    // 所有権チェック
    if ($video->user_id !== auth()->id()) {
        abort(403);
    }

    // プロフィールで使用中かチェック
    $profile = auth()->user()->profile;
    if ($profile->thumbnail_video_id === $video->id || $profile->popup_video_id === $video->id) {
        return back()->with('error', 'この動画はプロフィールで使用中のため削除できません');
    }

    // S3から削除
    Storage::disk('s3')->delete($video->encoded_path);

    // DBから削除
    $video->delete();

    // エンコード中の動画数のキャッシュをクリア
    Cache::forget("user:{$video->user_id}:encoding_videos_count");

    return redirect()->route('videos.index')->with('success', '動画を削除しました');
}
```

### 9. インデックス確認
マイグレーションファイルで以下のインデックスが設定されているか確認:

**users テーブル**:
```php
$table->index('email');
$table->index('role');
$table->index('deleted_at');
```

**profiles テーブル**:
```php
$table->index('user_id');
```

**videos テーブル**:
```php
$table->index('user_id');
$table->index('status');
```

### 10. キャッシュドライバ設定
**.env**:
```env
# ローカル開発環境
CACHE_DRIVER=file

# 本番環境（Phase 2でRedis導入）
# CACHE_DRIVER=redis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379
```

## 検証方法

### 1. Laravel Debugbarでクエリ数確認
```php
// Debugbarをブラウザで確認
// 各ページのクエリ数とN+1問題をチェック
```

**期待されるクエリ数**:
- 公開プロフィールページ: 1〜2クエリ（キャッシュヒット時は0）
- 動画一覧ページ: 1〜2クエリ
- ダッシュボード: 2〜4クエリ
- 管理者ユーザー一覧: 1〜2クエリ

### 2. パフォーマンステスト
```php
use Illuminate\Support\Facades\DB;

// クエリログを有効化
DB::enableQueryLog();

// ページを表示
$response = $this->get('/users/1');

// クエリ数を確認
$queries = DB::getQueryLog();
$this->assertLessThanOrEqual(5, count($queries));
```

### 3. ページ読み込み速度計測
```bash
# Apache Benchでパフォーマンステスト
ab -n 100 -c 10 http://localhost/users/1

# 結果の確認
# Time per request: < 3000ms (平均)
```

### 4. キャッシュ動作確認
```bash
# キャッシュクリア
php artisan cache:clear

# 1回目のアクセス（キャッシュなし）
curl -w "@curl-format.txt" http://localhost/users/1

# 2回目のアクセス（キャッシュあり）
curl -w "@curl-format.txt" http://localhost/users/1

# 2回目が高速化されていることを確認
```

**curl-format.txt**:
```
time_total: %{time_total}s
```

## パフォーマンス最適化チェックリスト

### コード全体の監査
- [ ] 全コントローラーでEager Loading（`with()`）が使用されているか確認
- [ ] `select()`で必要なカラムのみ取得しているか確認
- [ ] ループ内でクエリが実行されていないか確認
- [ ] キャッシュが適切にクリアされているか確認

### Laravel Debugbarチェック項目
- [ ] Queriesタブでクエリ数が適切か確認
- [ ] Duplicate Queriesがないか確認
- [ ] Slow Queriesがないか確認（100ms以上）
- [ ] N+1 Queriesアラートが表示されていないか確認

### ページ読み込み速度確認
- [ ] トップページ: < 1秒
- [ ] 公開プロフィールページ: < 2秒
- [ ] ダッシュボード: < 2秒
- [ ] 動画一覧ページ: < 2秒
- [ ] 管理者ユーザー一覧: < 3秒

## 関連Issue
- #25: セキュリティ強化（XSS/CSRF/SQLi対策）
- #23: 単体テスト実装（Model/Service）
- #24: 機能テスト実装（Controller/Route）

## 設計書参照
- `docs/01_requirements.md`: パフォーマンス要件（3秒以内）
- `docs/03_database.md`: インデックス設計
- `docs/05_data_flow.md`: データフロー

## 備考

### Phase 2での拡張予定
- **Redis導入**: ファイルキャッシュからRedisへ移行し、パフォーマンスをさらに向上
- **CloudFront導入**: S3の前段にCDNを配置し、動画配信を高速化
- **データベースクエリキャッシュ**: 頻繁なクエリ結果をRedisでキャッシュ
- **Lazyload**: 動画のLazyloadingで初期読み込み速度を向上

### キャッシュ戦略
- **頻繁に変更されないデータ**: 長時間キャッシュ（1時間〜1日）
  - ユーザープロフィール情報
  - エンコード完了済み動画情報
- **頻繁に変更されるデータ**: 短時間キャッシュ（5分〜10分）
  - エンコード中の動画数
  - 動画一覧
- **リアルタイム性が必要なデータ**: キャッシュなし
  - ログイン認証情報
  - CSRF トークン

### パフォーマンス監視（本番環境）
- CloudWatch（Issue #22）でページ読み込み時間を監視
- New RelicまたはDatadogでAPM監視（Phase 2）
- 定期的なパフォーマンステスト実施

### キャッシュのクリアタイミング
| イベント | クリアするキャッシュ |
|---------|-------------------|
| プロフィール更新 | `profile:{user_id}`, `user:{user_id}:profile` |
| 動画削除 | `user:{user_id}:encoding_videos_count` |
| 動画エンコード完了 | `user:{user_id}:encoding_videos_count` |
| ユーザー削除 | `profile:{user_id}`, `user:{user_id}:*` |
