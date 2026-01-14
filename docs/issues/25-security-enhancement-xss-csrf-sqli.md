# Issue #25: セキュリティ強化（XSS/CSRF/SQLi対策）

## 概要
セキュリティ脆弱性をチェックし、XSS/CSRF/SQLインジェクション対策を徹底します。本番運用前に主要なセキュリティリスクを排除します。

## 依存関係
- **前提Issue**: #24（機能テスト実装完了）

## タスク領域
- backend
- frontend

## 受け入れ基準（AC）

### XSS（クロスサイトスクリプティング）対策
- [ ] 全てのBladeテンプレートで出力エスケープが徹底される
  - ユーザー入力データは`{{ }}`でエスケープ（`{!! !!}`の使用を最小限に）
  - `{!! !!}`を使用する箇所はコメントで理由を明記
- [ ] 経歴テキストの改行表示で`nl2br()`ではなく`whitespace-pre-wrap`を使用
- [ ] JavaScriptにユーザー入力を埋め込む場合は`@json()`ディレクティブを使用
- [ ] HTMLタグを含むユーザー入力は`strip_tags()`または`htmlspecialchars()`で無害化

### CSRF（クロスサイトリクエストフォージェリ）対策
- [ ] 全てのPOST/PUT/DELETEフォームに`@csrf`ディレクティブが含まれる
  - ユーザー登録フォーム
  - ログインフォーム
  - プロフィール編集フォーム
  - 動画アップロードフォーム
  - 動画削除フォーム
  - 管理者ユーザー編集フォーム
  - 管理者ユーザー削除フォーム
- [ ] Ajaxリクエスト時にCSRFトークンがヘッダーに含まれる
- [ ] `VerifyCsrfToken`ミドルウェアが全ルートに適用される（API除く）

### SQLインジェクション対策
- [ ] 全てのDB操作がEloquent ORMまたはクエリビルダで実装される
- [ ] 生SQL（`DB::raw()`, `DB::statement()`）が使用されていない
- [ ] プリペアドステートメントが使用される（Eloquentのデフォルト動作）
- [ ] ユーザー入力がSQLクエリに直接結合されていない

### ファイルアップロードセキュリティ
- [ ] ファイルアップロード時のMIMEタイプチェックが実施される
  - 動画ファイル: mp4, mov, avi, wmv のみ許可
  - MIMEタイプ検証: `mimes:mp4,mov,avi,wmv`
- [ ] ファイルサイズ制限が適切に設定される（100MB以内）
- [ ] アップロードファイル名にユニークID（UUID）を付与し、元のファイル名を直接使用しない
- [ ] 実行可能ファイル（.php, .exe, .sh等）のアップロードが拒否される

### パスワードセキュリティ
- [ ] パスワードが`bcrypt`（またはLaravel標準の`Hash::make()`）でハッシュ化される
- [ ] パスワードが平文でログに記録されない
- [ ] パスワードリセット機能実装時にトークンの有効期限が設定される（Phase 2）

### S3バケットセキュリティ
- [ ] S3バケットのアクセス権限が適切に設定される
  - エンコード済み動画: パブリック読み取り可
  - 元動画: プライベート（アプリケーション経由でのみアクセス）
  - 書き込み: 不可（アプリケーションのみ）
- [ ] S3バケットポリシーで不要な権限が削除される
- [ ] S3署名付きURLの有効期限が設定される（将来的にプライベート動画対応時）

### 認証・認可セキュリティ
- [ ] 未認証ユーザーが保護されたルート（/dashboard/*）にアクセスできない
- [ ] 一般ユーザーが管理者専用ルート（/admin/*）にアクセスすると403エラー
- [ ] 他人のリソース（プロフィール、動画）を編集・削除できない
  - 動画削除時に所有権チェック
  - プロフィール編集時にユーザーIDチェック
- [ ] セッションタイムアウトが適切に設定される（デフォルト120分）

### セキュリティヘッダー設定
- [ ] `X-Frame-Options: DENY`（クリックジャッキング対策）
- [ ] `X-Content-Type-Options: nosniff`（MIMEタイプスニッフィング対策）
- [ ] `X-XSS-Protection: 1; mode=block`（XSSフィルタ有効化）
- [ ] `Strict-Transport-Security`（HTTPS強制、本番環境のみ）

## 実装タスク

### 1. Bladeテンプレート全体のXSSチェック
```bash
# 全Bladeファイルで {!! !!} の使用箇所を検索
grep -r "{!! " resources/views/
```

**修正例**:
```blade
<!-- ❌ XSS脆弱性あり -->
<p>{!! $profile->biography !!}</p>

<!-- ✅ 安全な実装 -->
<p class="whitespace-pre-wrap">{{ $profile->biography }}</p>
```

### 2. CSRF対策チェック
全フォームに`@csrf`を追加:
```blade
<form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('PUT')
    <!-- フォーム内容 -->
</form>
```

### 3. SQLインジェクション対策チェック
```bash
# 生SQLの使用箇所を検索
grep -r "DB::raw\|DB::statement" app/
```

**安全な実装例**:
```php
// ❌ 危険: ユーザー入力を直接結合
$users = DB::select("SELECT * FROM users WHERE email = '{$email}'");

// ✅ 安全: Eloquent ORM使用
$users = User::where('email', $email)->get();

// ✅ 安全: クエリビルダのプリペアドステートメント
$users = DB::table('users')->where('email', $email)->get();
```

### 4. ファイルアップロードバリデーション強化
**StoreVideoRequest.php**:
```php
public function rules()
{
    return [
        'video' => [
            'required',
            'file',
            'mimes:mp4,mov,avi,wmv',
            'max:102400', // 100MB
            function ($attribute, $value, $fail) {
                // 実際のMIMEタイプをチェック（偽装対策）
                $mimeType = $value->getMimeType();
                $allowedMimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv'];
                if (!in_array($mimeType, $allowedMimes)) {
                    $fail('動画ファイルの形式が無効です');
                }
            },
        ],
    ];
}
```

### 5. 動画ファイル名のサニタイズ
**VideoController.php**:
```php
use Illuminate\Support\Str;

public function store(StoreVideoRequest $request)
{
    $file = $request->file('video');
    $extension = $file->getClientOriginalExtension();

    // ユニークファイル名を生成（元のファイル名を直接使用しない）
    $uniqueFilename = Str::uuid() . '.' . $extension;

    // S3にアップロード
    $path = $file->storeAs(
        "users/{$user->id}/original",
        $uniqueFilename,
        's3'
    );

    // 元のファイル名はDBに保存（表示用）
    Video::create([
        'user_id' => auth()->id(),
        'original_filename' => $file->getClientOriginalName(),
        'original_path' => $path,
        'status' => 'uploading',
    ]);
}
```

### 6. セキュリティヘッダー設定
**Middleware: SecureHeaders.php** を作成:
```php
<?php

namespace App\Http\Middleware;

use Closure;

class SecureHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // 本番環境のみHSTSを有効化
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
```

**app/Http/Kernel.php** に登録:
```php
protected $middleware = [
    // ...
    \App\Http\Middleware\SecureHeaders::class,
];
```

### 7. S3バケットポリシー設定
**CloudFormation（本番環境）**:
```yaml
S3Bucket:
  Type: AWS::S3::Bucket
  Properties:
    BucketName: !Sub ${ProjectName}-videos-${Environment}
    PublicAccessBlockConfiguration:
      BlockPublicAcls: true
      BlockPublicPolicy: false
      IgnorePublicAcls: true
      RestrictPublicBuckets: false
    CorsConfiguration:
      CorsRules:
        - AllowedOrigins:
            - !Sub https://${DomainName}
          AllowedMethods:
            - GET
          AllowedHeaders:
            - "*"

S3BucketPolicy:
  Type: AWS::S3::BucketPolicy
  Properties:
    Bucket: !Ref S3Bucket
    PolicyDocument:
      Statement:
        - Effect: Allow
          Principal: "*"
          Action: s3:GetObject
          Resource: !Sub ${S3Bucket.Arn}/users/*/encoded/*
```

### 8. 所有権チェック強化
**VideoController.php**:
```php
public function destroy($id)
{
    $video = Video::findOrFail($id);

    // 所有権チェック
    if ($video->user_id !== auth()->id()) {
        abort(403, 'この動画を削除する権限がありません');
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

    return redirect()->route('videos.index')->with('success', '動画を削除しました');
}
```

## 検証方法

### 1. XSS脆弱性テスト
```php
// テストケース: ユーザー登録時にスクリプトタグを入力
$response = $this->post('/register', [
    'email' => 'test@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);

$user = User::where('email', 'test@example.com')->first();

// プロフィール更新でスクリプトタグを含む経歴を入力
$this->actingAs($user)->put('/dashboard/profile', [
    'name' => '<script>alert("XSS")</script>',
    'biography' => '<img src=x onerror=alert("XSS")>',
]);

// 公開ページでエスケープされているか確認
$response = $this->get("/users/{$user->id}");
$response->assertDontSee('<script>', false);
$response->assertSee('&lt;script&gt;', false);
```

### 2. CSRF脆弱性テスト
```php
// CSRFトークンなしでPOSTリクエスト
$response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->post('/dashboard/profile', [
        'name' => 'Hacked',
    ]);

$response->assertStatus(419); // CSRF token mismatch
```

### 3. SQLインジェクションテスト
```php
// SQLインジェクション試行
$response = $this->post('/login', [
    'email' => "admin@example.com' OR '1'='1",
    'password' => "password' OR '1'='1",
]);

$response->assertSessionHasErrors(); // 認証失敗
```

### 4. ファイルアップロードセキュリティテスト
```php
// 不正なファイル形式のアップロード試行
$file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

$response = $this->actingAs($user)->post('/dashboard/videos', [
    'video' => $file,
]);

$response->assertSessionHasErrors('video'); // バリデーションエラー
```

## セキュリティチェックリスト

### コード全体の監査
- [ ] `grep -r "{!! " resources/views/` で危険な出力箇所をチェック
- [ ] `grep -r "DB::raw\|DB::statement" app/` で生SQLをチェック
- [ ] `grep -r "@csrf" resources/views/` で全フォームにCSRFトークンがあるか確認
- [ ] `grep -r "Hash::make\|bcrypt" app/` でパスワードハッシュ化を確認

### ツールによる自動チェック
```bash
# Laravel標準のセキュリティチェック
composer require --dev enlightn/enlightn
php artisan enlightn

# 依存パッケージの脆弱性チェック
composer audit
```

## 関連Issue
- #24: 機能テスト実装（Controller/Route）
- #26: パフォーマンス最適化（N+1問題/キャッシュ）

## 設計書参照
- `docs/01_requirements.md`: セキュリティ要件
- `docs/06_routing.md`: 認証・認可
- `CLAUDE.md`: セキュリティ観点

## 備考

### OWASPトップ10対策状況
1. **A01:2021 – Broken Access Control**: ✅ 認証・認可チェック実装
2. **A02:2021 – Cryptographic Failures**: ✅ パスワードハッシュ化、HTTPS使用
3. **A03:2021 – Injection**: ✅ Eloquent ORM使用、SQLインジェクション対策
4. **A04:2021 – Insecure Design**: ✅ 設計書に基づく安全な設計
5. **A05:2021 – Security Misconfiguration**: ✅ セキュリティヘッダー設定
6. **A06:2021 – Vulnerable Components**: ✅ composer audit で依存パッケージチェック
7. **A07:2021 – Identification and Authentication Failures**: ✅ Laravel Breeze使用
8. **A08:2021 – Software and Data Integrity Failures**: ✅ CSRF対策
9. **A09:2021 – Security Logging Failures**: ✅ CloudWatch Logs（Issue #22）
10. **A10:2021 – Server-Side Request Forgery**: N/A（本プロジェクトでは該当なし）

### 本番環境での追加設定
- HTTPS強制（Nginxまたはロードバランサー設定）
- WAF（AWS WAF）の導入（Phase 2）
- 定期的なセキュリティパッチ適用
