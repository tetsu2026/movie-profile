<?php

namespace App\Services\Chatbot;

interface LLMClientInterface
{
    /**
     * LLMに応答を生成させる
     *
     * @param string $systemPrompt システムプロンプト
     * @param array $messages 会話履歴（['role' => 'user'|'assistant', 'content' => '...']の配列）
     * @param int $maxTokens 最大出力トークン数
     * @return string 生成された応答テキスト
     */
    public function generate(string $systemPrompt, array $messages, int $maxTokens = 500): string;
}
