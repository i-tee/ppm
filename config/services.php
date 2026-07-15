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
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'redirect_dev' => env('GOOGLE_REDIRECT_URI_DEV'),
    ],

    'yandex' => [
        'client_id' => env('YANDEX_CLIENT_ID'),
        'client_secret' => env('YANDEX_CLIENT_SECRET'),
        'redirect' => env('YANDEX_REDIRECT_URI'),
        'redirect_dev' => env('YANDEX_REDIRECT_URI_DEV'),
    ],

    // Основной бэкенд Avicenna — dual-write партнёрских купонов (Фаза D, этап 3).
    // Флаги-рычаги перехода/отката (§3.2 плана миграции партнёрки):
    //   mint_via_backend      — минтить через API бэка (иначе только Joomla-INSERT);
    //   joomla_dual_write     — дублировать успешный минт в Joomla;
    //   accruals_from_backend — добавлять к балансу net из GET /partner/accruals.
    'avicenna_backend' => [
        'base_url'              => env('AVICENNA_BACKEND_BASE_URL', 'http://host.docker.internal:8080'),
        'source_token'          => env('AVICENNA_BACKEND_SOURCE_TOKEN'),
        'timeout'               => (int) env('AVICENNA_BACKEND_TIMEOUT', 10),
        'mint_via_backend'      => (bool) env('PARTNER_MINT_VIA_BACKEND', false),
        'joomla_dual_write'     => (bool) env('PARTNER_JOOMLA_DUAL_WRITE', false),
        'accruals_from_backend' => (bool) env('PARTNER_ACCRUALS_FROM_BACKEND', false),
    ],

];
