<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'cloudconvert' => [
        'api_key' => env('CLOUDCONVERT_API_KEY'),
        'sandbox' => (bool) env('CLOUDCONVERT_SANDBOX', false),
        'webhook_secret' => env('CLOUDCONVERT_WEBHOOK_SECRET'),
        'retention_hours' => (int) env('CONVERSION_RETENTION_HOURS', 24),
        'operations' => [
            'pdf_to_docx' => [
                'label' => 'PDF to DOCX',
                'input_format' => 'pdf',
                'output_format' => 'docx',
                'allowed_mime_types' => [
                    'application/pdf',
                    'application/x-pdf',
                ],
                'allowed_extensions' => ['pdf'],
                'max_file_size_kb' => (int) env('CLOUDCONVERT_MAX_FILE_SIZE_KB', 10240), // 10 MB in KB
            ],
        ],
    ],

];
