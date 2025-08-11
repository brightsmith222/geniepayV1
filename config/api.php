<?php 

return [
    'vtpass' => [
        'base_url' => env('VTPASS_BASE_URL'),
        'api_key' => env('VTPASS_API_KEY'),       
        'public_key' => env('VTPASS_PUBLIC_KEY'),
        'private_key' => env('VTPASS_PRIVATE_KEY'),
        'username' => env('VTPASS_USERNAME'),
        'password' => env('VTPASS_PASSWORD'),
    ],
    'glad' => [
        'api_key' => env('GLAD_API_KEY'),
        'base_url' => env('GLAD_BASE_URL'),
    ],
    'artx' => [
    'base_url' => env('ARTX_BASE_URL'),
    'username' => env('ARTX_USERNAME'),
    'password' => env('ARTX_PASSWORD'),
],
'9psb' => [
    'public_key' => env('NINE_PSB_PUBLIC_KEY'),
    'private_key' => env('NINE_PSB_PRIVATE_KEY'),
    'base_url' => env('NINE_PSB_BASE_URL'),
    'webhook_username' => env('NINE_PSB_WEBHOOK_USERNAME'),
    'webhook_password' => env('NINE_PSB_WEBHOOK_PASSWORD'),
],
'reloadly' => [
    'client_id' => env('RELOADLY_CLIENT_ID'),
    'client_secret' => env('RELOADLY_CLIENT_SECRET'),
    'environment' => env('RELOADLY_ENVIRONMENT'), 
],
];

