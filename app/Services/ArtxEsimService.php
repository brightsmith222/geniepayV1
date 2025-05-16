<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\GeneralSettings;
use App\MyFunctions;

class ArtxEsimService implements EsimServiceInterface
{
    protected $baseUrl;
    protected $username;
    protected $serviceName;
    protected $passwordHash;

    public function __construct()
    {
        $this->serviceName = 'artx';
        $this->baseUrl = config('api.artx.base_url');
        $this->username = config('api.artx.username');
        $this->passwordHash = sha1(config('api.artx.password'));
    }

    protected function authPayload(string $salt): array
    {
        return [
            'username' => $this->username,
            'salt' => $salt,
            'password' => hash('sha512', $salt . $this->passwordHash)
        ];
    }

    public function isEnabled(): bool
    {
        return (bool) GeneralSettings::where('name', $this->serviceName)->value('is_enabled');
    }

    public function supportsStatusCheck(): bool
    {
        return true;
    }

    public function getCountries(): array
    {
        $salt = Str::random(40);
        $payload = [
            'auth' => $this->authPayload($salt),
            'version' => 5,
            'command' => 'getOperators',
            'productType' => 13
        ];

        $response = Http::post($this->baseUrl, $payload)->json();

        if (isset($response['result'])) {
            $countries = [];

            foreach ($response['result'] as $item) {
                $country = $item['country'];
                $countries[$country['id']] = $country['name'];
            }

            return ['status' => true, 'data' => $countries];
        }

        return ['status' => false, 'message' => $response['status']['name'] ?? 'Failed'];
    }

    public function getPlans(string $countryCode): array
    {
        $salt = Str::random(40);
        $payload = [
            'auth' => $this->authPayload($salt),
            'version' => 5,
            'command' => 'getOperators',
            'productType' => 13,
            'country' => $countryCode
        ];

        $response = Http::post($this->baseUrl, $payload)->json();

        if (!isset($response['result'])) {
            return ['status' => false, 'message' => $response['status']['name'] ?? 'No plans found'];
        }

        $plans = [];

        foreach ($response['result'] as $operatorId => $item) {
            $plans[] = [
                'operator_id' => $operatorId,
                'name' => $item['name'],
                'brand_id' => $item['brandId'],
                'logo' => "https://media.sochitel.com/img/operators/{$item['brandId']}.png"
            ];
        }

        return ['status' => true, 'data' => $plans];
    }

    public function purchaseEsim(array $requestData): array
    {
        $salt = Str::random(40);
        $payload = [
            'auth' => $this->authPayload($salt),
            'version' => 5,
            'command' => 'execTransaction',
            'operator' => $requestData['operator'],
            'productId' => $requestData['product_id'],
            'amount' => $requestData['amount'],
            'userReference' => MyFunctions::generateRequestId(),
            'extraParameters' => []
        ];

        $response = Http::post($this->baseUrl, $payload)->json();

        if ($response['status']['type'] == 0) {
            return [
                'status' => true,
                'transaction_id' => $response['result']['id'],
                'pin' => $response['result']['pin'] ?? null,
                'reference' => $response['result']['operator']['reference'] ?? null
            ];
        }

        return ['status' => false, 'message' => $response['status']['name'] ?? 'Purchase failed'];
    }

    public function checkTransactionStatus(string $transactionId): array
    {
        $salt = Str::random(40);
        $payload = [
            'auth' => $this->authPayload($salt),
            'version' => 5,
            'command' => 'getTransaction',
            'id' => $transactionId
        ];

        $response = Http::post($this->baseUrl, $payload)->json();

        return [
            'status' => $response['status']['name'],
            'status_id' => $response['status']['id'],
            'type' => $response['status']['type']
        ];
    }
}
