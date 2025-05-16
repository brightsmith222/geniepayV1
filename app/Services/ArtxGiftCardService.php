<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\GeneralSettings;
use App\MyFunctions;

class ArtxGiftCardService implements GiftCardServiceInterface
{
    protected $baseUrl, $username, $passwordHash, $serviceName;

    public function __construct()
    {
        $this->serviceName = 'artx_giftcard';
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
            'productType' => 12
        ];

        $response = Http::withoutVerifying()->post($this->baseUrl, $payload);
        $data = $response->json();

        if (isset($data['result'])) {
            $countries = [];

            foreach ($data['result'] as $item) {
                $country = $item['country'] ?? null;
                if ($country && !isset($countries[$country['id']])) {
                    $countries[$country['id']] = $country['name'];
                }
            }

            return [
                'status' => true, 
                'data' => $countries
            ];
        }

        return [
            'status' => false, 
            'message' => $data['status']['name'] ?? 'Failed'
        ];
    }

    public function getGiftCards(string $countryCode): array
    {
        $salt = Str::random(40);
        $payload = [
            'auth' => $this->authPayload($salt),
            'version' => 5,
            'command' => 'getOperators',
            'productType' => 12,
            'country' => $countryCode
        ];

        $response = Http::withoutVerifying()->post($this->baseUrl, $payload);
        $data = $response->json();

        if (isset($data['result'])) {
            $cards = [];

            foreach ($data['result'] as $id => $item) {
                $cards[] = [
                    'id' => $id,
                    'name' => $item['name'],
                    'brand_id' => $item['brandId'],
                    'logo' => "https://media.sochitel.com/img/operators/{$item['brandId']}.png"
                ];
            }

            return [
                'status' => true, 
                'data' => $cards
            ];
        }

        return [
            'status' => false, 
            'message' => $data['status']['name'] ?? 'Failed'
        ];
    }

    public function getCardDenominations(string $operatorId): array
    {
        $salt = Str::random(40);
        $payload = [
            'auth' => $this->authPayload($salt),
            'version' => 5,
            'command' => 'getOperatorProducts',
            'operator' => $operatorId
        ];

        $response = Http::withoutVerifying()->post($this->baseUrl, $payload);
        $data = $response->json();

        if (isset($data['result']['products'])) {
            $denoms = [];

            foreach ($data['result']['products'] as $id => $item) {
                $denoms[] = [
                    'product_id' => $id,
                    'name' => $item['name'],
                    'price_label' => "{$item['price']['operator']} - ₦" . number_format($item['price']['user'], 2),
                    'price_operator' => $item['price']['operator'],
                    'price_user' => $item['price']['user']
                ];
            }

            return ['status' => true, 'data' => $denoms];
        }

        return ['status' => false, 'message' => $data['status']['name'] ?? 'Failed'];
    }

    public function purchaseCard(array $requestData): array
    {
        $salt = Str::random(40);
        $payload = [
            'auth' => $this->authPayload($salt),
            'version' => 5,
            'command' => 'execTransaction',
            'operator' => $requestData['operator'],
            'productId' => $requestData['product_id'],
            'amountOperator' => $requestData['amount'],
            'userReference' => MyFunctions::generateRequestId(),
            'extraParameters' => [],
                ];

        $response = Http::withoutVerifying()->post($this->baseUrl, $payload);
        $data = $response->json();

        if ($data['status']['type'] == 0) {
            return [
                'status' => true,
                'transaction_id' => $data['result']['id'],
                'pin' => $data['result']['pin'] ?? null,
                'reference' => $data['result']['operator']['reference'] ?? null
            ];
        }

        return [
            'status' => false,
            'message' => $data['status']['name'] ?? 'Purchase failed'
        ];
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

        $response = Http::withoutVerifying()->post($this->baseUrl, $payload);
        $data = $response->json();

        return [
            'status' => $data['status']['name'],
            'status_id' => $data['status']['id'],
            'type' => $data['status']['type']
        ];
    }
}
