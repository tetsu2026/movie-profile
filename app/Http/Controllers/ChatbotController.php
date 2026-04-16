<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatbotMessageRequest;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(
        private ChatbotService $chatbotService,
    ) {}

    /**
     * チャットボットにメッセージを送信する
     */
    public function message(ChatbotMessageRequest $request): JsonResponse
    {
        $result = $this->chatbotService->handle(
            $request->user(),
            $request->validated('message'),
        );

        return response()->json($result);
    }

    /**
     * 会話履歴を取得する
     */
    public function history(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 20), 50);

        $messages = $request->user()
            ->chatMessages()
            ->orderBy('created_at')
            ->limit($limit)
            ->get(['role', 'content', 'created_at']);

        return response()->json(['messages' => $messages]);
    }
}
