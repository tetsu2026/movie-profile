<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\DB;

class RagService
{
    private const SIMILARITY_THRESHOLD = 0.3;
    private const TOP_K = 3;

    public function __construct(
        private EmbeddingService $embeddingService,
    ) {}

    /**
     * 質問に関連するチャンクを検索する
     *
     * @param string $query ユーザーの質問
     * @return array 類似度の高いチャンク（最大3件）
     */
    public function retrieve(string $query): array
    {
        $queryVector = $this->embeddingService->embed($query);
        $vectorString = '[' . implode(',', $queryVector) . ']';

        $chunks = DB::connection('pgsql_chatbot')
            ->select("
                SELECT
                    id,
                    source_path,
                    chunk_index,
                    content,
                    1 - (embedding <=> ?) as similarity
                FROM faq_chunks
                WHERE 1 - (embedding <=> ?) >= ?
                ORDER BY similarity DESC
                LIMIT ?
            ", [$vectorString, $vectorString, self::SIMILARITY_THRESHOLD, self::TOP_K]);

        return array_map(fn ($chunk) => [
            'id' => $chunk->id,
            'source_path' => $chunk->source_path,
            'chunk_index' => $chunk->chunk_index,
            'content' => $chunk->content,
            'similarity' => round($chunk->similarity, 4),
        ], $chunks);
    }
}
