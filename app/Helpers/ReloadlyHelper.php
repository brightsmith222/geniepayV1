<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ReloadlyHelper
{
    public static function getAccessToken()
    {
        $response = Http::asForm()->post('https://auth.reloadly.com/oauth/token', [
    'client_id' =>  config('api.reloadly.client_id'), 
    'client_secret' =>  config('api.reloadly.client_secret'), 
    'grant_type' => 'client_credentials',
    'audience' => 'https://giftcards.reloadly.com',
]);

            if (!$response->ok()) {
                throw new \Exception('Unable to authenticate with Reloadly');
            }

            return $response->json()['access_token'];
        }
    }

