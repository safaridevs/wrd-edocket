<?php

return [
    'repositories' => [
        'edocket' => [
            'enabled' => true,
            'storage_disk' => 'private'
        ],
        'onedrive' => [
            'enabled' => env('ONEDRIVE_SYNC_ENABLED', false),
            'client_id' => env('ONEDRIVE_CLIENT_ID'),
            'client_secret' => env('ONEDRIVE_CLIENT_SECRET')
        ],
        'sharepoint' => [
            'enabled' => env('SHAREPOINT_SYNC_ENABLED', false),
            'site_url' => env('SHAREPOINT_SITE_URL'),
            'client_id' => env('SHAREPOINT_CLIENT_ID'),
            'client_secret' => env('SHAREPOINT_CLIENT_SECRET')
        ],
        'revver' => [
            'enabled' => env('REVVER_SYNC_ENABLED', false),
            'api_url' => env('REVVER_API_URL'),
            'api_key' => env('REVVER_API_KEY')
        ],
        'website' => [
            'enabled' => env('WEBSITE_SYNC_ENABLED', false),
            'api_url' => env('WEBSITE_API_URL'),
            'api_key' => env('WEBSITE_API_KEY')
        ]
    ],
    
    'auto_stamp' => [
        'enabled' => true,
        'document_types' => ['filing', 'issuance']
    ],
    
    'case_routing' => [
        'auto_assign_hu' => true,
        'default_hu_role' => 'hu_officer'
    ]
];