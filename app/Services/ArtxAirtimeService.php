<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\MyFunctions;

class ArtxAirtimeService extends BaseApiService implements ApiServiceInterface
{
    protected $baseUrl;
    protected $username;
    protected $passwordHash;
    protected $clientId;

    public function __construct()
    {
        $this->serviceName = 'artx_airtime';
        $this->baseUrl = config('api.artx.base_url');
        $this->username = config('api.artx.username');
        $this->passwordHash = sha1(config('api.artx.password')); // First hash (SHA1)
    }

    public function supportsStatusCheck(): bool
{
    return true; // ARTX supports status checks
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
                'amount' => $requestData['amount'],
                'userReference' => MyFunctions::generateRequestId(),
                'simulate' => 1,
            ];

            Log::debug('ARTX Request Payload', ['payload' => $payload]);

            $response = Http::timeout(30)
            ->retry(2, 100)
            ->withoutVerifying() // Disable SSL verification
            ->post($this->baseUrl, $payload);
            Log::debug('ARTX API Request', [
                'url' => $this->baseUrl,
                'payload' => $payload,
                'response' => $response->body()
            ]);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::debug('ARTX API Response', [
                'status' => $statusCode,
                'response' => $responseData
            ]);

            return [
                'status_code' => $statusCode,
                'data' => $responseData,
                'raw_request' => $payload // For debugging
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('ARTX Connection Error', ['error' => $e->getMessage()]);
            return [
                'status_code' => 503,
                'data' => [
                    'status' => [
                        'id' => 48,
                        'name' => 'Communication error',
                        'type' => 2
                    ],
                    'message' => 'Service temporarily unavailable'
                ]
            ];
        } catch (\Exception $e) {
            Log::error('ARTX Processing Error', ['error' => $e->getMessage()]);
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
            'raw_response' => $responseData,
            'which_api' => 'artx',
            'operator_id' => $context['network'],
        ];

        // Handle HTTP errors
        if ($statusCode != 200) {
            $result['message'] = $this->getHttpErrorMessage($statusCode);
            return $result;
        }

        // Handle API response status
        if (!isset($responseData['status'])) {
            $result['message'] = 'Invalid response format from ARTX';
            return $result;
        }

        $statusType = $responseData['status']['type'] ?? 2; // Default to failure
        $statusId = $responseData['status']['id'] ?? 1; // Default to unknown error

        // Successful transaction
        if ($statusType == 0) {
            $result['success'] = true;
            $result['which_api'] = 'artx';
            $result['transaction_id'] = $responseData['result']['id'] ?? $result['transaction_id'];
            $result['message'] = $responseData['status']['name'] ?? 'Transaction successful';
            
            // Additional success data
            $result['api_reference'] = $responseData['result']['operator']['reference'] ?? null;
            $result['network_name'] = $responseData['result']['operator']['name'] ?? null;
            $result['operator_id'] = $responseData['result']['operator']['id'] ?? $context['network'];
            
            return $result;
        }

        // Pending transaction (type 1)
        if ($statusType == 1) {
            $result['pending'] = true;
            $result['which_api'] = 'artx';
            $result['transaction_id'] = $responseData['result']['id'] ?? $result['transaction_id'];
            $result['message'] = $responseData['status']['name'] ?? 'Transaction precessing';
            $result['api_reference'] = $responseData['result']['operator']['reference'] ?? null;
            $result['network_name'] = $responseData['result']['operator']['name'] ?? null;
            $result['operator_id'] = $responseData['result']['operator']['id'] ?? $context['network'];
            
            return $result;
        }

        // Failed transaction (type 2)
        $result['message'] = $this->getErrorMessage($statusId, $responseData);
        return $result;
    }

    protected function generatePasswordHash(string $salt): string
    {
        // ARTX requires: SHAx(salt.SHA1(password))
        return hash('sha512', $salt.$this->passwordHash); // Using SHA512 for higher security
    }

    protected function generateSignature(string $salt, array $requestData): string
    {
        $signatureData = [
            $this->passwordHash,
            $salt,
            'execTransaction',
            $this->mapNetwork($requestData['network']),
            null, // productId (not used)
            $requestData['amount'],
            null, // amount in operator's currency (not used)
            $this->normalizePhoneNumber($requestData['mobile_number']),
            Str::uuid()->toString() // transactionId
        ];

        return hash('sha512', implode('', $signatureData));
    }

   

protected function normalizePhoneNumber(string $phoneNumber): string
{
    // Remove any non-digit characters (just in case)
    $digits = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    // Convert 0803... to 234803...
    if (strlen($digits) === 11 && $digits[0] === '0') {
        return '234' . substr($digits, 1);
    }
    
    // Return as-is if already in ARTX format (unlikely in your case)
    return $digits;
}

    protected function mapNetwork(int $network): int
    {
        // Map your internal network codes to ARTX's operator IDs
        // These are example IDs - you need to replace with actual ARTX operator IDs
        return match($network) {
            1 => 1, // MTN Nigeria 
            2 => 19, // GLO Nigeria 
            3 => 20, // Airtel Nigeria 
            6 => 18, // 9Mobile Nigeria 
            default => 0
        };
    }

    protected function getHttpErrorMessage(int $statusCode): string
    {
        return match($statusCode) {
            401 => 'Authentication failed. Please verify your API credentials.',
            403 => 'Access denied. Please verify your account permissions.',
            404 => 'API endpoint not found.',
            422 => 'Validation failed. Please check your request parameters.',
            500 => 'ARTX server error. Please try again later.',
            503 => 'ARTX service is currently unavailable.',
            default => 'An error occurred while connecting to ARTX service.'
        };
    }

    protected function getPendingMessage(int $statusId): string
    {
        return match($statusId) {
            9 => 'Transaction is pending. Please check back later for status update.',
            46 => 'Transaction is in progress. Please check back later.',
            59 => 'Transaction pending manual verification. Please contact support.',
            default => 'Transaction is being processed. Please check back later.'
        };
    }

    protected function getErrorMessage(int $statusId, array $responseData): string
    {
        $defaultMessage = $responseData['status']['name'] ?? 'Transaction failed';

        // Custom messages for specific error codes
        return match($statusId) {
            2 => 'Invalid operator selected.',
            3 => 'Invalid phone number format.',
            4 => 'Invalid amount specified.',
            6 => 'Network system error. Please try again later. If this error persist please contact admin',
            7 => 'The recipient number is barred from receiving top-ups.',
            8 => 'The recipient number is inactive.',
            12 => 'Authentication failed. Please verify your API credentials.',
            13 => 'Something went wrong, please contact admin.',
            20 => 'Currency conversion error. Please contact support.',
            24 => 'Top-up failed. Please try again.',
            29 => 'Operator system temporarily unavailable. Please try again later.',
            37 => 'There is an issue perfoming this transaction, please contact admin.',
            52 => 'Ambiguous product. Please specify product ID.',
            56 => 'Invalid user account. Please contact support.',
            58 => 'Our system is down for maintenance.',
            68 => 'Missing required parameters in request.',
            70 => 'Operator system unreachable. Please try again later.',
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

            $response = Http::withHeaders($this->headers)
                ->timeout(15)
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
            1 => ['234803', '234703', '234903', '234806', '234706', '234813', '234810', '234814', '234816', '234906', '234913', '234801', '234707'], // MTN
            2 => ['234805', '234705', '234905', '234807', '234815', '234811', '234905', '234801'], // GLO
            3 => ['234802', '234902', '234701', '234808', '234708', '234812', '234904', '234901'], // Airtel
            6 => ['234809', '234909', '234817', '234818'] // 9Mobile
        ];
    }
    
    protected function getPrefixLength(): int
    {
        return 6; // ARTX uses 6-digit prefixes
    }
    
}
