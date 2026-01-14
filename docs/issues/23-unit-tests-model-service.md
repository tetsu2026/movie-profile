# Issue #23: 単体テスト実装（Model/Service）

## 背景 / 目的

Eloquentモデルとサービスクラスの単体テストを作成し、ビジネスロジックの正確性を保証する。リレーション、バリデーション、エンコード処理などの重要なロジックをテストで網羅する。

- **依存**: #14, #18
- **ラベル**: backend

---

## スコープ / 作業項目

### 1. モデルテスト作成
- `tests/Unit/Models/UserTest.php`
- `tests/Unit/Models/ProfileTest.php`
- `tests/Unit/Models/VideoTest.php`
- テスト内容:
  - リレーションの動作確認
  - $fillableの動作確認
  - SoftDeletesの動作確認

### 2. サービステスト作成
- `tests/Unit/Services/VideoEncoderServiceTest.php`
- テスト内容:
  - エンコード成功ケース
  - エンコード失敗ケース
  - リトライロジック
  - FFmpegコマンドのモック化

### 3. バリデーションテスト作成
- `tests/Unit/Requests/UpdateProfileRequestTest.php`
- `tests/Unit/Requests/StoreVideoRequestTest.php`
- テスト内容:
  - バリデーションルールの動作確認
  - エラーメッセージの確認

### 4. テストデータベース設定
- `phpunit.xml` でSQLiteメモリDBを使用
- テストごとにデータベースをリフレッシュ

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] User/Profile/Videoモデルのリレーションテストが作成される
- [ ] VideoEncoderServiceのエンコードロジックテストが作成される（モック使用）
- [ ] バリデーションルールのテストが作成される
- [ ] `php artisan test`で全テストが成功する
- [ ] テストカバレッジが主要ロジックで80%以上（オプション）
- [ ] テストがCI/CDパイプラインで自動実行される準備ができている

---

## テスト観点

### モデルリレーションテスト
- [ ] User hasOne Profile
- [ ] User hasMany Videos
- [ ] Profile belongsTo User
- [ ] Profile belongsTo thumbnailVideo/popupVideo
- [ ] Video belongsTo User

### SoftDeletesテスト
- [ ] ユーザー削除時にdeleted_atが設定される
- [ ] 削除されたユーザーは通常のクエリで取得されない
- [ ] withTrashed()で削除されたユーザーも取得できる

### VideoEncoderServiceテスト（モック使用）
- [ ] エンコード成功時にstatus='completed'になる
- [ ] エンコード失敗時にretry_countがインクリメントされる
- [ ] 3回失敗後にstatus='failed'になる
- [ ] FFmpegコマンドがモック化される

### バリデーションテスト
- [ ] 名前が空の場合バリデーションエラー
- [ ] 名前が51文字以上の場合バリデーションエラー
- [ ] 経歴が1001文字以上の場合バリデーションエラー
- [ ] 動画ファイルサイズが100MBを超える場合バリデーションエラー

### 検証方法
```bash
php artisan test
php artisan test --filter=UserTest
php artisan test --coverage（カバレッジレポート生成）
```

---

## 実装例

### tests/Unit/Models/UserTest.php
```php
<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_has_one_profile()
    {
        $user = User::factory()->create();
        $profile = $user->profile()->create(['name' => 'テストユーザー']);

        $this->assertInstanceOf(Profile::class, $user->profile);
        $this->assertEquals('テストユーザー', $user->profile->name);
    }

    /** @test */
    public function user_has_many_videos()
    {
        $user = User::factory()->create();
        $user->videos()->create(['original_filename' => 'video1.mp4', 'status' => 'completed']);
        $user->videos()->create(['original_filename' => 'video2.mp4', 'status' => 'completed']);

        $this->assertCount(2, $user->videos);
        $this->assertInstanceOf(Video::class, $user->videos->first());
    }

    /** @test */
    public function user_can_be_soft_deleted()
    {
        $user = User::factory()->create();

        $user->delete();

        $this->assertSoftDeleted($user);
        $this->assertNull(User::find($user->id));
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }
}
```

### tests/Unit/Services/VideoEncoderServiceTest.php
```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Video;
use App\Services\VideoEncoderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;

class VideoEncoderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    /** @test */
    public function encode_with_retry_succeeds_on_first_try()
    {
        $user = User::factory()->create();
        $video = $user->videos()->create([
            'original_filename' => 'test.mp4',
            'original_path' => 'users/1/original/1.mp4',
            'status' => 'encoding',
        ]);

        // S3にダミーファイルを配置
        Storage::disk('s3')->put($video->original_path, 'dummy content');

        // VideoEncoderServiceをモック化（実際のFFmpegは実行しない）
        $service = Mockery::mock(VideoEncoderService::class)->makePartial();
        $service->shouldReceive('encode')->andReturn(true);

        $result = $service->encodeWithRetry($video);

        $this->assertTrue($result);
        $this->assertEquals('completed', $video->fresh()->status);
    }

    /** @test */
    public function encode_with_retry_fails_after_three_attempts()
    {
        $user = User::factory()->create();
        $video = $user->videos()->create([
            'original_filename' => 'test.mp4',
            'original_path' => 'users/1/original/1.mp4',
            'status' => 'encoding',
        ]);

        Storage::disk('s3')->put($video->original_path, 'dummy content');

        // エンコードを常に失敗させる
        $service = Mockery::mock(VideoEncoderService::class)->makePartial();
        $service->shouldReceive('encode')->andReturn(false);

        $result = $service->encodeWithRetry($video);

        $this->assertFalse($result);
        $this->assertEquals('failed', $video->fresh()->status);
        $this->assertEquals(3, $video->fresh()->retry_count);
    }
}
```

### tests/Unit/Requests/UpdateProfileRequestTest.php
```php
<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function name_is_required()
    {
        $request = new UpdateProfileRequest();

        $validator = validator(['name' => ''], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertEquals('名前は必須です', $validator->errors()->first('name'));
    }

    /** @test */
    public function name_must_be_50_characters_or_less()
    {
        $request = new UpdateProfileRequest();

        $validator = validator(['name' => str_repeat('あ', 51)], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertEquals('名前は50文字以内で入力してください', $validator->errors()->first('name'));
    }

    /** @test */
    public function biography_must_be_1000_characters_or_less()
    {
        $request = new UpdateProfileRequest();

        $validator = validator(['biography' => str_repeat('あ', 1001)], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertEquals('経歴は1000文字以内で入力してください', $validator->errors()->first('biography'));
    }
}
```

### phpunit.xml（SQLiteメモリDB設定）
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

---

## 課題確認事項

- **カバレッジツール**: PHPUnitのカバレッジレポート（--coverage）を使用する？Xdebugのインストールが必要
- **モック戦略**: FFmpegやS3のモック化をどの程度行うか？
- **CI/CD**: GitHub ActionsなどでテストをCI/CDパイプラインに組み込む？

---

## 参考資料

- Laravel Testing公式: https://laravel.com/docs/11.x/testing
- PHPUnit公式: https://phpunit.de/
