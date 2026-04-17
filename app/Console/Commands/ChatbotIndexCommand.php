<?php

namespace App\Console\Commands;

use App\Models\FaqChunk;
use App\Services\Chatbot\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ChatbotIndexCommand extends Command
{
    protected $signature = 'chatbot:index {--fresh : 既存チャンクを全削除してから実行} {--path= : 特定ファイルのみインデックス（例: docs/user-manual.md）}';

    protected $description = 'docs/配下のMarkdownをチャンク化してfaq_chunksにインデックス登録する（差分更新）';

    private const MAX_TOKENS_PER_CHUNK = 500;
    private const OVERLAP_TOKENS = 100;

    public function handle(EmbeddingService $embeddingService): int
    {
        if ($this->option('fresh')) {
            FaqChunk::truncate();
            $this->info('既存チャンクを全削除しました。');
        }

        $mdFiles = $this->collectTargetFiles();

        if (empty($mdFiles)) {
            $this->info('インデックス対象のファイルがありません。');

            return Command::SUCCESS;
        }

        $totalChunks = 0;
        $totalTokens = 0;
        $skipped = 0;

        foreach ($mdFiles as $file) {
            $relativePath = 'docs/' . $file->getRelativePathname();
            $fileModified = \Carbon\Carbon::createFromTimestamp($file->getMTime());

            if (! $this->option('fresh') && $this->isUpToDate($relativePath, $fileModified)) {
                $this->line("  [{$relativePath}] 変更なし → スキップ");
                $skipped++;

                continue;
            }

            DB::connection('pgsql_chatbot')
                ->table('faq_chunks')
                ->where('source_path', $relativePath)
                ->delete();

            $content = $file->getContents();
            $sections = $this->splitByHeadings($content);

            foreach ($sections as $index => $section) {
                $subChunks = $this->splitByTokens($section);

                foreach ($subChunks as $subIndex => $chunk) {
                    $chunkIndex = $index * 100 + $subIndex;
                    $tokens = $this->estimateTokens($chunk);

                    $this->info("  [{$relativePath}] chunk #{$chunkIndex} ({$tokens} tokens)");

                    $embedding = $embeddingService->embed($chunk);
                    $vectorString = '[' . implode(',', $embedding) . ']';

                    DB::connection('pgsql_chatbot')->table('faq_chunks')->insert([
                        'source_path' => $relativePath,
                        'chunk_index' => $chunkIndex,
                        'content' => $chunk,
                        'embedding' => $vectorString,
                        'tokens' => $tokens,
                        'updated_at' => now(),
                    ]);

                    $totalChunks++;
                    $totalTokens += $tokens;
                }
            }
        }

        $this->info("完了: {$totalChunks}チャンク追加/更新, 合計{$totalTokens}トークン, {$skipped}ファイルスキップ");

        return Command::SUCCESS;
    }

    /**
     * インデックス対象のファイルを収集する
     */
    private function collectTargetFiles(): array
    {
        $docsPath = base_path('docs');

        if ($path = $this->option('path')) {
            $fullPath = base_path($path);
            if (! file_exists($fullPath)) {
                $this->error("ファイルが見つかりません: {$path}");

                return [];
            }

            $relativePath = str_replace($docsPath . '/', '', $fullPath);

            return [new \Symfony\Component\Finder\SplFileInfo($fullPath, dirname($relativePath), $relativePath)];
        }

        $files = File::allFiles($docsPath);

        return array_values(array_filter($files, fn ($f) => $f->getExtension() === 'md'));
    }

    /**
     * ファイルがインデックス済みで変更がないか判定する
     */
    private function isUpToDate(string $sourcePath, \Carbon\Carbon $fileModified): bool
    {
        $latestChunk = DB::connection('pgsql_chatbot')
            ->table('faq_chunks')
            ->where('source_path', $sourcePath)
            ->max('updated_at');

        if (! $latestChunk) {
            return false;
        }

        return $fileModified->lte(\Carbon\Carbon::parse($latestChunk));
    }

    /**
     * Markdownを##見出し単位で分割する
     */
    private function splitByHeadings(string $content): array
    {
        $sections = preg_split('/^(?=## )/m', $content);

        return array_values(array_filter(
            array_map('trim', $sections),
            fn ($s) => strlen($s) > 0,
        ));
    }

    /**
     * 長いセクションをトークン数で分割する（オーバーラップ付き）
     */
    private function splitByTokens(string $text): array
    {
        $tokens = $this->estimateTokens($text);

        if ($tokens <= self::MAX_TOKENS_PER_CHUNK) {
            return [$text];
        }

        $words = preg_split('/\s+/u', $text);
        $chunks = [];
        $start = 0;
        $wordsPerChunk = (int) (self::MAX_TOKENS_PER_CHUNK * 0.75);
        $overlapWords = (int) (self::OVERLAP_TOKENS * 0.75);

        while ($start < count($words)) {
            $end = min($start + $wordsPerChunk, count($words));
            $chunks[] = implode(' ', array_slice($words, $start, $end - $start));
            $start = $end - $overlapWords;
            if ($start >= count($words) - $overlapWords) {
                break;
            }
        }

        return $chunks;
    }

    /**
     * トークン数を概算する（日本語: 1文字≒1.5トークン、英語: 1単語≒1.3トークン）
     */
    private function estimateTokens(string $text): int
    {
        $jpChars = preg_match_all('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $text);
        $otherChars = mb_strlen($text) - $jpChars;

        return (int) ($jpChars * 1.5 + $otherChars * 0.4);
    }
}
