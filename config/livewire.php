<?php

return [
    'asset_url' => null,
    'app_url' => env('APP_URL', 'http://localhost'),
    'manifest_path' => public_path('build/livewire-manifest.json'),
    'temporary_file_upload' => [
        'disk' => 'public',
        'rules' => null,
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    ],
    'inject_assets' => true,
    'turbo' => false,
];
