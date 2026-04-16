# Issue #28: チャットボット機能実装（Phase 1: FAQ RAG最小構成）

## 背景 / 目的

ログイン後のユーザー向けに、操作ヘルプFAQに回答するAIチャットボットを実装する。RAG(Retrieval-Augmented Generation)方式で、プロジェクトの `docs/` ドキュメントを知識源とする。Phase 1では最小構成のRAG(ベクトル検索のみ)を実装し、動作確認後にPhase 2以降で拡張する。

- **依存**: #7（ダッシュボード実装、`layouts/app.blade.php`の存在）
- **ラベル**: backend, frontend, infrastructure, AI/ML
- **計画書**: `docs/plans/chatbot_rag_phase1.md`

---

## 前提作業（実装前）

### AWS側の準備
1. AWS Bedrock モデルアクセス有効化（コンソール → Bedrock → Model access）:
   - `amazon.nova-lite-v1:0`
   - `amazon.titan-embed-text-v2:0`
2. リージョン: `ap-northeast-1`(東京)
3. IAMユーザー/ロールに `bedrock:InvokeModel` 権限付与
4. アクセスキー・シークレットキーを発行

### ローカル側の準備
1. `.env` にBedrockクレデンシャル・Postgres接続情報を追加
2. Docker Desktopが起動していること

---

## スコープ / 作業項目

### 1. パッケージ追加
```bash
composer require aws/aws-sdk-php
```

### 2. Docker Compose設定（Postgres追加）
- `docker-compose.yml` に `postgres` サービス追加
- `pgvector/pgvector:pg16` イメージを使用（pgvector拡張プリインストール済み）
- ボリューム: `postgres_data`
- ポート: 5433（MySQL 3306と衝突回避）

### 3. 環境変数設定
- `.env.example` に以下を追記:
```
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-northeast-1
BEDROCK_LLM_PROVIDER=nova
BEDROCK_NOVA_MODEL_ID=amazon.nova-lite-v1:0
BEDROCK_EMBED_MODEL_ID=amazon.titan-embed-text-v2:0

DB_CHATBOT_CONNECTION=pgsql_chatbot
DB_CHATBOT_HOST=postgres
DB_CHATBOT_PORT=5432
DB_CHATBOT_DATABASE=chatbot
DB_CHATBOT_USERNAME=chatbot
DB_CHATBOT_PASSWORD=secret
```

### 4. Laravel接続設定
- `config/database.php` に `pgsql_chatbot` 接続を追加
- `config/services.php` に Bedrock 設定追加

### 5. マイグレーション作成

#### MySQL側: chat_messages
```bash
php artisan make:migration create_chat_messages_table
```
```php
Schema::create('chat_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('role', ['user', 'assistant']);
    $table->text('content');
    $table->json('context_chunks')->nullable();
    $table->timestamp('created_at')->useCurrent();
    $table->index(['user_id', 'created_at']);
});
```

#### Postgres側: faq_chunks
```bash
php artisan make:migration create_faq_chunks_table --database=pgsql_chatbot
```
```php
DB::connection('pgsql_chatbot')->statement('CREATE EXTENSION IF NOT EXISTS vector');

Schema::connection('pgsql_chatbot')->create('faq_chunks', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('source_path', 500);
    $table->integer('chunk_index');
    $table->text('content');
    // pgvector型はLaravel標準にないためstatementで追加
    $table->integer('tokens');
    $table->timestamp('updated_at')->useCurrent();
    $table->index('source_path');
});

DB::connection('pgsql_chatbot')->statement(
    'ALTER TABLE faq_chunks ADD COLUMN embedding vector(1024) NOT NULL'
);
DB::connection('pgsql_chatbot')->statement(
    'CREATE INDEX idx_faq_chunks_embedding ON faq_chunks USING hnsw (embedding vector_cosine_ops)'
);
```

### 6. Eloquent Model作成

- `app/Models/ChatMessage.php` (MySQL接続、`context_chunks` は array cast)
- `app/Models/FaqChunk.php` (`$connection = 'pgsql_chatbot'`)

### 7. Service層実装

#### app/Services/Chatbot/LLMClientInterface.php
```php
interface LLMClientInterface {
    public function generate(string $systemPrompt, array $messages, int $maxTokens): string;
}
```

#### app/Services/Chatbot/BedrockClient.php (NovaClient実装)
- `generate()` で `bedrock-runtime` の `InvokeModel` 呼び出し
- `model_id` は `config('services.bedrock.nova_model_id')`

#### app/Services/Chatbot/EmbeddingService.php
- Titan Embeddings v2 呼び出し
- 入力: 文字列 / 出力: `float[1024]`

#### app/Services/Chatbot/RagService.php
- `retrieve(string $query): array` メソッド
  - 質問をEmbeddingServiceでベクトル化
  - Postgres `faq_chunks` にpgvector演算子`<=>`でコサイン類似度検索
  - `1 - (embedding <=> $queryVec)` が類似度（0〜1、高いほど類似）
  - 類似度0.6未満のチャンクを除外
  - top-3を返却

#### app/Services/Chatbot/ChatbotService.php
- `handle(User $user, string $question): array` メソッド
  1. `chat_messages` にユーザー発言を保存
  2. 直近5件の履歴取得
  3. `RagService::retrieve()` でチャンク取得
  4. ヒットあり: システムプロンプト + チャンク + 履歴 + 質問で `LLMClient::generate()`
  5. ヒットなし: 固定応答「わかりません、具体的に教えてください」
  6. `chat_messages` にアシスタント応答保存（context_chunks記録）
  7. `['message' => ..., 'context_chunks' => ...]` を返却

### 8. Controller実装

#### app/Http/Controllers/ChatbotController.php
- `message(ChatbotMessageRequest $request)`:
  - `auth()->user()` と `$request->validated('message')` を `ChatbotService` に渡す
  - JSON で応答返却
- `history(Request $request)`:
  - `auth()->user()->chatMessages()->orderBy('created_at')->limit($limit)->get()` をJSON返却

#### app/Http/Requests/ChatbotMessageRequest.php
- `message`: `required|string|max:2000`

### 9. ルーティング追加

`routes/web.php`:
```php
Route::middleware('auth')->group(function () {
    Route::prefix('chatbot')->name('chatbot.')->group(function () {
        Route::post('/message', [ChatbotController::class, 'message'])
            ->middleware('throttle:10,1')
            ->name('message');
        Route::get('/history', [ChatbotController::class, 'history'])->name('history');
    });
});
```

### 10. インデックス化コマンド作成

`app/Console/Commands/ChatbotIndexCommand.php`:
```bash
php artisan make:command ChatbotIndexCommand
```
- シグネチャ: `chatbot:index {--fresh : 既存チャンクを全削除してから実行}`
- 処理:
  1. `docs/` 配下の `.md` ファイルを再帰取得
  2. Markdownを `##` 見出し単位で分割
  3. 長いセクションは500トークンごとに分割、前後100トークンのオーバーラップ
  4. 各チャンクをTitan Embeddingsでベクトル化
  5. Postgres `faq_chunks` にINSERT（`--fresh`時は既存を削除）
  6. 完了ログ: チャンク数・トークン消費

### 11. フロントエンド実装

#### resources/views/components/chatbot-widget.blade.php
- Alpine.js の `x-data` でウィジェット状態管理
- 右下固定のチャットボタン（未展開時）
- 展開パネル（400x600px）
  - ヘッダー（タイトル・閉じるボタン）
  - 会話履歴エリア（スクロール可、`role="log" aria-live="polite"`）
  - 入力エリア（textarea + 送信ボタン）
- 初回展開時に `GET /chatbot/history` で履歴取得
- 送信時に `POST /chatbot/message` で応答取得
- CSRF token必須: `@csrf` またはヘッダー経由
- エスケープ: `{{ message.content }}` でBladeが自動エスケープ

#### resources/views/layouts/app.blade.php（変更）
- `</body>` 直前に `<x-chatbot-widget />` を追加
- `@auth` で認証時のみ表示

### 12. 動作確認
1. `docker-compose up -d`
2. `php artisan migrate`
3. `php artisan migrate --database=pgsql_chatbot`
4. `php artisan chatbot:index`
5. ブラウザでログイン → 右下チャットボタン表示確認
6. 質問送信 → 応答表示確認

---

## ゴール / 完了条件（Acceptance Criteria）

- [ ] Docker環境で Postgres + pgvector が起動する
- [ ] `php artisan migrate` と `php artisan migrate --database=pgsql_chatbot` が成功する
- [ ] `php artisan chatbot:index` で `docs/` 配下のMarkdownがチャンク化され `faq_chunks` にINSERTされる
- [ ] ログイン後の全画面右下にチャットボタンが表示される
- [ ] チャットボタンクリックで展開パネルが表示される
- [ ] 質問送信で Nova Lite からの応答が表示される
- [ ] 会話履歴が `chat_messages` に保存される
- [ ] マルチターン会話で直近5件の文脈が参照される（「それは何？」のような指示語が解釈される）
- [ ] 類似度0.6未満のときは「わかりません」応答が返る
- [ ] レート制限 10req/min が動作する
- [ ] 2000文字超の入力がバリデーションで弾かれる
- [ ] 未認証ユーザーは `/chatbot/*` にアクセスできない（401）
- [ ] LLMClientInterfaceによりモデル差し替えが可能な構造になっている
- [ ] Feature/Unitテストが通過する
- [ ] セキュリティヘッダ・XSS対策・CSRF保護が機能する

---

## テスト

### Feature テスト
- `tests/Feature/Chatbot/ChatbotControllerTest.php`
  - 認証必須の確認
  - バリデーション（空・2000文字超）
  - レート制限
  - 正常応答

### Unit テスト
- `tests/Unit/Chatbot/RagServiceTest.php`
  - 類似度閾値フィルタ
  - top-3抽出
- `tests/Unit/Chatbot/ChatbotServiceTest.php`
  - 履歴取得・保存
  - ヒットなし時の固定応答
- BedrockClient, EmbeddingServiceは外部依存のためモック化

---

## 注意事項

- Bedrockへの実通信はテストで発生させない（モック必須）
- 本番はPostgresをスナップショット運用するため、`docs/operations/postgres_snapshot_operation.md` を参照
- Phase 2以降で以下を追加予定（今回は実装しない）:
  - Tool Use（ユーザーデータ参照）
  - ハイブリッド検索（BM25）
  - 会話履歴検索

---

## 関連ドキュメント

- 要件定義: `docs/requirements/01_requirements.md` §4.5
- アーキテクチャ: `docs/design-docs/02_architecture.md`
- DB設計: `docs/design-docs/03_database.md`
- データフロー: `docs/design-docs/05_data_flow.md` §7〜§8
- ルーティング: `docs/design-docs/06_routing.md`
- 画面設計: `docs/design-docs/07_screen_design.md`
- ER図: `docs/design-docs/09_er.md`
- 運用手順: `docs/operations/postgres_snapshot_operation.md`
- 実装計画: `docs/plans/chatbot_rag_phase1.md`
