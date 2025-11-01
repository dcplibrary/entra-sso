<?php

return [
    'tenant_id' => env('ENTRA_TENANT_ID'),
    'client_id' => env('ENTRA_CLIENT_ID'),
    'client_secret' => env('ENTRA_CLIENT_SECRET'),
    'redirect_uri' => env('ENTRA_REDIRECT_URI', env('APP_URL') . '/auth/entra/callback'),
    
    'user_model' => env('ENTRA_USER_MODEL', App\Models\User::class),
    'auto_create_users' => env('ENTRA_AUTO_CREATE_USERS', true),
    
    'sync_groups' => env('ENTRA_SYNC_GROUPS', false),
    'sync_on_login' => env('ENTRA_SYNC_ON_LOGIN', true),

    // Group to role mapping
    // Simple mapping via .env (recommended for most cases):
    // ENTRA_GROUP_ROLES="IT Admins:admin,Developers:developer,Staff:user"
    //
    // Advanced mapping via config (publish config file if needed):
    // 'group_role_mapping' => [
    //     'Computer Services' => 'admin',
    //     'Developers' => 'developer',
    // ],
    'group_role_mapping' => env('ENTRA_GROUP_ROLES')
        ? collect(explode(',', env('ENTRA_GROUP_ROLES')))
            ->mapWithKeys(function ($mapping) {
                $parts = explode(':', trim($mapping), 2);
                return count($parts) === 2 ? [trim($parts[0]) => trim($parts[1])] : [];
            })
            ->filter()
            ->toArray()
        : [
            // Default mappings (used if ENTRA_GROUP_ROLES is not set and config not published)
        ],
    
    'default_role' => env('ENTRA_DEFAULT_ROLE', 'user'),
    'role_model' => env('ENTRA_ROLE_MODEL', null),
    
    'enable_token_refresh' => env('ENTRA_ENABLE_TOKEN_REFRESH', true),
'refresh_threshold_minutes' => (int) env('ENTRA_REFRESH_THRESHOLD', 5),
    
    'custom_claims_mapping' => [
        // 'jobTitle' => 'job_title',
        // 'department' => 'department',
    ],
    
    'store_custom_claims' => env('ENTRA_STORE_CUSTOM_CLAIMS', false),
];
