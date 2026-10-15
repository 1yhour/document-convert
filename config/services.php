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
            'docx_to_pdf' => [
                'label' => 'DOCX to PDF',
                'input_format' => 'docx',
                'output_format' => 'pdf',
                'allowed_mime_types' => [
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
                'max_file_size_kb' => (int) env('CLOUDCONVERT_MAX_FILE_SIZE_KB', 10240), // 10 MB in KB
            ],
            'pdf_to_jpg' => [
                'label' => 'PDF to JPG',
                'input_format' => 'pdf',
                'output_format' => 'jpg',
                'allowed_extensions' => ['pdf'],
                'allowed_mime_types' => ['application/pdf'],
                'max_file_size_kb' => 10240,
            ],

            'jpg_to_pdf' => [
                'label' => 'JPG to PDF',
                'input_format' => 'jpg',
                'output_format' => 'pdf',
                'allowed_extensions' => ['jpg', 'jpeg'],
                'allowed_mime_types' => ['image/jpeg'],
                'max_file_size_kb' => 10240,
            ],

            'png_to_pdf' => [
                'label' => 'PNG to PDF',
                'input_format' => 'png',
                'output_format' => 'pdf',
                'allowed_extensions' => ['png'],
                'allowed_mime_types' => ['image/png'],
                'max_file_size_kb' => 10240,
            ],

            'docx_to_txt' => [
                'label' => 'Word to Text',
                'input_format' => 'docx',
                'output_format' => 'txt',
                'allowed_extensions' => ['docx'],
                'allowed_mime_types' => [
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
                'max_file_size_kb' => 10240,
            ],

            'xlsx_to_pdf' => [
                'label' => 'Excel to PDF',
                'input_format' => 'xlsx',
                'output_format' => 'pdf',
                'allowed_extensions' => ['xlsx'],
                'allowed_mime_types' => [
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
                'max_file_size_kb' => 10240,
            ],

            'pptx_to_pdf' => [
                'label' => 'PowerPoint to PDF',
                'input_format' => 'pptx',
                'output_format' => 'pdf',
                'allowed_extensions' => ['pptx'],
                'allowed_mime_types' => [
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ],
                'max_file_size_kb' => 10240,
            ],
        ],
    ],

];
