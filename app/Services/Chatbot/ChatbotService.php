<?php

namespace App\Services\Chatbot;

use App\Models\ChatMessage;
use App\Models\User;

class ChatbotService
{
    private const HISTORY_LIMIT = 5;

    private const SYSTEM_PROMPT = <<<'PROMPT'
あなたは「動画プロフィール」サービスの操作ヘルプアシスタントです。
ユーザーからの質問に対して、以下の参考情報をもとに正確に回答してください。

ルール:
参考情報に含まれる内容のみを根拠に回答してください。
参考情報にない内容については「わかりません」と回答してください。
回答は簡潔で分かりやすい日本語で記述してください。
Markdown記法は絶対に使わないでください。太字(**text**)、見出し(#)、リスト記号(- や *)は禁止です。
手順を説明する場合は「1. 」「2. 」のように番号で記述してください。
ユーザーの操作を助けることが目的です。

参考情報:
{context}
PROMPT;

    private const HISTORY_ONLY_SYSTEM_PROMPT = <<<'PROMPT'
あなたは「動画プロフィール」サービスの操作ヘルプアシスタントです。
今回のユーザーの質問に対する新しい参考情報は見つかりませんでした。

ルール:
これまでの会話履歴に答えの根拠があれば、それをもとに回答してください（例: 要約する、短くする、初心者向けに言い換える など）。
履歴にも根拠がない場合は「申し訳ありませんが、ご質問の内容に関する情報が見つかりませんでした。もう少し具体的に教えていただけますか？」とだけ回答してください。
履歴にない事実を新たに作り出さないでください。
回答は簡潔で分かりやすい日本語で記述してください。
Markdown記法は絶対に使わないでください。太字(**text**)、見出し(#)、リスト記号(- や *)は禁止です。
手順を説明する場合は「1. 」「2. 」のように番号で記述してください。
PROMPT;

    private const FALLBACK_MESSAGE = '申し訳ありませんが、ご質問の内容に関する情報が見つかりませんでした。もう少し具体的に教えていただけますか？';

    public function __construct(
        private RagService $ragService,
        private LLMClientInterface $llmClient,
    ) {}

    /**
     * ユーザーの質問を処理して応答を返す
     *
     * @param User $user ユーザー
     * @param string $question 質問テキスト
     * @return array{message: string, context_chunks: array|null}
     */
    public function handle(User $user, string $question): array
    {
        ChatMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $question,
        ]);

        $failedAssistantIds = ChatMessage::where('user_id', $user->id)
            ->where('role', 'assistant')
            ->whereNull('context_chunks')
            ->pluck('id');

        $failedUserIds = $failedAssistantIds->map(function ($assistantId) use ($user) {
            return ChatMessage::where('user_id', $user->id)
                ->where('role', 'user')
                ->where('id', '<', $assistantId)
                ->orderByDesc('id')
                ->value('id');
        })->filter();

        $excludeIds = $failedAssistantIds->merge($failedUserIds)->all();

        $history = ChatMessage::where('user_id', $user->id)
            ->whereNotIn('id', $excludeIds)
            ->orderByDesc('created_at')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->values();

        $chunks = $this->ragService->retrieve($question);

        $messages = $history->map(fn ($msg) => [
            'role' => $msg->role,
            'content' => $msg->role === 'user'
                ? "<user_question>{$msg->content}</user_question>"
                : $msg->content,
        ])->toArray();

        if (empty($chunks)) {
            // 履歴が今回のユーザー質問のみ（=直前のアシスタント応答がない）ならフォールバック
            $hasPriorAssistant = $history->contains(fn ($msg) => $msg->role === 'assistant');

            if (! $hasPriorAssistant) {
                $response = self::FALLBACK_MESSAGE;
                $contextChunks = null;
            } else {
                // 履歴に根拠が残っている可能性があるのでLLMに継続させる
                $response = $this->llmClient->generate(self::HISTORY_ONLY_SYSTEM_PROMPT, $messages);
                $contextChunks = [];
            }
        } else {
            $context = collect($chunks)
                ->map(fn ($c) => "【{$c['source_path']}】\n{$c['content']}")
                ->implode("\n\n---\n\n");

            $systemPrompt = str_replace('{context}', $context, self::SYSTEM_PROMPT);

            $response = $this->llmClient->generate($systemPrompt, $messages);
            $contextChunks = array_map(fn ($c) => $c['id'], $chunks);
        }

        ChatMessage::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $response,
            'context_chunks' => $contextChunks,
        ]);

        return [
            'message' => $response,
            'context_chunks' => $contextChunks,
        ];
    }
}
