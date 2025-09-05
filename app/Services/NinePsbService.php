<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NinePsbService
{
    protected string $baseUrl;
    protected string $publicKey;
    protected string $privateKey;

    public function __construct()
    {
        $this->baseUrl = 'https://baastest.9psb.com.ng/iva-api/v1/merchant/virtualaccount/';
        $this->publicKey = '8D2DE8945F514D95A7517A99DAC30F55';
        $this->privateKey = 'oQ5YcwuKFz1v_M18X2t4_rPlXCro9LWyo8Zn-XHhz7G9KCNCzDDnGT4wphbVoSGS';
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

            Log::info("Authentication response: " . $response);

            if ($response->successful() && $response['code'] === '00') {
                return $response['access_token'];
            }

            Log::error("Authentication response: " . $response);

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
        Log::info("9PSB {$endpoint} response: " . $response);

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

