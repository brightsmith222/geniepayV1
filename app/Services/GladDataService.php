<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\MyFunctions;

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
        return true;
    }

    public function checkTransactionStatus(string $transactionId): array
    {
        throw new \RuntimeException('Status check not supported by GladTidings');
    }

    public function processRequest(array $requestData): array
    {
        $url = config('api.glad.base_url') . 'data/';

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
            'transaction_id' => MyFunctions::generateRequestId(),
            'network_name' => $context['network'],
            'message' => '',
            'plan_id' => null,
            'api_reference' => MyFunctions::generateRequestId(),
            'which_api' => 'glad',
        ];

        if ($statusCode >= 200 && $statusCode < 300) {
            if (isset($responseData['Status']) && $responseData['Status'] === 'successful') {
                $result['success'] = true;
                $result['which_api'] = 'glad';
                $result['serviceName'] = $this->serviceName;
                $result['transaction_id'] = $responseData['ident'];
                $result['operator_name'] = $responseData['plan_network'];
                $result['plan_id'] = $responseData['plan'];
                $result['api_reference'] = $responseData['ident'] ?? null;
                $result['message'] = 'Data purchase successful';
            } elseif (isset($responseData['Status']) && $responseData['Status'] === 'pending') {
                $result['pending'] = true;
                $result['which_api'] = 'glad';
                $result['serviceName'] = $this->serviceName;
                $result['transaction_id'] = $responseData['ident'];
                $result['operator_name'] = $responseData['plan_network'];
                $result['plan_id'] = $responseData['plan'];
                $result['api_reference'] = $responseData['ident'] ?? null;
                $result['message'] = 'Data purchase processing';
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
            $url = config('api.glad.base_url') . 'user/';
            $response = Http::withHeaders($this->headers)
                ->withoutVerifying()
                ->post($url);

            $responseData = $response->json();

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
                    'plan_id'    => $plan['dataplan_id'] ?? null,
                    'plan'       => $plan['month_validate'] ?? null,
                    'plan_type'       => $plan['plan_type'] ?? null,
                    'network'    => $networkName,
                    'amount'     => $plan['plan_amount'] ?? null,
                    'validity'   => $this->extractValidity($plan['month_validate'] ?? ''), // <-- Use the helper here
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

    protected function extractValidity(string $monthValidate): string
{
    $val = strtolower($monthValidate);

    // Special cases for night plans
    if (str_contains($val, 'night plan') || str_contains($val, '1 night plan') || str_contains($val, 'nite')) {
        return '1 day';
    }

    // Special case for weekly YouTube or similar
    if (str_contains($val, 'weekly') || str_contains($val, '7 days') || str_contains($val, '7days')) {
        return '7 days';
    }

    // Special case for "YouTube_Night_Weekly_1am-5am"
    if (str_contains($val, 'youtube_night_weekly')) {
        return '7 days';
    }

    // Special case for "Night Plan 50 - Data - 250.0 M"
    if (str_contains($val, 'night plan 50')) {
        return '1 day';
    }

    // Try to extract patterns like "1 day", "7 days", "30 day", "3 Month", "365 day", etc.
    if (preg_match('/(\d+)\s*(day|week|month|year)s?/i', $monthValidate, $matches)) {
        $num = (int)$matches[1];
        $unit = strtolower($matches[2]);
        // Pluralize only if $num > 1
        if ($num > 1) {
            $unit = match($unit) {
                'day' => 'days',
                'week' => 'weeks',
                'month' => 'months',
                'year' => 'years',
                default => $unit
            };
        }
        return "{$num} {$unit}";
    }
    // Try to extract patterns like "3-days", "7-days", "30day", "30days"
    if (preg_match('/(\d+)\s*-\s*(day|week|month|year)s?/i', $monthValidate, $matches)) {
        $num = (int)$matches[1];
        $unit = strtolower($matches[2]);
        if ($num > 1) {
            $unit = match($unit) {
                'day' => 'days',
                'week' => 'weeks',
                'month' => 'months',
                'year' => 'years',
                default => $unit
            };
        }
        return "{$num} {$unit}";
    }
    if (preg_match('/(\d+)\s*(day|week|month|year)s?/i', str_replace('-', ' ', $monthValidate), $matches)) {
        $num = (int)$matches[1];
        $unit = strtolower($matches[2]);
        if ($num > 1) {
            $unit = match($unit) {
                'day' => 'days',
                'week' => 'weeks',
                'month' => 'months',
                'year' => 'years',
                default => $unit
            };
        }
        return "{$num} {$unit}";
    }
    // Fallback
    return $monthValidate;
}

}
