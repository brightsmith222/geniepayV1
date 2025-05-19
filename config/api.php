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
];

