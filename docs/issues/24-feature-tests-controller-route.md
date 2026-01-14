# Issue #24: 機能テスト実装（Controller/Route）

## 概要
コントローラーとルートの機能テストを作成し、エンドツーエンドの動作を検証します。

## 依存関係
- **前提Issue**: #23（単体テスト実装完了）

## タスク領域
- backend

## 受け入れ基準（AC）

### 認証機能テスト
- [ ] ユーザー登録機能のテストが作成される
  - バリデーションエラーのテスト（メール形式、パスワード長さ）
  - 登録成功時にusers/profilesテーブルにレコードが作成されることを確認
  - 登録後にダッシュボードへリダイレクトされることを確認
- [ ] ログイン機能のテストが作成される
  - 正しい認証情報でログイン成功
  - 誤った認証情報でログイン失敗
  - ログイン後にセッションが作成されることを確認
- [ ] ログアウト機能のテストが作成される
  - ログアウト後にセッションが破棄されることを確認

### プロフィール編集機能テスト
- [ ] プロフィール編集画面の表示テストが作成される
  - 未認証ユーザーはログインページへリダイレクト
  - 認証ユーザーは編集フォームが表示される
- [ ] プロフィール更新機能のテストが作成される
  - バリデーションエラーのテスト（名前必須、経歴1000文字超過）
  - 更新成功時にprofilesテーブルが更新される
  - 更新成功メッセージが表示される
- [ ] 動画選択機能のテストが作成される
  - 自分の動画のみ選択可能
  - status='completed'の動画のみドロップダウンに表示される

### 動画アップロード・削除機能テスト
- [ ] 動画アップロード機能のテストが作成される
  - ファイルバリデーションのテスト（100MB超過、非対応形式）
  - アップロード成功時にvideosテーブルにレコード作成
  - S3へのアップロードが実行される（モック使用）
- [ ] 動画削除機能のテストが作成される
  - 所有権チェックのテスト（他人の動画は削除不可）
  - プロフィールで使用中の動画は削除不可
  - 削除成功時にvideosテーブルからレコード削除
  - S3からファイルが削除される（モック使用）

### 公開プロフィールページテスト
- [ ] 公開プロフィールページの表示テストが作成される
  - 存在するユーザーIDでプロフィールページが表示される
  - 存在しないユーザーIDで404エラーが返される
  - 動画が設定されている場合は動画が表示される
  - 動画が未設定の場合はプレースホルダーが表示される

### 管理者機能テスト
- [ ] 管理者ユーザー一覧ページのテストが作成される
  - 管理者のみアクセス可能
  - 一般ユーザーがアクセスすると403エラー
  - 全ユーザーが一覧表示される
- [ ] 管理者ユーザー編集機能のテストが作成される
  - プロフィール情報の更新が可能
  - 権限（admin/user）の変更が可能
- [ ] 管理者ユーザー削除機能のテストが作成される
  - ソフトデリート（deleted_at）が実行される
  - プロフィールと動画がカスケード削除される

### 権限チェックテスト
- [ ] 未認証ユーザーの権限チェックテストが作成される
  - /dashboardにアクセスするとログインページへリダイレクト
  - /dashboard/*にアクセスするとログインページへリダイレクト
- [ ] 一般ユーザーの権限チェックテストが作成される
  - /admin/*にアクセスすると403エラー
  - 他人のプロフィール編集にアクセスすると403エラー
- [ ] 管理者の権限チェックテストが作成される
  - /admin/*に正常にアクセス可能
  - 全ユーザーのプロフィール編集が可能

### テスト実行
- [ ] `php artisan test`で全テストが成功する
- [ ] テストカバレッジレポートが生成される
- [ ] 主要機能のカバレッジが80%以上

## 実装タスク

### 1. 認証機能テスト作成
```bash
php artisan make:test Auth/RegisterTest
php artisan make:test Auth/LoginTest
php artisan make:test Auth/LogoutTest
```

**RegisterTest.php 実装例**:
```php
<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_with_valid_data()
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('profiles', ['user_id' => User::first()->id]);
    }

    /** @test */
    public function registration_fails_with_invalid_email()
    {
        $response = $this->post('/register', [
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function registration_fails_with_short_password()
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
```

### 2. プロフィール編集機能テスト作成
```bash
php artisan make:test ProfileControllerTest
```

**ProfileControllerTest.php 実装例**:
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_view_profile_edit_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard/profile/edit');

        $response->assertOk();
        $response->assertViewIs('profile.edit');
    }

    /** @test */
    public function unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/dashboard/profile/edit');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function user_can_update_profile_with_valid_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'name' => 'Updated Name',
            'biography' => 'Updated biography',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'name' => 'Updated Name',
            'biography' => 'Updated biography',
        ]);
    }

    /** @test */
    public function profile_update_fails_with_name_exceeding_50_characters()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/dashboard/profile', [
            'name' => str_repeat('a', 51),
            'biography' => 'Test biography',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
```

### 3. 動画機能テスト作成
```bash
php artisan make:test VideoControllerTest
```

**VideoControllerTest.php 実装例**:
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VideoControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_upload_video()
    {
        Storage::fake('s3');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.mp4', 10000, 'video/mp4');

        $response = $this->actingAs($user)->post('/dashboard/videos', [
            'video' => $file,
        ]);

        $response->assertRedirect('/dashboard/videos');
        $this->assertDatabaseHas('videos', [
            'user_id' => $user->id,
            'original_filename' => 'test.mp4',
        ]);
    }

    /** @test */
    public function user_can_delete_own_video()
    {
        $user = User::factory()->create();
        $video = Video::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/dashboard/videos/{$video->id}");

        $response->assertRedirect('/dashboard/videos');
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    /** @test */
    public function user_cannot_delete_others_video()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $video = Video::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->delete("/dashboard/videos/{$video->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('videos', ['id' => $video->id]);
    }
}
```

### 4. 管理者機能テスト作成
```bash
php artisan make:test Admin/UserControllerTest
```

**Admin/UserControllerTest.php 実装例**:
```php
<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_access_user_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
    }

    /** @test */
    public function regular_user_cannot_access_user_list()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/admin/users');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin)->delete("/admin/users/{$user->id}");

        $response->assertRedirect('/admin/users');
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
```

### 5. テストカバレッジ確認
```bash
# Xdebugを有効化してカバレッジ生成
php artisan test --coverage
```

## 検証方法

### テスト実行
```bash
# 全テスト実行
php artisan test

# 特定のテストクラスのみ実行
php artisan test --filter=RegisterTest

# カバレッジレポート生成（HTML形式）
php artisan test --coverage-html coverage
```

### 期待される結果
- 全テストがPASS
- テストカバレッジが80%以上
- 認証、プロフィール、動画、管理者機能の全てが網羅される

## 関連Issue
- #23: 単体テスト実装（Model/Service）
- #25: セキュリティ強化（XSS/CSRF/SQLi対策）

## 設計書参照
- `docs/06_routing.md`: ルート定義
- `docs/07_screen_design.md`: バリデーションルール
- `docs/05_data_flow.md`: データフロー

## 備考

### テストデータベース設定
`phpunit.xml`でテスト用DB設定:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Factory使用
User/Profile/VideoのFactoryを活用してテストデータを効率的に生成

### S3モック
`Storage::fake('s3')`でS3操作をモック化し、実際のS3アクセスなしでテスト可能
