<?php

// Proposed defaults only — prices, quotas and retention periods require owner approval (spec §20).
return [
    'brand' => env('APP_NAME', 'DezignersHub'),

    // The public address people see on shared links and post cards (e.g. https://dezignershub.com).
    // Falls back to APP_URL. Set this when the app itself runs on another host.
    'public_url' => rtrim((string) env('PUBLIC_URL', env('APP_URL', 'http://localhost')), '/'),

    'portfolio_limits' => [
        'free' => (int) env('PORTFOLIO_LIMIT_FREE', 3),
        'professional' => (int) env('PORTFOLIO_LIMIT_PRO', 30),
    ],

    'media_disk' => env('MEDIA_DISK', 'public'),

    // Untouched copies of every compressed upload, kept privately so images can always be re-processed.
    'keep_originals' => env('KEEP_IMAGE_ORIGINALS', true),
    'originals_disk' => env('ORIGINALS_DISK', 'local'),

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
