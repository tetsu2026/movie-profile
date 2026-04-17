# チャットボット導入プラン（Phase 1: FAQ RAG最小構成）

## Context

動画プロフィール(Laravel版)のログイン後ユーザー向けに、**操作ヘルプFAQに回答するチャットボット**を導入する。RAG学習目的も兼ねるため、Phase 1では**最小構成のRAG(ベクトル検索のみ)**を実装し、動作確認 → 次フェーズで拡張する。

**Phase 1スコープ(今回実装)**:
- FAQ RAG(ベクトル検索のみ、ハイブリッド検索はPhase 3送り)
- ユーザーデータ参照なし(Tool UseはPhase 2送り)
- **マルチターン会話対応**: 直近5件の履歴をコンテキストに含めてLLMへ送信

**データ不足懸念への対応(Phase 1版)**:
- RAGソースに `docs/design-docs/*`, `docs/issues/*` を活用 → 既に数百チャンク確保可能
- 類似度閾値を設定し、ヒットしなければ「わかりません」で逃げる(誤回答を出さない)

---

## LLM/埋め込み構成

**採用**: **AWS Bedrock + Amazon Nova Lite + Amazon Titan Embeddings v2**

**LLM差し替え設計**: `LLMClientInterface` を定義し、実装は `BedrockClient`（Bedrock Converse API 経由）のみ。モデル切替は `.env` の `BEDROCK_MODEL_ID` を変更するだけで Nova / Claude / Llama 等に差し替え可能（Converse API がモデル固有のリクエスト形式差異を吸収するため、PHPコード変更不要）。

理由:
- AWS既存環境(EC2/S3/RDS)とIAMで統一管理
- Nova LiteはClaude Haikuの1/10以下の料金(入力$0.06/1M、出力$0.24/1M)で日本語FAQ程度なら実用品質
- Titan Embeddings v2(1024次元)は日本語対応・Bedrockで統一

**前提作業(ユーザー側)**:
- AWSコンソール → Bedrock → Model access で以下を有効化
  - `amazon.nova-lite-v1:0`
  - `amazon.titan-embed-text-v2:0`
- リージョン: `ap-northeast-1`(東京)推奨
- IAMに `bedrock:InvokeModel` 権限付与

---

## アーキテクチャ

```
[Blade + Alpine.js ウィジェット] ← 全ページ右下フローティング
        ↓ POST /chatbot/message
[ChatbotController]
        ↓
[ChatbotService]
        ├→ [chat_messages から直近5件取得] (文脈構築)
        ↓
[RagService] ── EmbeddingService (Titan) → 質問を1024次元ベクトル化
        ↓
[Postgres pgvector でコサイン類似度検索 → 閾値0.3・top-3抽出]
        ↓ (ヒットあり)                                       ↓ (ヒットなし)
[BedrockClient (Converse API / BEDROCK_MODEL_ID)]            [固定応答「情報が見つかりませんでした」]
  ← システムプロンプト + top-3チャンク                         (LLM呼び出さず、context_chunks=NULL で保存)
    + 直近5件履歴(フォールバックペア除外) + 質問
        ↓
[ChatMessage に保存して返却]
```

### DB構成: MySQL(アプリ本体) + Postgres(チャットボット専用・分離)

**採用理由**:
- アプリ本体の既存MySQLは触らない → 既存機能への影響ゼロ
- チャットボット用に**Postgres + pgvector**を新設 → RAG定石を学べる
- 本番はスナップショット運用でコスト抑制(月$2程度)

### 運用方針(B3: スナップショット運用)

```
[ローカル開発] docker-compose.yml に postgres サービス追加 → 無料
[本番利用時]   スナップショットから復元(10〜30分) → 利用 → 終了時にスナップショット→削除
[待機時]      スナップショットのみ保存(月$1.9程度)
```

**前提**: 本機能は**自己学習目的**で使用。外部ユーザーへのサービス提供はしないため、DB停止中のUX制約(初回10〜30分の起動待ち等)は許容する。

---

## 実装対象ファイル(Phase 1)

| パス | 種別 | 役割 |
|---|---|---|
| `app/Http/Controllers/ChatbotController.php` | 新規 | メッセージ受信 |
| `app/Services/Chatbot/ChatbotService.php` | 新規 | オーケストレーション |
| `app/Services/Chatbot/RagService.php` | 新規 | ベクトル検索 |
| `app/Services/Chatbot/EmbeddingService.php` | 新規 | Titan呼び出し |
| `app/Services/Chatbot/BedrockClient.php` | 新規 | Bedrock Converse API 呼び出し（`BEDROCK_MODEL_ID` で Nova / Claude / Llama 切替可能）|
| `app/Console/Commands/ChatbotIndexCommand.php` | 新規 | **FAQインデックス化バッチ** (`php artisan chatbot:index` で`docs/*.md`をチャンク分割→Titan埋め込み生成→Postgres `faq_chunks`に投入) |
| `app/Models/ChatMessage.php` | 新規 | 会話履歴 |
| `app/Models/FaqChunk.php` | 新規 | チャンク+ベクトル |
| `database/migrations/xxxx_create_chat_messages_table.php` | 新規 | MySQL側 |
| `database/migrations/2025_xx_xx_create_faq_chunks_table.php` | 新規 | **pgsql接続指定** |
| `config/database.php` | 変更 | `pgsql_chatbot` 接続追加 |
| `docker-compose.yml` | 変更 | `postgres` サービス追加 |
| `docs/operations/chatbot_db_snapshot.md` | 新規 | 本番スナップショット運用手順 |
| `resources/views/components/chatbot-widget.blade.php` | 新規 | Alpine.js UI |
| `resources/views/layouts/app.blade.php` | 変更 | ウィジェット差し込み |
| `routes/web.php` | 変更 | /chatbot/* ルート |
| `config/services.php` | 変更 | Bedrock設定 |
| `.env.example` | 変更 | AWS認証情報のキー追加 |
| `composer.json` | 変更 | `aws/aws-sdk-php` 追加 |

---

## DB設計(Phase 1)

```sql
-- chat_messages: 会話履歴
id              BIGINT PK
user_id         BIGINT FK -> users.id
role            ENUM('user', 'assistant')
content         TEXT
context_chunks  JSON NULL  -- 参照したチャンクIDの配列(デバッグ用)
created_at      TIMESTAMP

-- faq_chunks: FAQチャンク(Postgres側・pgvector使用)
id              BIGSERIAL PK
source_path     VARCHAR(500)  -- 例: docs/design-docs/02_architecture.md
chunk_index     INT
content         TEXT           -- チャンク原文
embedding       VECTOR(1024)   -- pgvector型・Titan v2の次元数
tokens          INT
updated_at      TIMESTAMP
INDEX USING hnsw (embedding vector_cosine_ops)  -- HNSW近似最近傍インデックス
INDEX(source_path)

-- ※ chat_messages は MySQL 側(既存DBに同居)
-- ※ faq_chunks のみ Postgres 側(チャットボット専用DB)
```

---

## フロントエンド(Alpine.js)

- `resources/views/components/chatbot-widget.blade.php`
- 右下固定ボタン → クリックで展開(幅400px×高600px)
- Alpine.jsで状態管理:
  ```js
  x-data="{ open: false, messages: [], input: '', loading: false }"
  ```
- fetch API で `/chatbot/message` にPOST
- 送信中はスピナー表示
- @csrf必須、`{{ }}`でエスケープ

---

## セキュリティ・制約

- `auth` middleware必須
- レート制限: `throttle:10,1`(10req/min per user)
- プロンプトインジェクション対策: ユーザー入力を `<user_question>` タグで分離
- 入力上限: 100文字
- 出力上限: 500トークン
- Bedrockクレデンシャルは `.env` → `config/services.php` 経由、コードにハードコード禁止
- 応答に含める情報はRAGヒットチャンクのみ(ユーザーDBアクセスはPhase 2でTool Use導入時)

---

## コスト試算

### LLM・埋め込み(Nova Lite採用・100ユーザー×月50QA)

- 入力: 平均2000トークン × 5000回 = 1000万トークン × $0.06 = **$0.60**
- 出力: 平均300トークン × 5000回 = 150万トークン × $0.24 = **$0.36**
- 埋め込み: 初期300チャンク + 日次更新 ≒ 数万トークン × $0.02 = **$0.01**
- 小計: **約$1/月**

### DB(B3案: スナップショット運用)

- スナップショット保存(20GB): $0.095 × 20 = $1.9/月
- 復元時のみインスタンス稼働(月10時間想定): $0.017 × 10 = $0.17
- 小計: **約$2/月**

### 合計

- **月額約$3** → 既存MySQL($15)と合わせて**全体$18程度**、目標$30以下を達成

---

## 検証方法

1. `.env` にAWSクレデンシャル + Postgres接続情報を設定
2. `docker-compose up -d` (MySQL + Postgres + app が起動)
3. `docker-compose exec app php artisan migrate` (MySQL側 chat_messages)
4. `docker-compose exec app php artisan migrate --database=pgsql_chatbot` (Postgres側 faq_chunks、pgvector拡張有効化含む)
5. `docker-compose exec app php artisan chatbot:index`
   - `docs/` 配下のMarkdownをチャンク化 → 埋め込み生成 → `faq_chunks`に保存
   - 完了ログで件数とトークン消費を表示
4. ブラウザでログイン → 右下にチャットボタン出現を確認
5. テスト質問:
   - 「動画のアップロード方法を教えて」→ FAQヒット確認
   - 「パスワードリセットはどうする？」→ ヒットなし時の応答確認
   - 「今日の天気は？」→ スコープ外応答確認
6. `php artisan test --filter=Chatbot` でFeature/Unitテスト
7. AWS Cost Explorer で初日の利用料が $0.10 以内か確認

---

## Phase 2以降の拡張(今回は実装しない)

- **Phase 2**: Tool Use追加(ユーザーのプロフィール・動画データ照会)
- **Phase 3**: ハイブリッド検索(BM25 + ベクトル + RRF統合)
- **Phase 4**: 会話履歴を文脈に含める、フィードバックボタン、評価メトリクス(Recall@5等)

---

## 学習ポイント(RAG勉強目的)

Phase 1で体験できること:
- **チャンク分割**: Markdown見出し単位 + オーバーラップ100トークン
- **埋め込み生成**: Titan v2の使い方、1024次元ベクトルの扱い
- **類似度計算**: コサイン類似度をPHPで実装
- **閾値設計**: ヒットなし判定のしきい値をどう決めるか
- **プロンプト設計**: Context挿入位置、システムプロンプトの書き方
- **妥協判断**: MySQL+JSON保持の限界、いつpgvectorへ移行すべきか

Phase 3以降でさらに:
- ハイブリッド検索の実装とRRF統合
- Tool Use(Function Calling)との組み合わせ判断
