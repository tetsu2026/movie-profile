<?php

namespace App\Services\Chatbot;

use Aws\BedrockRuntime\BedrockRuntimeClient;

class BedrockClient implements LLMClientInterface
{
    private BedrockRuntimeClient $client;
    private string $modelId;

    public function __construct()
    {
        $config = [
            'region' => config('services.bedrock.region'),
            'version' => 'latest',
        ];

        $key = config('services.bedrock.key');
        $secret = config('services.bedrock.secret');
        if ($key && $secret) {
            $config['credentials'] = [
                'key' => $key,
                'secret' => $secret,
            ];
        }

        $this->client = new BedrockRuntimeClient($config);
        $this->modelId = config('services.bedrock.model_id');
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $systemPrompt, array $messages, int $maxTokens = 500): string
    {
        $body = [
            'messages' => array_map(fn ($msg) => [
                'role' => $msg['role'],
                'content' => [['text' => $msg['content']]],
            ], $messages),
            'system' => [['text' => $systemPrompt]],
            'inferenceConfig' => [
                'maxTokens' => $maxTokens,
                'temperature' => 0.3,
            ],
        ];

        $result = $this->client->converse([
            'modelId' => $this->modelId,
            'messages' => $body['messages'],
            'system' => $body['system'],
            'inferenceConfig' => $body['inferenceConfig'],
        ]);

        return $result['output']['message']['content'][0]['text'] ?? '';
    }
}
