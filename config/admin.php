<?php

/*
| Admin access. super_admin can do everything; every other role sees only the sections listed for it.
| Menus, routes and actions all read this map, so changing it here changes the whole console.
*/
return [
    'roles' => [
        'super_admin' => 'Super admin',
        'moderator' => 'Moderator',
        'support' => 'Support',
        'finance' => 'Finance',
        'dispute_reviewer' => 'Dispute reviewer',
        'analyst' => 'Analyst',
    ],

    'role_descriptions' => [
        'super_admin' => 'Everything, including the admin team, settings and system health.',
        'moderator' => 'Reports, job & portfolio moderation, verification, content.',
        'support' => 'Users, reports, verification, data requests, view-as-user.',
        'finance' => 'Orders, subscriptions, plans & fees, refunds, revenue insights.',
        'dispute_reviewer' => 'Reports, agreements, payment-detail log, audit log.',
        'analyst' => 'Insights and data exports (read-only).',
    ],

    'sections' => [
        'dashboard' => ['*'],
        'search' => ['*'],
        'users' => ['moderator', 'support', 'dispute_reviewer'],
        'users_manage' => ['moderator', 'support'],
        'impersonate' => ['support'],
        'team' => [],
        'reports' => ['moderator', 'support', 'dispute_reviewer'],
        'moderation' => ['moderator'],
        'verification' => ['moderator', 'support'],
        'marketplace' => ['moderator', 'support', 'dispute_reviewer'],
        'agreements' => ['dispute_reviewer', 'support'],
        'disclosures' => ['dispute_reviewer'],
        'reviews' => ['moderator'],
        'finance' => ['finance'],
        'content' => ['moderator'],
        'settings' => [],
        'data_requests' => ['support'],
        'health' => [],
        'audit' => ['dispute_reviewer'],
        'insights' => ['analyst', 'finance', 'moderator'],
        'exports' => ['analyst'],
    ],

    // Admins re-confirm with an emailed code before using the console (on by default in production).
    'step_up' => env('ADMIN_STEP_UP', env('APP_ENV') === 'production'),
    'step_up_hours' => 12,
];
