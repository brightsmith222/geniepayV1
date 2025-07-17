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
use Illuminate\Support\Facades\Mail;
use App\Mail\TransactionSuccessMail;


class EsimController extends Controller
{
    protected $baseUrl, $username, $password, $network = '14'; // Category 14.0 for eSIM

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

    public function getEsims()
    {
        // Define a unique cache key
        $cacheKey = 'esims_list';

        // Check if the data is already cached
        return Cache::remember($cacheKey, now()->addHours(24), function () {
            $payload = [
                'auth' => $this->authPayload(),
                'version' => 5,
                'command' => 'getOperators',
                "productCategory" => "14.0"
            ];

            $response = Http::withoutVerifying()->post($this->baseUrl, $payload)->json();
            Log::info('ARTX eSIMs request', ['payload' => $payload, 'response' => $response]);
            $result = $response['result'] ?? [];
            Log::info('ARTX eSIMs response', ['response result' => $result]);

            return collect($result)
                ->filter(function ($item) {
                    return isset($item['productCategories']) &&
                        collect($item['productCategories'])->contains('14.0');
                })
                ->map(function ($item) {
                    return [
                        'status' => true,
                        'data' => [
                            'id' => $item['id'],
                            'name' => $item['name'],
                            'country' => $item['country']['name'] ?? 'Unknown',
                            'currency' => $item['currency'],
                            'brandId' => $item['brandId'],
                            'flag' => "https://media.sochitel.com/img/flags/{$item['country']['id']}.png"
                        ],
                    ];
                })
                ->values()
                ->toArray();
        });
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

        if (empty($products)) {
            return response()->json([
                'status' => false,
                'message' => 'No eSIM plans found for the selected operator.'
            ], 404);
        }

        $operatorCurrency = $response['result']['currency']['operator'] ?? 'USD';
        $currencySymbol = CurrencyHelper::getSymbol($operatorCurrency);

        $denoms = collect($products)->flatMap(function ($product, $id) use ($currencySymbol, $operatorId, $brandId, $percentageService) {
            $name = $product['name'] ?? 'Unknown';
            $priceOperator = $product['price']['operator'] ?? null;
            $priceUser = $product['price']['user'] ?? null;
            $adjustedUserPrice = $priceUser ? $percentageService->calculateEsimDiscountedAmount('14', $priceUser) : null;

            return [
                [
                    'operator_id' => $operatorId,
                    'product_id' => $id,
                    'plan_name' => 'eSim - ' . $name,
                    'brand_id' => $brandId,
                    'price_operator' => number_format($priceOperator, 2),
                    'price_user' => number_format($adjustedUserPrice, 2),
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
            'operator_amount' => 'required|numeric',
            'brand_id' => 'required|string',
            'plan_name' => 'required|string',
            'quantity' => 'required|integer',
        ]);

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
            'amountOperator' => $request->operator_amount,
            'quantity' => $request->quantity,
            'userReference' => $reference,
            //'msisdn' => "2348032337583",
            'simulate' => 1
        ];

        $response = Http::withoutVerifying()->post($this->baseUrl, $payload)->json();
        Log::info('ARTX eSIM purchase request', ['payload' => $payload]);
        Log::info('ARTX eSIM purchase response', ['response' => $response]);
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

        if (in_array($txStatus, ['Successful', 'Pending'])) {
            $balanceBefore = $user->wallet_balance;
            $balanceAfter = $wallet_balance - $totalAmount;
            $user->wallet_balance = $balanceAfter;
            $user->save();

            $walletTrans = new WalletTransactions();
            $walletTrans->trans_type = 'debit';
            $walletTrans->user = $user->username;
            $walletTrans->amount = $totalAmount;
            $walletTrans->service = 'esim';
            $walletTrans->status = 'Successful';
            $walletTrans->transaction_id = (string) $reference;
            $walletTrans->balance_before = $balanceBefore;
            $walletTrans->balance_after = $balanceAfter;
            $walletTrans->save();
        }

        $transaction = new Transactions();
        $transaction->user_id = $user->id;
        $transaction->username = $user->username;
        $transaction->amount = $totalAmount;
        $transaction->service_provider = $productName;
        $transaction->provider_id = $request->operator;
        $transaction->status = $txStatus;
        $transaction->service = 'esim';
        $transaction->service_plan = $request->plan_name;
        $transaction->image = $request->brand_id;
        $transaction->transaction_id = (string) $reference;
        $transaction->reference = (string) $operatorRef;
        $transaction->plan_id = $request->product_id;
        $transaction->epin = $pin['number'] ?? null;
        $transaction->serial = $pin['serial'] ?? null;
        $transaction->instructions = $pin['instructions'] ?? null;
        $transaction->which_api = 'artx';
        $transaction->save();

        if ($txStatus === 'Successful') {
            (new ReferralService())->handleFirstTransactionBonus($user, 'esim', $totalAmount);

            // Send email to user
            try {
                $details = [
                    'Service Provider' => $transaction->service_provider,
                    'Plan' => $transaction->service_plan,
                    'Amount' => '₦' . number_format($transaction->amount, 2),
                    'ePIN' => $transaction->epin,
                    'Serial' => $transaction->serial,
                ];
                if (!empty($transaction->instructions)) {
                    $details['Instructions'] = $transaction->instructions;
                }
                Mail::to($user->email)->send(new TransactionSuccessMail($details, 'eSIM Purchase Details', 'Your eSIM Purchase Details'));
            } catch (\Throwable $e) {
                Log::error('Failed to send eSIM purchase email: ' . $e->getMessage());
            }

            return response()->json([
                'status' => true,
                'message' => 'eSIM purchased successfully',
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
                'message' => 'Unknown transaction status. Contact support.',
                'reference' => $reference
            ], 500);
        }
    }
}
