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
use Illuminate\Support\Facades\DB;
use App\Jobs\FetchGiftCardPin;
use App\Mail\TransactionSuccessMail;
use Illuminate\Support\Facades\Mail;



class GiftCardController extends Controller
{
    protected $baseUrl, $username, $password, $network = '13';

    // ARTX API STARTS HERE
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
                        'price_operator' =>  number_format($operatorPrice, 2),
                        'price_user' => number_format($adjustedUserPrice, 2),
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
            $walletTrans->user_id = $user->id;
            $walletTrans->user = $user->username;
            $walletTrans->amount = $totalAmount;
            $walletTrans->service = 'giftcard';
            $walletTrans->status = 'Successful';
            $walletTrans->transaction_id = (string) $reference;
            $walletTrans->balance_before = $balanceBefore;
            $walletTrans->balance_after = $user->wallet_balance;
            $walletTrans->save();

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
        }

       

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

    // ARTX API END HERE

    ///RELOADLY API STARTS HERE


    public function getCountry()
    {
        try {
            // Cache for 24 hours (1440 minutes)
            $countries = Cache::remember('reloadly_countries', now()->addHours(24), function () {
                $token = ReloadlyHelper::getAccessToken();

                $response = Http::withToken($token)
                    ->withoutVerifying()
                    ->get(ReloadlyHelper::baseUrl() . '/countries');

                if ($response->failed()) {
                    throw new \Exception('Failed to fetch countries from Reloadly');
                }

                return $response->json();
            });

            return response()->json([
                'status' => 'success',
                'countries' => $countries
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Reloadly error: ' . $e->getMessage()], 500);
        }
    }



    public function getProductsByCountry($countryIso)
    {
        try {
            $token = ReloadlyHelper::getAccessToken();

            $response = Http::withToken($token)->withoutVerifying()
                ->get(ReloadlyHelper::baseUrl() . "/countries/{$countryIso}/products");

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to fetch gift card products for this country',
                    'message' => 'No gift cards found for the selected country.',
                    'details' => $response->json()
                ], 500);
            }

            $products = collect($response->json())->map(function ($product) {
                return [
                    'productId'   => $product['productId'] ?? null,
                    'brandId'     => $product['brand']['brandId'] ?? null,
                    'productName' => $product['productName'] ?? null,
                    'productType' => $product['denominationType'] ?? null,
                    'logoUrls'    => $product['logoUrls'] ?? [],
                ];
            })->values();

            Log::info('Raw API response for getProductsByCountry', ['response' => $response->json()]);

            // Add this check for empty products
            if ($products->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No gift cards found for the selected country.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'country' => $countryIso,
                'products' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Reloadly error: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getProductDetails(Request $request, PercentageService $percentageService)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'brand_id' => 'required|integer',
            'image' => 'nullable|string',
        ]);

        $productId = $request->product_id;
        $brandId = $request->brand_id;

        try {
            $token = ReloadlyHelper::getAccessToken();

            $response = Http::withToken($token)->withoutVerifying()
                ->get(ReloadlyHelper::baseUrl() . "/products/{$productId}");

            $product = $response->json();

            if (!$response->ok() || empty($product)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Product not found on Reloadly.'
                ], 404);
            }

            Log::info('Reloadly Product Info', ['product' => $product]);

            $denominationType = $product['denominationType'];
            $name = $product['productName'] ?? 'Unknown';
            $currencyCode = $product['recipientCurrencyCode'] ?? 'USD';
            $currencySymbol = CurrencyHelper::getSymbol($currencyCode);

            $result = [];

            if ($denominationType === 'FIXED') {
                $recipientDenoms = $product['fixedRecipientDenominations'] ?? [];
                $senderDenoms = $product['fixedSenderDenominations'] ?? [];

                foreach ($recipientDenoms as $index => $recipientPrice) {
                    $userPrice = $senderDenoms[$index] ?? null;

                    $adjustedUserPrice = $userPrice
                        ? $percentageService->calculateGiftCardDiscountedAmount($this->network, $userPrice)
                        : null;

                    $result[] = [
                        'product_id' => $productId,
                        'brand_id' => $brandId,
                        'name' => $name,
                        'price_operator' =>  number_format($recipientPrice, 2),
                        'price_user' => $adjustedUserPrice ?  number_format($adjustedUserPrice, 2) : 'N/A',
                        'operator_price_symbol' => $currencySymbol,
                        'user_price_symbol' => '₦',
                        'image' => $request->image ?? null
                    ];
                }
            } elseif ($denominationType === 'RANGE') {
                $minOperator = $product['minRecipientDenomination'] ?? null;
                $maxOperator = $product['maxRecipientDenomination'] ?? null;
                $minUser = $product['minSenderDenomination'] ?? null;
                $maxUser = $product['maxSenderDenomination'] ?? null;

                if (!$minOperator || !$maxOperator || !$minUser || !$maxUser) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Incomplete range pricing data.'
                    ], 422);
                }

                $conversionRate = $minUser / $minOperator;

                // Predefined steps
                $steps = [5, 10, 15, 20, 25, 50, 100, 125, 150, 200, 500, 1000];

                // Find the first step >= minOperator
                $startIndex = 0;
                foreach ($steps as $i => $step) {
                    if ($step >= $minOperator) {
                        $startIndex = $i;
                        break;
                    }
                }

                // Loop through steps, only include those within min/max
                for ($i = $startIndex; $i < count($steps); $i++) {
                    $opPrice = $steps[$i];
                    if ($opPrice > $maxOperator) break;

                    $userPrice = $opPrice * $conversionRate;
                    $adjustedUserPrice = $percentageService->calculateGiftCardDiscountedAmount($this->network, $userPrice);

                    $result[] = [
                        'product_id' => $productId,
                        'brand_id' => $brandId,
                        'name' => $name,
                        'price_operator' => number_format($opPrice, 2),
                        'price_user' =>  number_format($adjustedUserPrice, 2),
                        'operator_price_symbol' => $currencySymbol,
                        'user_price_symbol' => '₦'
                    ];
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Unsupported denomination type.'
                ], 422);
            }

            return response()->json([
                'status' => true,
                'data' => collect($result)->values()
            ]);
        } catch (\Exception $e) {
            Log::error('Reloadly getDenominations error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching denominations.'
            ], 500);
        }
    }

    public function purchaseCard(Request $request, PinService $pinService)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'brand_id' => 'required|integer',
            'price_operator' => 'required|numeric',
            'price_user' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
            'image' => 'nullable|string',
        ]);

        $pin = $request->input('pin');
        $user = $request->user();


        if (!$pinService->checkPin($user, $pin)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid transaction pin.'
            ], 403);
        }
        $totalAmount = $request->price_user * $request->quantity;

        if ($user->wallet_balance < $totalAmount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient wallet balance.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $reference = MyFunctions::generateRequestId();

            $token = ReloadlyHelper::getAccessToken();
            $payload = [
                'productId' => (int) $request->product_id,
                'brandId' => (int) $request->brand_id,
                'quantity' => (int) $request->quantity,
                'unitPrice' => (float) $request->price_operator,
                'customIdentifier' => $reference,
                'senderEmail' => $user->email,
                'recipientEmail' => $user->email
            ];

            $response = Http::withToken($token)->withoutVerifying()
                ->post(ReloadlyHelper::baseUrl() . '/orders', $payload);

            $data = $response->json();

            Log::info('Reloadly gift card order response', ['response' => $data]);

            // Check for status in the response
            $status = strtoupper($data['status'] ?? '');

            if (in_array($status, ['REFUNDED', 'FAILED'])) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => $data['status'] ?? 'Gift card purchase failed. Status: ' . $status,
                    'details' => $data
                ], 400);
            }

            if (in_array($status, ['SUCCESSFUL', 'PROCESSING', 'PENDING'])) {
                // Debit user
                $balanceBefore = $user->wallet_balance;
                $balanceAfter = $balanceBefore - $totalAmount;
                $user->wallet_balance = $balanceAfter;
                $user->save();

                // Save transaction
                $walletTrans = new WalletTransactions();
                $walletTrans->trans_type = 'debit';
                $walletTrans->user_id = $user->id;
                $walletTrans->user = $user->username;
                $walletTrans->amount = $totalAmount;
                $walletTrans->service = 'giftcard';
                $walletTrans->status = 'Successful';
                $walletTrans->transaction_id = (string) $reference;
                $walletTrans->balance_before = $balanceBefore;
                $walletTrans->balance_after = $balanceAfter;
                $walletTrans->save();

                $transaction = new Transactions();
                $transaction->user_id = $user->id;
                $transaction->username = $user->username;
                $transaction->amount = $totalAmount;
                $transaction->service_provider = $data['product']['productName']  ?? '';
                $transaction->provider_id = $request->product_id;
                $transaction->status = ucfirst(strtolower($status));
                $transaction->service = 'giftcard';
                $transaction->image = $request->image;
                $transaction->transaction_id = $data['transactionId'] ?? '';
                $transaction->service_plan = $data['product']['brand']['brandName'] ?? '';
                $transaction->quantity = $request->quantity;
                $transaction->reference = (string) $reference;
                $transaction->plan_id = $request->brand_id;
                // $transaction->epin = $epin;
                // $transaction->serial = $serial;
                // $transaction->instructions = $instructions;
                $transaction->which_api = 'reloadly';
                $transaction->save();

                if (in_array($status, ['SUCCESSFUL'])) {
                    (new ReferralService())->handleFirstTransactionBonus($user, 'giftcard', $totalAmount);
                    $cardDetails = ReloadlyHelper::getCardDetails($data['transactionId']);
                    $cardInstructions = ReloadlyHelper::getGiftCardRedeemInstructions($request->product_id);
                    // Update transaction with card info
                    if ($cardDetails && $cardDetails['success']) {
                        $transaction->epin = $cardDetails['data']['pinCode'] ?? null;
                        $transaction->serial = $cardDetails['data']['serialNumber'] ?? null;
                        $transaction->save();
                        $pins = collect($cardDetails['data'] ?? [])->pluck('pinCode')->implode(', ');
                        $serials = collect($cardDetails['data'] ?? [])->pluck('cardNumber')->implode(', ');

                        $details = [
                            'Service Provider' => $data['product']['productName'] ?? 'Unknown',
                            'Plan' => $data['product']['brand']['brandName'] ?? '',
                            'Amount' => '₦' . number_format($totalAmount, 2),
                            'PIN' => $pins,
                            'Serial' => $serials,
                        ];
                    }

                    if ($cardInstructions && $cardInstructions['success']) {
                        $transaction->instructions = $cardInstructions['data']['verbose'] ?? null;
                        $transaction->save();
                    }


                    if (!empty($transaction->instructions)) {
                        $details['Instructions'] = $transaction->instructions;
                    }
                    Mail::to($user->email)->send(new TransactionSuccessMail($details, 'Giftcard Purchase Details', 'Your Giftcard Purchase Details'));
                    // If available, save details to transaction
                } elseif (in_array($status, ['PROCESSING', 'PENDING'])) {
                    // Queue job to fetch PIN later
                    FetchGiftCardPin::dispatch($data['transactionId'], $transaction->id)->delay(now()->addSeconds(10));
                }


                Log::info('Reloadly gift card details', ['card_details' => $cardDetails]);



                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Gift card purchased successfully',
                    'data' => $transaction,
                    'card_details' => $cardDetails['data'] ?? null,
                    'card_instructions' => $cardInstructions['data']['verbose'] ?? null


                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Unexpected status: ' . $status,
                    'details' => $data
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gift card purchase error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    public function getGiftCardRedeemCode($orderId)
    {
        try {
            // Step 1: Get access token for gift cards
            $token = ReloadlyHelper::getAccessToken();
            Log::info('getGiftCardRedeemCode: Token acquired');

            // Step 2: Build URL and make request
            $url = "https://giftcards-sandbox.reloadly.com/orders/transactions/{$orderId}/cards";
            Log::info('getGiftCardRedeemCode: Calling URL', ['url' => $url]);

            $response = Http::withToken($token)
                ->withoutVerifying()
                ->get($url);

            // Step 3: Check response
            if (!$response->ok()) {
                Log::error('getGiftCardRedeemCode: Failed to retrieve redeem code', [
                    'orderId' => $orderId,
                    'response' => $response->body()
                ]);
                return response()->json(['status' => false, 'message' => 'Could not retrieve redeem code.']);
            }

            $redeemData = $response->json();
            Log::info('getGiftCardRedeemCode: Redeem code retrieved', ['data' => $redeemData]);

            return response()->json(['status' => true, 'data' => $redeemData]);
        } catch (\Exception $e) {
            Log::error('getGiftCardRedeemCode: Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }

    // RELOADLY API ENDS HERE
}
