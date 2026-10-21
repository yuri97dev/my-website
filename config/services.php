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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434/api/'),
        'chat_model' => env('OLLAMA_CHAT_MODEL', 'qwen3.5:2b'),
        'timeout' => env('OLLAMA_TIMEOUT'),
        'pull_timeout' => env('OLLAMA_PULL_TIMEOUT', 3600),
        'keep_alive' => env('OLLAMA_KEEP_ALIVE', '5m'),
    ],

    'laya' => [
        'url' => env('LAYA_URL'),
        'key' => env("LAYA_API_KEY"),
    ],

];
