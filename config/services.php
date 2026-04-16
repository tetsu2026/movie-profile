<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'bedrock' => [
        'key' => env('BEDROCK_ACCESS_KEY_ID'),
        'secret' => env('BEDROCK_SECRET_ACCESS_KEY'),
        'region' => env('BEDROCK_REGION', 'ap-northeast-1'),
        'llm_provider' => env('BEDROCK_LLM_PROVIDER', 'nova'),
        'model_id' => env('BEDROCK_MODEL_ID', 'amazon.nova-lite-v1:0'),
        'embed_model_id' => env('BEDROCK_EMBED_MODEL_ID', 'amazon.titan-embed-text-v2:0'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
