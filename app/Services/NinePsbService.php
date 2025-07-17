<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class NinePsbService
{
    protected string $baseUrl;
    protected string $publicKey;
    protected string $privateKey;

    public function __construct()
    {
        $this->baseUrl = config('api.9psb.base_url');
        $this->publicKey = config('api.9psb.public_key');
        $this->privateKey = config('api.9psb.private_key');
    }

    /**
     * Get Bearer Token with cache
     */
    public function authenticate(): string
    {
        return Cache::remember('9psb_token', 6900, function () {
            $response = Http::withoutVerifying()->post($this->baseUrl . 'authenticate', [
                'publickey' => $this->publicKey,
                'privatekey' => $this->privateKey,
            ]);

            if ($response->successful() && $response['code'] === '00') {
                return $response['access_token'];
            }

            throw new \Exception("Authentication failed: " . $response['message']);
        });
    }

    /**
     * General-purpose API request wrapper
     */
    protected function request(string $endpoint, array $payload, string $method = 'post')
    {
        $token = $this->authenticate();

        $response = Http::withoutVerifying()->withToken($token)->{$method}($this->baseUrl . $endpoint, $payload);

        if ($response->successful() && isset($response['code']) && $response['code'] === '00') {
            return $response->json();
        }

        throw new \Exception("9PSB Error: " . ($response['message'] ?? 'Unknown error'));
    }

    // PUBLIC METHODS

    public function createVirtualAccount(array $payload)
    {
        return $this->request('create', $payload);
    }

    public function deactivateVirtualAccount(array $payload)
    {
        return $this->request('deactivate', $payload);
    }

    public function reactivateVirtualAccount(array $payload)
    {
        return $this->request('reactivate', $payload);
    }

    public function reallocateVirtualAccount(array $payload)
    {
        return $this->request('reallocate', $payload);
    }

    public function confirmPayment(array $payload)
    {
        return $this->request('confirmpayment', $payload);
    }

    public function refundPayment(array $payload)
    {
        return $this->request('paymentrefund', $payload);
    }
}

