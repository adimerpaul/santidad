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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'baneco' => [
        'base_url' => env('BANECO_BASE_URL', 'https://apimktdesa.baneco.com.bo/ApiGateway'),
        'aes_key' => env('BANECO_AES_KEY'),
        'username' => env('BANECO_USERNAME'),
        'password' => env('BANECO_PASSWORD'),
        'account_credit' => env('BANECO_ACCOUNT_CREDIT'),
        'branch_code' => env('BANECO_BRANCH_CODE'),
    ],

    'publicidad' => ['socket_url' => env('SOCKET_SERVER_URL')],
    'support_realtime' => [
        'url' => env('SOCKET_SERVER_URL'),
        'secret' => env('SUPPORT_SOCKET_SECRET'),
    ],

    'whatsapp_webhook' => [
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'test_phone_number_id' => env('WHATSAPP_TEST_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v25.0'),
        'bot_enabled' => env('WHATSAPP_BOT_ENABLED', false),
        'bot_queue' => env('WHATSAPP_BOT_QUEUE', 'sync'),
        'catalog_price_channel' => env('WHATSAPP_CATALOG_PRICE_CHANNEL'),
        'context_hours' => env('WHATSAPP_CONTEXT_HOURS', 48),
    ],

    'whatsapp_ai' => [
        'enabled' => env('AI_ENABLED', false),
        'provider' => env('AI_PROVIDER', 'gemini'),
        'model' => env('AI_MODEL'),
        'api_key' => env('AI_API_KEY'),
        'adult_only_channel' => env('AI_ADULT_ONLY_CHANNEL', false),
        'timeout' => env('AI_TIMEOUT', 4),
        'max_input_tokens' => env('AI_MAX_INPUT_TOKENS', 4096),
        'max_output_tokens' => env('AI_MAX_OUTPUT_TOKENS', 256),
        'per_user_per_minute' => env('AI_PER_USER_PER_MINUTE', 2),
        'concurrency' => env('AI_CONCURRENCY', 2),
        'monthly_budget_microusd' => env('AI_MONTHLY_BUDGET_MICROUSD', 0),
        'request_reserve_microusd' => env('AI_REQUEST_RESERVE_MICROUSD', 0),
    ],

];
