<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Transactions;
use App\Models\WalletTransactions;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\VtpassService;
use App\MyFunctions;
use Illuminate\Support\Facades\Validator;
use App\Services\PercentageService;
use App\Services\BeneficiaryService;
use App\Services\PinService;

class SmileController extends Controller
{

    public function verifySmileAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Please enter a valid email address.'
            ], 422);
        }

        try {
            $vtpass = new VtpassService();
            $url = config('api.vtpass.base_url') . "merchant-verify";
            if ($vtpass->isVtpassEnabled()) {
                $headers = $vtpass->getHeaders();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Server call service is currently disabled.',
                ]);
            }
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->post($url, [
                    'serviceID' => 'smile-direct',
                    'billersCode' => $request->email
                ]);

            $body = $response->body();
            $data = json_decode($body, true);

            if (!is_array($data)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid response from Server',
                    'raw' => $body
                ], 500);
            }

            // Check for VTpass error code or missing account
            if (($data['code'] ?? '') !== '000' || empty($data['content']['AccountList']['Account'])) {
                $errorMsg = 'Smile account verification failed. Please check the email and try again.';
                return response()->json([
                    'status' => false,
                    'message' => $errorMsg,
                    'data' => $data
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => $data['response_description'] ?? 'Verification successful',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong during verification',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getSmilePlans(PercentageService $percentageService)
    {
        $cacheKey = 'smile_plans';

        $sortedPlans = cache()->remember($cacheKey, now()->addHours(6), function () use ($percentageService) {
            $vtpass = new VtpassService();
            $url = config('api.vtpass.base_url') . "service-variations?serviceID=smile-direct";

            if ($vtpass->isVtpassEnabled()) {
                $headers = $vtpass->getHeaders();
            } else {
                return collect();
            }

            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->get($url);

            $data = $response->json();
            Log::info('Smile Plans Response', ['response' => $data]);

            $plans = collect($data['content']['varations'] ?? [])->map(function ($plan) use ($percentageService) {
                $plan['amount'] = $percentageService->calculateSmileDiscountedAmount((float) $plan['variation_amount']);

                // Remove the trailing " - xxxx Naira" from the name
                $cleanName = preg_replace('/\s*-\s*[\d,\.]+\s*Naira$/i', '', $plan['name']);

                // Extract validity from the cleaned name
                preg_match('/(\d+)\s?(day|days|week|weeks|month|months|year|years)/i', $cleanName, $matches);
                if (isset($matches[1], $matches[2])) {
                    $number = (int) $matches[1];
                    $unit = strtolower($matches[2]);
                    if ($number > 1) {
                        $unit = \Illuminate\Support\Str::plural($unit);
                    }
                    $plan['validity'] = "{$number} {$unit}";
                } else {
                    $plan['validity'] = 'Unknown';
                }

                return [
                    'variation_code' => $plan['variation_code'],
                    'plan' => $cleanName,
                    'fixedPrice' => $plan['fixedPrice'],
                    'amount' => $plan['amount'],
                    'validity' => $plan['validity'],
                ];
            });

            return $plans->sortBy('validity')->values();
        });

        if ($sortedPlans->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Smile plans not available at the moment.'
            ], 503);
        }

        return response()->json([
            'status' => true,
            'data' => $sortedPlans
        ]);
    }

    public function purchaseInternetData(Request $request, PinService $pinService)
    {
        $type = $request->input('type'); // 'smile' or 'spectranet'
        $user = $request->user();
        $provider = $request->input('provider', $type === 'smile' ? 1 : 2);
        $variation_code = $request->input('variation_code');
        $amount = $request->input('amount');
        $beneficiary = $request->input('beneficiary', false);
        $identifier = $type === 'smile' ? $request->accountId : $request->customer_id;

        // Validate common fields
        $validator = Validator::make($request->all(), [
            'variation_code' => 'required|string',
            'amount' => 'required|numeric',
            'beneficiary' => 'required|boolean',
            'pin' => 'required',
            'type' => 'required|in:smile,spectranet',
            'email' => $type === 'smile' ? 'required|email' : 'nullable',
            'accountId' => $type === 'smile' ? 'required|string' : 'nullable',
            'customer_id' => $type === 'spectranet' ? 'required|string' : 'nullable',
            //'quantity' => $type === 'spectranet' ? 'required|integer|min:1' : 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        if (!$pinService->checkPin($user, $request->pin)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid transaction pin.'
            ], 403);
        }

        $wallet_balance = $user->wallet_balance;

        if ($wallet_balance < $amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance ₦' . number_format($wallet_balance)
            ], 401);
        }

        try {
            $vtpass = new VtpassService();
            if (!$vtpass->isVtpassEnabled()) {
                return response()->json([
                    'status' => false,
                    'message' => ucfirst($type) . ' service is currently disabled.'
                ]);
            }

            $transactionId = MyFunctions::generateRequestId();

             $headers = $vtpass->getHeaders();
            // $transactionId = MyFunctions::generateRequestId();
             $payUrl = config('api.vtpass.base_url') . "pay";
            // $identifier = $type === 'smile' ? $request->email : $request->customer_id;

            // // For Smile, verify account to get AccountId
            // if ($type === 'smile') {
            //     $verifyUrl = config('api.vtpass.base_url') . "merchant-verify";
            //     $verifyResponse = Http::withHeaders($headers)->post($verifyUrl, [
            //         'serviceID' => 'smile-direct',
            //         'billersCode' => $identifier
            //     ]);

            //     $verifyData = $verifyResponse->json();
            //     $accountId = $verifyData['content']['AccountList']['Account'][0]['AccountId'] ?? null;

            //     if (!$accountId) {
            //         return response()->json([
            //             'status' => false,
            //             'message' => 'Unable to retrieve Smile Account ID.',
            //             'data' => $verifyData
            //         ], 400);
            //     }

            //     $identifier = $accountId;
            // }

            // Prepare payload
            $payload = [
                'request_id'     => $transactionId,
                'serviceID'      => $type === 'smile' ? 'smile-direct' : 'spectranet',
                'billersCode'    => $identifier,
                'variation_code' => $variation_code,
                'phone'          => $user->phone_number,
            ];

            if ($type === 'spectranet') {
                $payload['quantity'] = 1;
            }

            // if ($type === 'spectranet') {
            //     $payload['quantity'] = $request->input('quantity', 1);
            // }


            $payResponse = Http::withoutVerifying()->withHeaders($headers)->post($payUrl, $payload);
            if (!$payResponse->ok()) {
                return response()->json([
                    'status' => false,
                    'message' => 'An error occurred, please contact support.',
                    'http_code' => $payResponse->status(),
                    'body' => $payResponse->body(),
                ], 502);
            }

            $payData = $payResponse->json();
            $tx = $payData['content']['transactions'] ?? [];

            $vtpassStatus = strtolower($tx['status'] ?? 'failed');
            $status = match ($vtpassStatus) {
                'delivered' => 'Successful',
                'pending'   => 'Pending',
                default     => 'Failed',
            };

            $balance_before = $user->wallet_balance;
            if (in_array($status, ['Successful', 'Pending'])) {
                $wallet_balance = $wallet_balance - $amount;
                $user->wallet_balance = $wallet_balance;
                $user->save();

                $walletTrans = new  WalletTransactions();
                $walletTrans->trans_type = 'debit';
                $walletTrans->user_id = $user->id;
                $walletTrans->user = $user->username;
                $walletTrans->amount = $request->amount;
                $walletTrans->service = $type;
                $walletTrans->transaction_id = (string) $transactionId;
                $walletTrans->balance_before = $balance_before;
                $walletTrans->balance_after = $wallet_balance;
                $walletTrans->status = $status;
                $walletTrans->save();

                if ($beneficiary && $identifier) {
                    (new BeneficiaryService())->save([
                        'type' => $type,
                        'identifier' => $type === 'smile' ? $request->email : $request->customer_id,
                        'provider' => $provider,
                    ], $user);
                }
            }

            

            $transaction = new Transactions();
            $transaction->amount             = $amount;
            $transaction->user_id            = $user->id;
            $transaction->username           = $user->username;
            $transaction->status             = $status;
            $transaction->service_provider   = $type;
            $transaction->provider_id        = $variation_code;
            $transaction->service            = 'data';
            $transaction->plan_id            = $request->variation_code ?? null;
            $transaction->smart_card_number  = $identifier;
            $transaction->service_plan       = $request->plan ?? null;
            $transaction->image              = $request->image ?? null;
            $transaction->transaction_id     = (string) $transactionId;
            $transaction->quantity           = $tx['quantity'] ?? 1;
            $transaction->commission         = $tx['commission'] ?? '0';
            $transaction->which_api          = 'vtpass';
            $transaction->save();

            

            if ($status === 'Successful') {
                (new ReferralService())->handleFirstTransactionBonus($user, $type, $amount);
            }

            return response()->json([
                'status' => $status !== 'Failed',
                'message' => $payData['response_description'] ?? 'Unknown result',
                'data' => $transaction,
            ], $status === 'Successful' ? 200 : ($status === 'Pending' ? 202 : 500));
        } catch (\Exception $e) {
            Log::error(strtoupper($type) . ' Purchase Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred during the purchase process',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
