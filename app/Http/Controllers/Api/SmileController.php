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

class SmileController extends Controller
{
    public function verifySmileAccount(Request $request)
    {
        $request->validate(['email' => 'required|email']);

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



            $body = $response->body(); // Always string
            $data = json_decode($body, true); // Convert to array

            if (!is_array($data)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid response from VTpass',
                    'raw' => $body
                ], 500);
            }

            return response()->json([
                'status' => ($data['code'] ?? '') == '000',
                'message' => $data['response_description'] ?? 'Verification failed',
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
    $vtpass = new VtpassService();
    $url = config('api.vtpass.base_url') . "service-variations?serviceID=smile-direct";

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
        ->get($url);

    $data = $response->json();
    Log::info('Smile Plans Response', ['response' => $data]);

    // Adjust the amount for each plan and extract validity
    $plans = collect($data['content']['varations'] ?? [])->map(function ($plan) use ($percentageService) {
        $plan['amount'] = $percentageService->calculateSmileDiscountedAmount((float) $plan['variation_amount']);

        // Extract validity from the name field
        preg_match('/(\d+)\s?(day|days|week|weeks|month|months|year|years)/i', $plan['name'], $matches);
        if (isset($matches[1], $matches[2])) {
            $number = (int) $matches[1];
            $unit = strtolower($matches[2]);

            // Ensure the unit is pluralized if the number is greater than 1
            if ($number > 1) {
                $unit = Str::plural($unit);
            }

            $plan['validity'] = "{$number} {$unit}";
        } else {
            $plan['validity'] = 'Unknown';
        }

        // Return only the required fields
        return [
            'variation_code' => $plan['variation_code'],
            'plan' => $plan['name'],
            'fixedPrice' => $plan['fixedPrice'],
            'amount' => $plan['amount'],
            'validity' => $plan['validity'],
        ];
    });

    // Sort the plans by validity_days in ascending order
    $sortedPlans = $plans->sortBy('validity')->values();

    return response()->json([
        'status' => true,
        'data' => $sortedPlans
    ]);
}

    public function purchaseSmileData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'variation_code' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = $request->user();

        if ($user->wallet_balance < $request->amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient wallet balance'
            ], 401);
        }

        try {
            $vtpass = new VtpassService();
            if (!$vtpass->isVtpassEnabled()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Smile service is currently disabled.'
                ]);
            }

            $headers = $vtpass->getHeaders();
            $verifyUrl = config('api.vtpass.base_url') . "merchant-verify";

            // Step 1: Verify email
            $verifyResponse = Http::withHeaders($headers)
                ->withoutVerifying()
                ->post($verifyUrl, [
                    'serviceID' => 'smile-direct',
                    'billersCode' => $request->email
                ]);

            $verifyData = $verifyResponse->json();

            $accounts = $verifyData['content']['AccountList']['Account'] ?? [];
            $accountId = $accounts[0]['AccountId'] ?? null;

            if (!$accountId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unable to retrieve Smile Account ID.',
                    'data' => $verifyData
                ], 400);
            }

            // Step 2: Purchase
            $transactionId = MyFunctions::generateRequestId();
            $payUrl = config('api.vtpass.base_url') . "pay";
            $payload = [
                'request_id'     => $transactionId,
                'serviceID'      => 'smile-direct',
                'billersCode'    => "08011111111", //$accountId,
                'variation_code' => $request->variation_code,
                'phone'          => $user->phone_number,
            ];

            $payResponse = Http::withHeaders($headers)
                ->withoutVerifying()
                ->post($payUrl, $payload);

            if (!$payResponse->ok()) {
                return response()->json([
                    'status' => false,
                    'message' => 'An error occured, please contact support',
                    'http_code' => $payResponse->status(),
                    'body' => $payResponse->body(),
                ], 502);
            }

            $payData = $payResponse->json();
            $tx = $payData['content']['transactions'] ?? [];

            // Even if code is 000, check actual transaction status
            $vtpassStatus = strtolower($tx['status'] ?? 'failed');
            $status = match ($vtpassStatus) {
                'delivered' => 'Successful',
                'pending'   => 'Pending',
                default     => 'Failed',
            };

            // Deduct wallet for Successful or Processing
            $balance_before = $user->wallet_balance;
            if (in_array($status, ['Successful', 'Pending'])) {
                $user->wallet_balance -= $request->amount;
                $user->save();

                $walletTrans = new  WalletTransactions();
                $walletTrans->trans_type = 'debit';
                $walletTrans->user = $user->username;
                $walletTrans->amount = $request->amount;
                $walletTrans->service = 'smile';
                $walletTrans->transaction_id = $transactionId;
                $walletTrans->balance_before = $balance_before;
                $walletTrans->balance_after = $user->wallet_balance;
                $walletTrans->status = $status;
                $walletTrans->save();
            }

            $transaction = new Transactions();
            $transaction->amount             = $request->amount;
            $transaction->user_id            = $user->id;
            $transaction->username           = $user->username;
            $transaction->status             = $status;
            $transaction->service_provider   = 'Smile';
            $transaction->service            = 'data';
            $transaction->plan_id            = $request->variation_code ?? null;
            $transaction->smart_card_number  = $tx['unique_element'] ?? $request->email;
            $transaction->service_plan       = $request->plan ?? null;
            $transaction->image              = $request->image ?? null;
            $transaction->transaction_id     = $transactionId;
            $transaction->quantity           = $tx['quantity'] ?? 1;
            $transaction->commission         = $tx['commission'] ?? '0';
            $transaction->which_api          = 'vtpass';
            $transaction->save();

            if ($status === 'Successful') {
                (new ReferralService())->handleFirstTransactionBonus($user, 'smile', $request->amount);
            }

            return response()->json([
                'status'  => $status !== 'Failed',
                'message' => $payData['response_description'] ?? 'Unknown result',
                'data'    => $transaction

            ], $status === 'Successful' ? 200 : ($status === 'Pending' ? 202 : 500));
        } catch (\Exception $e) {
            Log::error('Smile Purchase Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred during the purchase process',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
