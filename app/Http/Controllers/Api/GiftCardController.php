<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Transactions;
use App\Models\WalletTransactions;
use Illuminate\Support\Facades\Cache;
use App\Services\CurrencyHelper;
use App\MyFunctions;
use App\Services\ReferralService;
use App\Services\PercentageService;
use App\Services\PinService;
use App\Helpers\ReloadlyHelper;




class GiftCardController extends Controller
{
    protected $baseUrl, $username, $password, $network = '13';

    public function __construct()
    {
        $this->baseUrl = config('api.artx.base_url');
        $this->username = config('api.artx.username');
        $this->password = sha1(config('api.artx.password'));
    }

    protected function authPayload()
    {
        $salt = Str::random(40);
        return [
            'salt' => $salt,
            'username' => $this->username,
            'password' => hash('sha512', $salt . $this->password)
        ];
    }

    public function getCountries()
    {
        $countries = Cache::remember('giftcard_countries', now()->addHours(24), function () {
            $payload = [
                'auth' => $this->authPayload(),
                'version' => 5,
                'command' => 'getOperators'
            ];

            $response = Http::withoutVerifying()->post($this->baseUrl, $payload)->json();
            $result = $response['result'] ?? [];

            return collect($result)
                ->filter(fn($item) => isset($item['country']))
                ->mapWithKeys(fn($item) => [$item['country']['id'] => $item['country']])
                ->unique()
                ->map(function ($country) {
                    return [
                        'id' => $country['id'],
                        'name' => $country['name'],
                        'flag' => "https://media.sochitel.com/img/flags/{$country['id']}.png"
                    ];
                })
                ->sortBy('name')
                ->values()
                ->toArray();
        });

        return response()->json([
            'status' => true,
            'data' => $countries
        ]);
    }


    public function getGiftCards(Request $request)
    {
        $request->validate(['country' => 'required|string']);
        $country = strtoupper($request->country);
        $cacheKey = 'giftcards_' . $country;

        $cards = Cache::remember($cacheKey, now()->addHours(24), function () use ($country) {
            $payload = [
                'auth' => $this->authPayload(),
                'version' => 5,
                'command' => 'getOperators',
                'country' => $country,
                "productCategory" => "13"
            ];

            $response = Http::withoutVerifying()->post($this->baseUrl, $payload)->json();
            $result = $response['result'] ?? [];

            return collect($result)
                ->filter(function ($item) {
                    // Match any productCategories starting with "13."
                    return isset($item['productCategories']) &&
                        collect($item['productCategories'])->contains(function ($category) {
                            return str_starts_with((string) $category, '13.');
                        });
                })
                ->map(function ($item) {
                    return [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'currency' => $item['currency'],
                        'brandId' => $item['brandId'],
                        'logo' => "https://media.sochitel.com/img/operators/{$item['brandId']}.png",
                        'flag' => "https://media.sochitel.com/img/flags/{$item['country']['id']}.png"
                    ];
                })
                ->values()
                ->toArray(); // Store as array in cache
        });

        // Handle empty results
        if (empty($cards)) {
            return response()->json([
                'status' => false,
                'message' => 'No gift cards found for the selected country.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $cards
        ]);
    }


    public function getDenominations(Request $request, PercentageService $percentageService)
    {
        $request->validate([
            'operator_id' => 'required|string',
            'brand_id' => 'required|string'
        ]);

        $operatorId = $request->operator_id;
        $brandId = $request->brand_id;

        $payload = [
            'auth' => $this->authPayload(),
            'version' => 5,
            'command' => 'getOperatorProducts',
            'operator' => $operatorId
        ];

        $response = Http::withoutVerifying()->post($this->baseUrl, $payload)->json();
        $products = $response['result']['products'] ?? [];

        Log::info('Raw API response for getGiftCards', ['response' => $response]);

        // Check if no products are found
        if (empty($products)) {
            return response()->json([
                'status' => false,
                'message' => 'No items found for the selected operator.'
            ], 404);
        }

        $operatorCurrency = $response['result']['currency']['operator'] ?? 'USD';
        $currencySymbol = CurrencyHelper::getSymbol($operatorCurrency);

        $denoms = collect($products)->flatMap(function ($product, $id) use ($currencySymbol, $operatorId, $brandId, $percentageService) {
            $name = $product['name'] ?? 'Unknown';
            $priceType = $product['priceType'] ?? 'unknown';
            $priceMinOperator = $product['price']['min']['operator'] ?? null;
            $priceMinUser = $product['price']['min']['user'] ?? null;
            $priceMaxOperator = $product['price']['max']['operator'] ?? null;

            // Handle range pricing
            if ($priceType === 'range' && $priceMinOperator && $priceMinUser && $priceMaxOperator) {
                $conversionRate = $priceMinUser / $priceMinOperator;
                $predefinedPrices = [];

                for ($operatorPrice = $priceMinOperator; $operatorPrice <= $priceMaxOperator;) {
                    $userPrice = $operatorPrice * $conversionRate;

                    // Apply percentage to the user price using the network property
                    $adjustedUserPrice = $percentageService->calculateGiftCardDiscountedAmount($this->network, $userPrice);

                    $predefinedPrices[] = [
                        'operator_id' => $operatorId,
                        'product_id' => $id,
                        'name' => $name,
                        'brand_id' => $brandId,
                        'price_operator' => $currencySymbol . number_format($operatorPrice, 2),
                        'price_user' => '₦' . number_format($adjustedUserPrice, 2),
                        'operator_price_symbol' => $currencySymbol,
                        'user_price_symbol' => '₦'
                    ];

                    // Adjust increment logic
                    if ($operatorPrice < 100) {
                        $operatorPrice += 5;
                    } elseif ($operatorPrice < 200) {
                        $operatorPrice += 100;
                    } elseif ($operatorPrice < 500) {
                        $operatorPrice += 300;
                    } else {
                        $operatorPrice += 500;
                    }
                }

                return $predefinedPrices;
            }

            // Handle fixed price
            $priceOperator = $product['price']['operator'] ?? null;
            $priceUser = $product['price']['user'] ?? null;

            // Apply percentage to the user price
            $adjustedUserPrice = $priceUser ? $percentageService->calculateGiftCardDiscountedAmount($this->network, $priceUser) : null;

            return [
                [
                    'operator_id' => $operatorId,
                    'product_id' => $id,
                    'name' => $name,
                    'brand_id' => $brandId,
                    'price_operator' => $priceOperator ? $currencySymbol . number_format($priceOperator, 2) : 'N/A',
                    'price_user' => $adjustedUserPrice ? '₦' . number_format($adjustedUserPrice, 2) : 'N/A',
                    'operator_price_symbol' => $currencySymbol,
                    'user_price_symbol' => '₦'
                ]
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $denoms->values()
        ]);
    }


    public function purchase(Request $request, PinService $pinService)
    {
        $request->validate([
            'operator' => 'required|string',
            'product_id' => 'required|string',
            'amount' => 'required|numeric',
            'quantity' => 'required|integer|min:1|max:10'
        ]);

        $plan_id = $request->operator;
        $quantity = $request->quantity;
        $image = $request->image ?? null;

        $pin = $request->input('pin');
        $user = $request->user();


        if (!$pinService->checkPin($user, $pin)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid transaction pin.'
            ], 403);
        }

        $totalAmount = $request->amount * $request->quantity;
        $wallet_balance = $user->wallet_balance;

        if ($wallet_balance < $totalAmount) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Insufficient balance ₦' . number_format($wallet_balance)
                ],
                400
            );
        }

        $reference = MyFunctions::generateRequestId();
        $payload = [
            'auth' => $this->authPayload(),
            'version' => 5,
            'command' => 'execTransaction',
            'operator' => $request->operator,
            'productId' => $request->product_id,
            'amountOperator' => $request->amount,
            'quantity' => $request->quantity,
            'userReference' => $reference
        ];


        $response = Http::withoutVerifying()->post($this->baseUrl, $payload)->json();

        // Check if API responded with success
        $apiStatusId = $response['status']['id'] ?? null;
        $txStatus = match ($apiStatusId) {
            0 => 'Successful',
            1 => 'Pending',
            2 => 'Failed',
            default => 'Unknown'
        };

        $pin = $response['result']['pin'] ?? null;
        $operatorRef = $response['result']['userReference'] ?? null;
        $productName = $response['result']['operator']['name'] ?? null;
        $serial = $pin['serial'] ?? null;
        $epin = $pin['number'] ?? null;
        $instructions = $pin['instructions'] ?? null;

        // If Successful or Pending, deduct wallet
        if (in_array($txStatus, ['Successful', 'Pending'])) {
            $balanceBefore = $user->wallet_balance;
            $user->wallet_balance -= $totalAmount;
            $user->save();

            $walletTrans = new WalletTransactions();
            $walletTrans->trans_type = 'debit';
            $walletTrans->user = $user->username;
            $walletTrans->amount = $totalAmount;
            $walletTrans->service = 'giftcard';
            $walletTrans->status = 'Successful';
            $walletTrans->transaction_id = (string) $reference;
            $walletTrans->balance_before = $balanceBefore;
            $walletTrans->balance_after = $user->wallet_balance;
            $walletTrans->save();
        }

        // Save all transaction statuses including Failed
        $transaction = new Transactions();
        $transaction->user_id = $user->id;
        $transaction->username = $user->username;
        $transaction->amount = $totalAmount;
        $transaction->service_provider = $productName;
        $transaction->provider_id = $request->operator;
        $transaction->status = $txStatus;
        $transaction->service = 'giftcard';
        $transaction->image = $image;
        $transaction->transaction_id = (string) $reference;
        $transaction->service_plan = $quantity;
        $transaction->reference = (string) $operatorRef;
        $transaction->plan_id = $plan_id;
        $transaction->epin = $epin;
        $transaction->serial = $serial;
        $transaction->instructions = $instructions;
        $transaction->which_api = 'artx';
        $transaction->save();

        // Handle response messages
        if ($txStatus === 'Successful') {

            (new ReferralService())->handleFirstTransactionBonus($user, 'giftcard', $totalAmount);

            return response()->json([
                'status' => true,
                'message' => 'Gift card purchased successfully',
                'data' => $transaction,
            ]);
        } elseif ($txStatus === 'Pending') {
            return response()->json([
                'status' => true,
                'message' => 'Your purchase is being processed.',
                'data' => $transaction,
            ], 202);
        } elseif ($txStatus === 'Failed') {
            return response()->json([
                'status' => false,
                'message' => 'Transaction failed. You were not charged.',
                'data' => $transaction,
            ], 400);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Transaction returned unknown status. Please contact support.',
                'transaction_status' => $txStatus,
                'reference' => $reference
            ], 500);
        }
    }






    public function purchaseGiftcard(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'country_code' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric',
            'recipient_email' => 'nullable|email',
            'recipient_phone' => 'nullable|array',
        ]);

        $token = ReloadlyHelper::getAccessToken();

        $payload = [
            "productId" => $request->product_id,
            "countryCode" => $request->country_code,
            "quantity" => $request->quantity,
            "unitPrice" => $request->unit_price,
            "recipientEmail" => $request->recipient_email,
            "recipientPhoneDetails" => $request->recipient_phone,
            "customIdentifier" => uniqid('gift_'),
        ];

        $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/com.reloadly.giftcards-v1+json'
            ])
            ->post('https://giftcards.reloadly.com/orders', $payload);

        if ($response->failed()) {
            return response()->json(['error' => $response->json()], 400);
        }

        // Optionally save transaction in DB here

        return response()->json([
            'status' => 'success',
            'data' => $response->json(),
        ]);
    }


}
