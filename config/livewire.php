<?php

return [
    'asset_url' => null,
    'app_url' => env('APP_URL', 'http://localhost'),
    'manifest_path' => public_path('build/livewire-manifest.json'),
    'temporary_file_upload' => [
    'disk' => 'local', // Change to 'public' if using a public storage
    'rules' => ['image', 'max:2048'], // Adjust max size if needed
    'preview_mimes' => ['image/jpeg', 'image/png'],
    'directory' => 'livewire-tmp', // Temporary folder for uploads
    'middleware' => null, // Default middleware
],
    'inject_assets' => true,
    'turbo' => false,
];
