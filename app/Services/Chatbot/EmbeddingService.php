<?php

namespace App\Services\Chatbot;

use Aws\BedrockRuntime\BedrockRuntimeClient;

class EmbeddingService
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
        $this->modelId = config('services.bedrock.embed_model_id');
    }

    /**
     * テキストを埋め込みベクトルに変換する
     *
     * @param string $text 入力テキスト
     * @return array<float> 1024次元のベクトル
     */
    public function embed(string $text): array
    {
        $result = $this->client->invokeModel([
            'modelId' => $this->modelId,
            'contentType' => 'application/json',
            'accept' => 'application/json',
            'body' => json_encode([
                'inputText' => $text,
            ]),
        ]);

        $response = json_decode($result['body']->getContents(), true);

        return $response['embedding'];
    }
}
