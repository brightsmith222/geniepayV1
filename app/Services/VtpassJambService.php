<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\MyFunctions;
use Illuminate\Support\Facades\Log;

class VtpassJambService
{
    protected $baseUrl;
    protected $headers;

    public function __construct()
    {
        $this->baseUrl = config('api.vtpass.base_url');
        $this->headers = [
            'api-key' => config('api.vtpass.api_key'),
            'secret-key' => config('api.vtpass.private_key'),
            'public-key' => config('api.vtpass.public_key'),
            'Content-Type' => 'application/json',
        ];
    }

    public function authHeaders()
    {
        return $this->headers;
    }

    public function getJambVariations( string $serviceID)
    {
        

        $response = Http::withoutverifying()->withHeaders($this->authHeaders())->get(
            $this->baseUrl . 'service-variations?serviceID=' . $serviceID,
        );


        return $response->json();
    }

    public function verifyJambProfile(string $profileId, string $variationCode)
    {
        $response = Http::withoutverifying()->withHeaders($this->authHeaders())->post(
            $this->baseUrl . 'merchant-verify',
            [
                'serviceID' => 'jamb',
                'billersCode' => $profileId,
                'variation_code' => $variationCode,
            ]
        );

        return $response->json();
    }

    public function purchaseJambPin(string $profileId, string $variationCode, int $amount, string $phone)
    {
        $response = Http::withoutverifying()->withHeaders($this->authHeaders())->post(
            $this->baseUrl . 'pay',
            [
                'request_id' => MyFunctions::generateRequestId(),
                'serviceID' => 'jamb',
                'billersCode' => $profileId,
                'variation_code' => $variationCode,
                'amount' => $amount,
                'phone' => $phone,
            ]
        );

        return $response->json();
    }

     public function purchaseWaecPin(string $serviceID, string $variationCode, int $amount, string $phone, string $quantity)
{
    $payload = [
        'request_id' => MyFunctions::generateRequestId(),
        'serviceID' => $serviceID,
        'variation_code' => $variationCode,
        'amount' => $amount,
        'quantity' => $quantity,
        'phone' => $phone,
    ];

    Log::info('VTPass WAEC payload:', $payload); // Log the payload

    $response = Http::withoutverifying()->withHeaders($this->authHeaders())->post(
        $this->baseUrl . 'pay',
        $payload
    ); 

    Log::info('VTPass WAEC response:', $response->json());

    return $response->json();
}

    public function requeryTransaction(string $requestId)
{
    $response = Http::withoutVerifying()
        ->withHeaders($this->authHeaders())
        ->post($this->baseUrl . 'requery', [
            'request_id' => $requestId,
        ]);

    return $response->json();
}

public function getWaecVariations( string $serviceID)
    {
        

        $response = Http::withoutverifying()->withHeaders($this->authHeaders())->get(
            $this->baseUrl . 'service-variations?serviceID=' . $serviceID,
        );


        return $response->json();
    }

}

