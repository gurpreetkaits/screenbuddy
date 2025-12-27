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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/api/auth/google/callback'),
    ],

    'frontend' => [
        'url' => env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'polar' => [
        'api_key' => env('POLAR_ACCESS_TOKEN', env('POLAR_API_KEY')), // For backward compatibility
        'organization_id' => env('POLAR_ORGANIZATION_ID'),
        'product_id_monthly' => env('POLAR_PRODUCT_ID_MONTHLY'),
        'product_id_yearly' => env('POLAR_PRODUCT_ID_YEARLY'),
        'webhook_secret' => env('POLAR_WEBHOOK_SECRET'),
        'environment' => env('POLAR_SERVER', env('POLAR_ENVIRONMENT', 'sandbox')),
        'api_url' => env('POLAR_SERVER', env('POLAR_ENVIRONMENT', 'sandbox')) === 'production'
            ? 'https://api.polar.sh'
            : 'https://sandbox-api.polar.sh',
    ],

];
