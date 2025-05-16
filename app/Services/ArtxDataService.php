<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\MyFunctions;

class ArtxDataService extends BaseApiService implements ApiServiceInterface
{
    protected $baseUrl;
    protected $username;
    protected $passwordHash;

    public function __construct()
    {
        $this->serviceName = 'artx';
        $this->baseUrl = config('api.artx.base_url');
        $this->username = config('api.artx.username');
        $this->passwordHash = sha1(config('api.artx.password'));
    }

    public function supportsStatusCheck(): bool
    {
        return true;
    }

    public function processRequest(array $requestData): array
    {
        try {
            $salt = Str::random(40);
            $passwordHash = $this->generatePasswordHash($salt);
            
            $payload = [
                'auth' => [
                    'username' => $this->username,
                    'salt' => $salt,
                    'password' => $passwordHash,
                ],
                'version' => 5,
                'command' => 'execTransaction',
                'operator' => $this->mapNetwork($requestData['network']),
                'msisdn' => $this->normalizePhoneNumber($requestData['mobile_number']),
                'productId' => $requestData['plan_id'],
                'amountOperator' => $requestData['amount'],
                'userReference' => MyFunctions::generateRequestId(),
                'simulate' => 1,
            ];

            $response = Http::timeout(30)
                ->retry(2, 100)
                ->withoutVerifying()
                ->post($this->baseUrl, $payload);

            $statusCode = $response->status();
            $responseData = $response->json();

            return [
                'status_code' => $statusCode,
                'data' => $responseData,
                'raw_request' => $payload
            ];

        } catch (\Exception $e) {
            Log::error('ARTX Data Error', ['error' => $e->getMessage()]);
            return [
                'status_code' => 500,
                'data' => [
                    'status' => [
                        'id' => 1,
                        'name' => 'Unknown error',
                        'type' => 2
                    ],
                    'message' => 'An error occurred while processing your request'
                ]
            ];
        }
    }

    public function handleResponse(array $response, array $context): array
    {
        $statusCode = $response['status_code'];
        $responseData = $response['data'];

        $result = [
            'success' => false,
            'status_code' => $statusCode,
            'transaction_id' => Str::uuid(),
            'network_name' => $this->mapNetwork($context['network']),
            'message' => '',
            'raw_response' => $responseData
        ];

        if ($statusCode != 200) {
            $result['message'] = $this->getHttpErrorMessage($statusCode);
            return $result;
        }

        if (!isset($responseData['status'])) {
            $result['message'] = 'Invalid response format from ARTX';
            return $result;
        }

        $statusType = $responseData['status']['type'] ?? 2;
        $statusId = $responseData['status']['id'] ?? 1;

        if ($statusType == 0) {
            $result['success'] = true;
            $result['transaction_id'] = $responseData['result']['id'] ?? $result['transaction_id'];
            $result['message'] = $responseData['status']['name'] ?? 'Data purchase successful';
            $result['api_reference'] = $responseData['result']['operator']['reference'] ?? null;
            $result['operator_name'] = $responseData['result']['operator']['name'] ?? null;
            $result['instructions'] = $responseData['result']['instructions'] ?? null;
            
            return $result;
        }

        if ($statusType == 1) {
            $result['pending'] = true;
            $result['transaction_id'] = $responseData['result']['id'] ?? $result['transaction_id'];
            $result['message'] = $this->getPendingMessage($statusId);
            $result['api_reference'] = $responseData['result']['operator']['reference'] ?? null;
            $result['operator_name'] = $responseData['result']['operator']['name'] ?? null;
            $result['instructions'] = $responseData['result']['instructions'] ?? null;
            
            
            return $result;
        }

        $result['message'] = $this->getErrorMessage($statusId, $responseData);
        return $result;
    }

    public function getDataPlans(int $network): array
    {
        try {
            $salt = Str::random(40);
            $passwordHash = $this->generatePasswordHash($salt);
            
            $payload = [
                'auth' => [
                    'username' => $this->username,
                    'salt' => $salt,
                    'password' => $passwordHash,
                ],
                'version' => 5,
                'command' => 'getOperatorProducts',
                'operator' => $this->mapNetwork($network),
                'productCategory' => 4 // Mobile Data category
            ];

            $response = Http::timeout(30)
                ->retry(2, 100)
                ->withoutVerifying()
                ->post($this->baseUrl, $payload);

            $responseData = $response->json();
            Log::debug('ARTX Data Plans Response', ['response' => $responseData]);

            if (!isset($responseData['result']['products'])) {
                return [];
            }

            $plans = [];
            foreach ($responseData['result']['products'] as $productId => $product) {
                if ($product['productType']['id'] == 4) { // Mobile Data
                    $plans[] = [
                        'plan_id' => $productId,
                        'plan' => $product['name'],
                        'network' => $this->getNetworkName($network),
                        'amount' => $product['price']['user'],
                        'validity' => $this->extractValidity($product['name']),
                        'data_volume' => $this->extractDataVolume($product['name'])
                    ];
                }
            }

            return $plans;

        } catch (\Exception $e) {
            Log::error('ARTX Data Plans Error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function generatePasswordHash(string $salt): string
    {
        return hash('sha512', $salt.$this->passwordHash);
    }

    protected function mapNetwork(int $network): int
    {
        return match($network) {
            1 => 1, // MTN Nigeria 
            2 => 19, // GLO Nigeria 
            3 => 20, // Airtel Nigeria 
            6 => 539, // 9Mobile Nigeria 
            default => 0
        };
    }

    protected function getNetworkName(int $network): string
    {
        return match($network) {
            1 => 'MTN',
            2 => 'GLO',
            3 => 'Airtel',
            6 => '9Mobile',
            default => 'Unknown'
        };
    }

    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (strlen($digits) === 11 && $digits[0] === '0') {
            return '234' . substr($digits, 1);
        }
        return $digits;
    }

    protected function extractValidity(string $planName): string
    {
        // Extract validity from plan name (e.g., "1GB 30 Days" => "30 Days")
        if (preg_match('/(\d+\s*(Day|Month)s?)/i', $planName, $matches)) {
            return $matches[0];
        }
        return 'N/A';
    }

    protected function extractDataVolume(string $planName): string
    {
        // Extract data volume from plan name (e.g., "1GB 30 Days" => "1GB")
        if (preg_match('/(\d+\s*[KMGT]B)/i', $planName, $matches)) {
            return $matches[0];
        }
        return 'N/A';
    }

    protected function getHttpErrorMessage(int $statusCode): string
    {
        return match($statusCode) {
            401 => 'Authentication failed',
            403 => 'Access denied',
            404 => 'API endpoint not found',
            500 => 'ARTX server error',
            default => 'An error occurred while connecting to ARTX'
        };
    }

    protected function getPendingMessage(int $statusId): string
    {
        return match($statusId) {
            9 => 'Transaction is pending. Please check back later',
            46 => 'Transaction is in progress',
            59 => 'Transaction pending manual verification',
            default => 'Transaction is being processed'
        };
    }

    protected function getErrorMessage(int $statusId, array $responseData): string
    {
        $defaultMessage = $responseData['status']['name'] ?? 'Transaction failed';

        return match($statusId) {
            2 => 'Invalid operator selected',
            3 => 'Invalid phone number format',
            4 => 'Invalid amount specified',
            6 => 'Network system error',
            13 => 'Insufficient balance',
            52 => 'Ambiguous product',
            68 => 'Missing required parameters',
            default => $defaultMessage
        };
    }

    public function checkTransactionStatus(string $transactionId): array
    {
        try {
            $salt = Str::random(40);
            $passwordHash = $this->generatePasswordHash($salt);
            
            $payload = [
                'auth' => [
                    'username' => $this->username,
                    'salt' => $salt,
                    'password' => $passwordHash
                ],
                'version' => 5,
                'command' => 'getTransaction',
                'id' => $transactionId
            ];

            $response = Http::timeout(15)
                ->post($this->baseUrl, $payload);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('ARTX Status Check Error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => [
                    'id' => 1,
                    'name' => 'Status check failed',
                    'type' => 2
                ]
            ];
        }
    }

    public function getNetworkPrefixes(): array
    {
        return [
            1 => ['234803', '234703', '234903', '234806', '234706', '234813', '234810', '234814', '234816', '234906', '234913', '234801', '234707'],
            2 => ['234805', '234705', '234905', '234807', '234815', '234811', '234905', '234801'],
            3 => ['234802', '234902', '234701', '234808', '234708', '234812', '234904', '234901'],
            6 => ['234809', '234909', '234817', '234818']
        ];
    }
    
    protected function getPrefixLength(): int
    {
        return 6;
    }
}