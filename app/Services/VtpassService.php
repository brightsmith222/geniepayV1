<?php

namespace App\Services;
use App\Models\GeneralSettings;


class VtpassService {
    protected $headers;

    public function __construct() {
        
        $this->headers = [
            'api-key' => config('api.vtpass.api_key'),
            'secret-key' => config('api.vtpass.private_key'),
            'public-key' => config('api.vtpass.public_key'),
            'Content-Type' => 'application/json',
        ];
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function isVtpassEnabled(): bool {
        return (bool) GeneralSettings::where('name', 'vtpass')->value('is_enabled');
    }
}

