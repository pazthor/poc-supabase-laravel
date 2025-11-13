<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supabase Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for your Supabase project.
    | You can find these values in your Supabase project settings.
    |
    */

    'url' => env('SUPABASE_URL', ''),
    'key' => env('SUPABASE_KEY', ''),
    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY', ''),

    // Storage configuration
    'storage' => [
        'url' => env('SUPABASE_URL', '') . '/storage/v1',
    ],

    // Auth configuration
    'auth' => [
        'url' => env('SUPABASE_URL', '') . '/auth/v1',
    ],

    // Database (PostgREST) configuration
    'database' => [
        'url' => env('SUPABASE_URL', '') . '/rest/v1',
    ],
];
