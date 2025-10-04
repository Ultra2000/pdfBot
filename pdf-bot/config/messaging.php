<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Messaging Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default messaging provider that will be used
    | to send WhatsApp messages. You can change this to switch between
    | different providers like Twilio or Meta.
    |
    | Supported: "twilio", "meta"
    |
    */
    'default' => env('MESSAGING_PROVIDER', 'twilio'),

    /*
    |--------------------------------------------------------------------------
    | Messaging Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the messaging providers used by your application.
    | Each provider has its own configuration options.
    |
    */
    'providers' => [
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'whatsapp_number' => env('TWILIO_WHATSAPP_NUMBER'),
        ],

        'meta' => [
            'access_token' => env('WHATSAPP_TOKEN'),
            'phone_number_id' => env('WHATSAPP_PHONE_ID'),
            'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
            'app_secret' => env('WHATSAPP_APP_SECRET'),
            'api_version' => env('WHATSAPP_API_VERSION', 'v18.0'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Settings
    |--------------------------------------------------------------------------
    |
    | Configure global settings for messaging
    |
    */
    'settings' => [
        'max_file_size' => env('WHATSAPP_MAX_FILE_SIZE', 16 * 1024 * 1024), // 16MB
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'signed_url_expires' => env('SIGNED_URL_EXPIRES', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for webhook endpoints
    |
    */
    'rate_limits' => [
        'webhook' => [
            'max_attempts' => env('WEBHOOK_RATE_LIMIT', 100),
            'decay_minutes' => env('WEBHOOK_RATE_DECAY', 1),
        ],
    ],
];
