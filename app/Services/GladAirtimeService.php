<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GladAirtimeService extends BaseApiService implements ApiServiceInterface
{
    public function __construct() 
    {
        $this->serviceName = 'glad';
        $this->headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Token ' . config('api.glad.api_key'),
        ];

    }

    public function supportsStatusCheck(): bool
{
    return false; // GladTidings doesn't support status checks
}

public function checkTransactionStatus(string $transactionId): array
{
    throw new \RuntimeException('Status check not supported by GladTidings');
}

public function processRequest(array $requestData): array
{
    $url = 'https://www.gladtidingsdata.com/api/topup/';
    
    $data = [
        'network' => $requestData['network'],
        'mobile_number' => $requestData['mobile_number'],
        'amount' => $requestData['amount'],
        "airtime_type" => "VTU",
        "Ported_number" => true
    ];

    try {
        // Make the API request
        $response = Http::withHeaders($this->headers)
                        ->withoutVerifying()
                        ->post($url, $data);

        // Log the response
        Log::info('Glad Service Response: ', [
            'status_code' => $response->status(),
            'body' => $response->body()
        ]);

        // Parse the response
        $responseData = $response->json();

        // Return the response
        return [
            'status_code' => $response->status(),
            'data' => $responseData
        ];
    } catch (\Exception $e) {
        // Log the error
        Log::error('Glad Service Error: ', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        // Return a consistent error response
        return [
            'status_code' => 500,
            'data' => ['error' => ['An error occurred while processing your request. Please try again later.']]
        ];
    }
}

public function handleResponse(array $response, array $context): array
    {
        $statusCode = $response['status_code'];
        $responseData = $response['data'];
        
        $result = [
            'status_code' => $statusCode,
            'success' => false,
            'transaction_id' => null,
            'network' => $context['network'],
            'message' => ''
        ];

        if ($statusCode >= 200 && $statusCode < 300) {
            if (isset($responseData['Status'])) {
                if ($responseData['Status'] === 'successful') {
                    $result['success'] = true;
                    $result['transaction_id'] = $responseData['ident'];
                    $result['network_name'] = $responseData['plan_network'];
                    $result['message'] = 'Top-up successful';
                } else {
                    $result['message'] = $responseData['error'] ?? 'Transaction failed';
                }
            } else {
                $result['message'] = 'Unexpected response, please try again!';
            }
        } else {
            $result['message'] = $this->getErrorMessage($responseData, $statusCode);
        }

        return $result;
    }

    private function getErrorMessage(array $responseData, int $statusCode): string
{
    if ($statusCode === 401 || $statusCode === 403) {
        Log::critical('GladTidings Authentication Failed');
        return 'Service authentication failed';
    }

    if (isset($responseData['error'])) {
        if (is_array($responseData['error'])) {
            $service_error = $responseData['error'][0] ?? 'Transaction failed';

            // Check for "insufficient balance" in the error message
            if (Str::contains($service_error, 'insufficient balance')) {
                return 'Something went wrong, please contact admin';
            }

            return $service_error;
        }

        return $responseData['error'];
    }

    return $responseData['message'] ?? 'An error occurred with Our service, please contact admin';
}

    public function getNetworkPrefixes(): array
    {
        return [
            1 => ['0803', '0703', '0903', '0806', '0706', '0813', '0810', '0814', '0816', '0906', '0913', '0801', '0707'], // MTN
            2 => ['0805', '0705', '0905', '0807', '0815', '0811', '0905', '0801'], // GLO
            3 => ['0802', '0902', '0701', '0808', '0708', '0812', '0904', '0901'], // Airtel
            6 => ['0809', '0909', '0817', '0818'] // 9Mobile
        ];
    }

}