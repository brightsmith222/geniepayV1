<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GladDataService extends BaseApiService implements ApiServiceInterface
{
    protected $baseUrl;
    protected $username;
    protected $passwordHash;

    public function __construct() 
    {
        $this->serviceName = 'glad_data';
        $this->headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Token ' . config('api.glad.api_key'),
        ];
    }

    public function getServiceName(): string
{
    return $this->serviceName;
}

    public function supportsStatusCheck(): bool
    {
        return false;
    }

    public function checkTransactionStatus(string $transactionId): array
    {
        throw new \RuntimeException('Status check not supported by GladTidings');
    }

    public function processRequest(array $requestData): array
    {
        $url = 'https://www.gladtidingsdata.com/api/data/';
        
        $data = [
            'network' => $requestData['network'],
            'mobile_number' => $requestData['mobile_number'],
            'plan' => $requestData['plan'],
            'Ported_number' => true
        ];

        try {
            $response = Http::withHeaders($this->headers)
                            ->withoutVerifying()
                            ->post($url, $data);

            return [
                'status_code' => $response->status(),
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Glad Data Error', ['error' => $e->getMessage()]);
            return [
                'status_code' => 500,
                'data' => ['error' => ['An error occurred while processing your request']]
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
            'pending' => false,
            'transaction_id' => null,
            'network' => $context['network'],
            'message' => '',
            'plan_id' => null,
            'api_reference' => null
        ];

        if ($statusCode >= 200 && $statusCode < 300) {
            if (isset($responseData['Status']) && $responseData['Status'] === 'successful') {
                $result['success'] = true;
                $result['serviceName'] = $this->serviceName;
                $result['transaction_id'] = $responseData['ident'];
                $result['network_name'] = $responseData['plan_network'];
                $result['plan_id'] = $responseData['plan'];
                $result['api_reference'] = $responseData['ident'] ?? null; 
                $result['message'] = 'Data purchase successful';
            } elseif (isset($responseData['Status']) && $responseData['Status'] === 'pending') {
                $result['pending'] = true; // Set "pending" to true for pending transactions
                $result['message'] = 'Transaction is pending';
            } else {
                $result['message'] = $responseData['error'] ?? 'Transaction failed';
            }
        } else {
            $result['message'] = $this->getErrorMessage($responseData, $statusCode);
        }

        return $result;
    }

    public function getDataPlans(int $network): array
    {
        try {
            $url = 'https://www.gladtidingsdata.com/api/user/';
            $response = Http::withHeaders($this->headers)
                            ->withoutVerifying()
                            ->post($url);

            $responseData = $response->json();
            Log::info('Glad Data Plans Response', ['response' => $responseData]);

            // Check if Dataplans key exists
            if (!isset($responseData['Dataplans'])) {
                Log::error('Dataplans key missing in response');
                return [];
            }

            $dataplans = $responseData['Dataplans'];

            // Dynamically fetch plans based on the network
            $networkKey = match ($network) {
                1 => 'MTN_PLAN',
                2 => 'GLO_PLAN',
                3 => 'AIRTEL_PLAN',
                6 => '9MOBILE_PLAN',
                default => null
            };

            if (!$networkKey || !isset($dataplans[$networkKey])) {
                Log::error("Network key {$networkKey} missing in Dataplans");
                return [];
            }

            // Fetch plans (use ALL or fallback to the first available key)
            $networkPlans = $dataplans[$networkKey]['ALL'] ?? reset($dataplans[$networkKey]) ?? [];

            // Map network ID to network name
            $networkName = match ($network) {
                1 => 'MTN',
                2 => 'GLO',
                3 => 'AIRTEL',
                6 => '9MOBILE',
                default => null
            };

            $formattedPlans = [];
            foreach ($networkPlans as $plan) {
                $formattedPlans[] = [
                    'plan_id' => $plan['dataplan_id'] ?? null,
                    'plan' => $plan['plan'] ?? null,
                    'network' => $networkName, // Use the mapped network name
                    'amount' => $plan['plan_amount'] ?? null,
                    'validity' => $plan['month_validate'] ?? null,
                    'data_volume' => $plan['plan'] ?? null
                ];
            }

            return $formattedPlans;

        } catch (\Exception $e) {
            Log::error('Glad Data Plans Error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getErrorMessage(array $responseData, int $statusCode): string
    {
        if ($statusCode == 401 || $statusCode == 403) {
            return 'Service authentication failed';
        }

        if (isset($responseData['error'])) {
            if (is_array($responseData['error'])) {
                $service_error = $responseData['error'][0] ?? 'Transaction failed';
                if (str_contains($service_error, 'insufficient balance')) {
                    return 'Something went wrong, please contact admin';
                }
                return $service_error;
            }
            return $responseData['error'];
        }

        return $responseData['message'] ?? 'An error occurred with Our service';
    }

    public function getNetworkPrefixes(): array
    {
        return [
            1 => ['0803', '0703', '0903', '0806', '0706', '0813', '0810', '0814', '0816', '0906', '0913', '0801', '0707'],
            2 => ['0805', '0705', '0905', '0807', '0815', '0811', '0905', '0801'],
            3 => ['0802', '0902', '0701', '0808', '0708', '0812', '0904', '0901'],
            6 => ['0809', '0909', '0817', '0818']
        ];
    }

    protected function getPrefixLength(): int
    {
        return 4;
    }
}