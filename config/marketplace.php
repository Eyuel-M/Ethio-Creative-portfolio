<?php

// Proposed defaults only — prices, quotas and retention periods require owner approval (spec §20).
return [
    'brand' => env('APP_NAME', 'Ethio Creative'),

    'portfolio_limits' => [
        'free' => (int) env('PORTFOLIO_LIMIT_FREE', 3),
        'professional' => (int) env('PORTFOLIO_LIMIT_PRO', 30),
    ],

    'media_disk' => env('MEDIA_DISK', 'public'),

    'max_upload_kb' => [
        'image' => 20 * 1024,
        'project_file' => 50 * 1024,
    ],

    'project_files' => [
        'disk' => env('PROJECT_FILES_DISK', 'local'),
        'confirmed_retention_days' => 30,
        'unconfirmed_retention_days' => 30,
        'allowed_mimes' => 'pdf,zip,png,jpg,jpeg,webp,gif,svg,psd,ai,fig,mp4,mov,docx,xlsx,pptx,txt',
    ],

    'payments' => [
        'gateway' => env('PAYMENT_GATEWAY', 'sandbox'),
        'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', 'change-me-local-sandbox-secret'),
    ],

    'currencies' => ['ETB', 'USD'],
];
