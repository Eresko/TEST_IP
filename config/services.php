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
    'search' => [
      'driver'  => env('SEARCH_DRIVER', 'eloquent'),
    ],
    'export_order_url' => env('EXPORT_ORDER_URL', 'https://httpbin.org/'),
    'notifications' => [
        'max_tries' => env('NOTIFICATION_MAX_TRIES', 3),
        'backoff' => explode(',', env('NOTIFICATION_BACKOFF_STRATEGY', '10,30,60')),
    ],
    'reports' => [
        'max_tries' => env('REPORTS_MAX_TRIES', 3),
    ],
    'smsc' => [
        'login' => env('SMSC_LOGIN'),
        'api_key' => env('SMSC_API_KEY'),
        'url' => env('SMSC_API_URL'),
    ],

];
