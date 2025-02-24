<?php

namespace App\Services;

use Google\Cloud\Firestore\FirestoreClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected $httpClient;
    protected $firebaseCredentials;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->firebaseCredentials = config('firebase.credentials');
    }

    public function sendNotification($token, $title, $body, 
    // $link = "https://5bb1-197-210-53-209.ngrok-free.app/notifications"
    )
    {
        $url = 'https://fcm.googleapis.com/v1/projects/geniepay-3b877/messages:send';
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                // 'webpush' => [
                //     'fcm_options'=> [ 
                //     //   "link" => $link
                //     ]
                // ]
                // 'data' => $data,
            ],
        ];

        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $message,
            ]);
           
            $responseBody = $response->getBody();

            // Log the response
            Log::info('FCM Response: ' . $responseBody);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Response From FCM: '. $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    protected function getAccessToken()
    {
        $client = new \Google_Client();
        $client->setAuthConfig($this->firebaseCredentials);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithAssertion();
        }

        return $client->getAccessToken()['access_token'];
    }
}
