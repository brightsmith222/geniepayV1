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

class SmileController extends Controller
{
    public function verifySmileAccount(Request $request)
{
    $request->validate(['email' => 'required|email']);

    try {
        $response = Http::withBasicAuth(
            config('api.vtpass.username'),
            config('api.vtpass.password')
        )->withoutVerifying()->post(config('api.vtpass.base_url') . 'merchant-verify', [
            'serviceID'   => 'smile-direct',
            'billersCode' => $request->email, 
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


    public function getSmilePlans()
    {
        $response = Http::withHeaders([
            'api-key' => config('api.vtpass.api_key')
        ])->withoutVerifying()->get(config('api.vtpass.base_url') . '/service-variations?serviceID=smile-direct');

        $data = $response->json();

        return response()->json([
            'status' => true,
            'plans' => $data['content']['varations'] ?? []
        ]);
    }

    public function purchaseSmileData(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'variation_code' => 'required|string',
            'amount' => 'required|numeric'
        ]);

        $user = $request->user();
        $wallet = $user->wallet_balance;

        if ($wallet < $request->amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient wallet balance'
            ], 401);
        }

        $transactionId = Str::uuid();

        $payload = [
            'request_id' => $transactionId,
            'serviceID' => 'smile-direct',
            'billersCode' => $request->email,
            'variation_code' => $request->variation_code,
            'amount' => $request->amount,
            'phone' => $user->phone
        ];

        $response = Http::withHeaders([
            'api-key' => config('api.vtpass.api_key')
        ])->withoutVerifying()->post(config('api.vtpass.base_url') . '/pay', $payload);

        $data = $response->json();

        if ($data['code'] == '000') {
            // Deduct wallet
            $balance_before = $user->wallet_balance;
            $user->wallet_balance -= $request->amount;
            $user->save();

            Transactions::create([
                'user_id' => $user->id,
                'username' => $user->username,
                'amount' => $request->amount,
                'service' => 'smile',
                'service_provider' => 'smile',
                'status' => 'Successful',
                'transaction_id' => $transactionId,
                'smart_card_number' => $request->email,
                'which_api' => 'vtpass',
            ]);

            WalletTransactions::create([
                'user' => $user->username,
                'trans_type' => 'debit',
                'amount' => $request->amount,
                'service' => 'smile',
                'transaction_id' => $transactionId,
                'balance_before' => $balance_before,
                'balance_after' => $user->wallet_balance,
                'status' => 'Successful',
            ]);

            (new ReferralService())->handleFirstTransactionBonus($user, 'smile', $request->amount);

            return response()->json([
                'status' => true,
                'message' => 'Smile data purchase successful',
                'transaction_id' => $transactionId
            ]);
        }

        // If failed
        return response()->json([
            'status' => false,
            'message' => $data['response_description'] ?? 'Transaction failed',
            'error' => $data
        ], 500);
    }
}
