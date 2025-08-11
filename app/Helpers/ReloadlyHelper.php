<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use libphonenumber\PhoneNumberUtil;

class ReloadlyHelper
{
    public static function isSandbox()
    {
        return config('api.reloadly.environment') === 'sandbox';
    }

    public static function getAccessToken()
    {
        $audience = self::isSandbox()
            ? 'https://giftcards-sandbox.reloadly.com'
            : 'https://giftcards.reloadly.com';

        Log::info('ReloadlyHelper: Getting access token for audience: ' . $audience);

        $response = Http::asForm()->withoutVerifying()->post('https://auth.reloadly.com/oauth/token', [
            'client_id' => config('api.reloadly.client_id'),
            'client_secret' => config('api.reloadly.client_secret'),
            'grant_type' => 'client_credentials',
            'audience' => $audience,
        ]);


        if (!$response->ok()) {
            throw new \Exception('Reloadly authentication failed: ' . $response->body());
        }

        return $response->json()['access_token'];
    }

    public static function getAccessTokenForAirtimes()
    {
        $audience = self::isSandbox()
            ? 'https://topups-sandbox.reloadly.com'
            : 'https://topups.reloadly.com';

        Log::info('ReloadlyHelper: Getting access token for audience: ' . $audience);

        $response = Http::asForm()->withoutVerifying()->post('https://auth.reloadly.com/oauth/token', [
            'client_id' => config('api.reloadly.client_id'),
            'client_secret' => config('api.reloadly.client_secret'),
            'grant_type' => 'client_credentials',
            'audience' => $audience,
        ]);


        if (!$response->ok()) {
            throw new \Exception('Reloadly authentication failed: ' . $response->body());
        }

        return $response->json()['access_token'];
    }

    public static function getAccessTokenForAirtime()
    {
        // Check cache first
        if (Cache::has('reloadly_airtime_token')) {
            return Cache::get('reloadly_airtime_token');
        }

        // Get credentials from .env or config
        $client_id = config('api.reloadly.client_id');
        $client_secret = config('api.reloadly.client_secret');
        $audience = "https://topups-sandbox.reloadly.com";

        // Request access token
        $response = Http::asForm()->withoutVerifying()->post('https://auth.reloadly.com/oauth/token', [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'client_credentials',
            'audience' => $audience
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['access_token'];
            $expires_in = $data['expires_in']; // usually 3600 (1 hour)

            // Cache the token for future use
            Cache::put('reloadly_airtime_token', $token, now()->addSeconds($expires_in - 60)); // cache for slightly less

            return $token;
        }

        throw new \Exception('Failed to get Reloadly Airtime access token: ' . $response->body());
    }

    public static function baseUrl()
    {
        return self::isSandbox()
            ? 'https://giftcards-sandbox.reloadly.com'
            : 'https://giftcards.reloadly.com';
    }

    public static function getCardDetails($orderId)
    {
        try {
            // Step 1: Get access token for gift cards
            $token = ReloadlyHelper::getAccessToken();
            Log::info('getGiftCardRedeemCode: Token acquired');

            // Step 2: Build URL and make request
            $url = "https://giftcards-sandbox.reloadly.com/orders/transactions/{$orderId}/cards";
            Log::info('getGiftCardRedeemCode: Calling URL', ['url' => $url]);

            $response = Http::withToken($token)
                ->withoutVerifying()
                ->get($url);
            Log::info('getGiftCardRedeemCode: Response received', ['status' => $response]);

            if (!$response->ok()) {
                Log::error('getGiftCardRedeemCode: Failed to retrieve redeem code', [
                    'orderId' => $orderId,
                    'response' => $response->body()
                ]);
                return [
                    'success' => false,
                    'data' => null
                ];
            }

            $redeemData = $response->json();
            Log::info('getGiftCardRedeemCode: Redeem code retrieved', ['data' => $redeemData]);

            // Return as array, not JsonResponse
            return [
                'success' => true,
                'data' =>  $redeemData
            ];
        } catch (\Exception $e) {
            Log::error('getGiftCardRedeemCode: Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'data' => null,
                'message' => 'Something went wrong.'
            ];
        }
    }

    public static function getGiftCardRedeemInstructions($productId)
    {
        try {
            $token = ReloadlyHelper::getAccessToken();
            Log::info('getGiftCardRedeemInstructions: Access token retrieved');

            $url = "https://giftcards-sandbox.reloadly.com/products/{$productId}/redeem-instructions";
            Log::info('getGiftCardRedeemInstructions: Fetching redeem instructions', ['url' => $url]);

            $response = Http::withToken($token)
                ->withoutVerifying()
                ->get($url);

            if (!$response->ok()) {
                Log::error('getGiftCardRedeemInstructions: Failed to fetch instructions', [
                    'productId' => $productId,
                    'response' => $response->body()
                ]);
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Could not fetch redeem instructions.'
                ];
            }

            $data = $response->json();
            Log::info('getGiftCardRedeemInstructions: Instructions fetched successfully', ['data' => $data]);

            return [
                'success' => true,
                'data' =>  $data
            ];
        } catch (\Exception $e) {
    Log::error('getGiftCardRedeemInstructions: Exception', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    return [
        'success' => false,
        'data' => null,
        'message' => 'Something went wrong.'
    ];
}
    }




    public static function getCountryISO($phone)
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneProto = $phoneUtil->parse($phone, null);
            $countryCode = $phoneUtil->getRegionCodeForNumber($phoneProto);
            return $countryCode; // e.g. "NG"
        } catch (\Exception $e) {
            return null;
        }
    }
}
