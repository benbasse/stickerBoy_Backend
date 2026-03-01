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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'bictorys' => [
        'api_key' => env('BICTORYS_API_KEY'),
        'base_url' => env('BICTORYS_BASE_URL'),
    ],
    'senepay' => [
        'base_url'      => env('SENEPAY_BASE_URL'),
        'public_key'    => env('SENEPAY_PUBLIC_KEY'),
        'secret_key'    => env('SENEPAY_SECRET_KEY'),
        'webhook_secret' => env('SENEPAY_WEBHOOK_SECRET'),
    ],
    'naboopay' => [
        'url' => env('NABOOPAY_URL', 'https://api.dev.naboopay.com/api/v2'),
        'token' => env('NABOOPAY_TOKEN'),
        'webhook_secret' => env('NABOOPAY_WEBHOOK_SECRET'),
        'recipient_phone' => env('NABOOPAY_RECIPIENT_PHONE', '+221786536567'),
        'recipient_first_name' => env('NABOOPAY_RECIPIENT_FIRST_NAME', 'StickerBoy'),
        'recipient_last_name' => env('NABOOPAY_RECIPIENT_LAST_NAME', 'Store'),
        'success_url' => env('NABOOPAY_SUCCESS_URL'),
        'error_url' => env('NABOOPAY_ERROR_URL'),
    ],

];
