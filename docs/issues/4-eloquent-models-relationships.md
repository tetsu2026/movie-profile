# Issue #4: Eloquentモデル作成とリレーション定義

## 背景 / 目的

User/Profile/Videoモデルを作成し、Eloquentのリレーション機能を使ってテーブル間の関係を定義する。データアクセスを安全かつ効率的に行うため、$fillableによるマスアサインメント対策も実施する。

- **依存**: #3
- **ラベル**: backend

---

## スコープ / 作業項目

### 1. Userモデルの拡張
- Laravel BreezeのデフォルトUserモデルを拡張
- ファイル: `app/Models/User.php`
- 追加内容:
  - `use SoftDeletes;` トレイト
  - リレーション定義:
    - `hasOne(Profile::class)`
    - `hasMany(Video::class)`
  - `$fillable` に `role` を追加
  - `$casts` に `role` の型定義（必要に応じて）

### 2. Profileモデル作成
```bash
php artisan make:model Profile
```
- ファイル: `app/Models/Profile.php`
- リレーション定義:
  - `belongsTo(User::class)`
  - `belongsTo(Video::class, 'thumbnail_video_id')`（別名: `thumbnailVideo()`）
  - `belongsTo(Video::class, 'popup_video_id')`（別名: `popupVideo()`）
- `$fillable`:
  - `['user_id', 'name', 'biography', 'thumbnail_video_id', 'popup_video_id']`

### 3. Videoモデル作成
```bash
php artisan make:model Video
```
- ファイル: `app/Models/Video.php`
- リレーション定義:
  - `belongsTo(User::class)`
- `$fillable`:
  - `['user_id', 'original_filename', 'original_path', 'encoded_path', 'file_size', 'duration', 'status', 'error_message', 'retry_count']`
- `$casts`:
  - `'status'` → `string`（ENUMだが文字列として扱う）

### 4. リレーション動作確認
- Tinkerで動作確認:
```bash
php artisan tinker
```
```php
$user = User::factory()->create();
$profile = $user->profile()->create(['name' => 'Test User']);
$video = $user->videos()->create(['original_filename' => 'test.mp4', 'status' => 'uploading']);
```

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] User/Profile/Videoモデルが作成され、適切な名前空間（`App\Models`）に配置される
- [ ] User hasOne Profile、User hasMany Videosのリレーションが定義される
- [ ] Profile belongsTo User、Profile belongsTo thumbnail_video/popup_videoが定義される
- [ ] Video belongsTo Userが定義される
- [ ] $fillableまたは$guardedが適切に設定され、マスアサインメント対策が完了
- [ ] UserモデルにSoftDeletesトレイトが適用される
- [ ] Tinkerでリレーションを使ったデータ作成・取得が成功する

---

## テスト観点

### リレーション動作確認（Tinker）
```php
// ユーザー作成
$user = User::factory()->create(['email' => 'test@example.com', 'role' => 'user']);

// プロフィール作成（hasOne）
$profile = $user->profile()->create(['name' => 'テストユーザー', 'biography' => 'これはテストです']);

// 動画作成（hasMany）
$video = $user->videos()->create(['original_filename' => 'test.mp4', 'status' => 'uploading']);

// リレーション取得
$user->profile; // Profileモデルが返る
$user->videos; // Videoコレクションが返る
$profile->user; // Userモデルが返る
$video->user; // Userモデルが返る

// プロフィールに動画を紐付け
$profile->update(['thumbnail_video_id' => $video->id]);
$profile->thumbnailVideo; // Videoモデルが返る
```

### 検証方法
1. `php artisan tinker` を起動
2. 上記のコードを実行
3. エラーが発生しないことを確認
4. `$user->profile`、`$user->videos` でリレーションデータが取得できることを確認

---

## 課題確認事項

- **Factory作成**: User/Profile/Videoのファクトリーを作成する？（テストで使用）
- **$guardedの使用**: $fillableの代わりに$guardedを使用するか？（Laravelのベストプラクティスは$fillable）
- **カスタムキャスト**: statusをENUMとして扱うカスタムキャストを作成する？（Phase 2で検討）
- **アクセサ/ミューテータ**: 必要なアクセサ（例: `getStatusLabelAttribute()`）を追加する？

---

## 実装例

### User.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // リレーション
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
```

### Profile.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'biography',
        'thumbnail_video_id',
        'popup_video_id',
    ];

    // リレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function thumbnailVideo()
    {
        return $this->belongsTo(Video::class, 'thumbnail_video_id');
    }

    public function popupVideo()
    {
        return $this->belongsTo(Video::class, 'popup_video_id');
    }
}
```

### Video.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'original_path',
        'encoded_path',
        'file_size',
        'duration',
        'status',
        'error_message',
        'retry_count',
    ];

    // リレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## 参考資料

- データベース設計書: `docs/03_database.md`
- Laravel Eloquent公式: https://laravel.com/docs/11.x/eloquent-relationships
