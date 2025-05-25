<?php
// app/Http/Controllers/Api/AirtimeController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\WalletTransactions;
use App\Services\ReferralService;
use App\Services\PercentageService;
use App\Services\ApiServiceFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\ApiServiceInterface;
use App\Jobs\CheckArtxTransactionStatus;
use App\Services\ArtxAirtimeService;

class AirtimeController extends Controller
{
    public function buyAirtime(Request $request, PercentageService $airtimePercentageService)
    {
        $validator = Validator::make($request->all(), [
            'network' => 'required|integer',
            'amount' => 'required',
            'image' => 'string|nullable',
            'mobile_number' => ['required', 'string', 'min:11', 'max:11'],
            'regex:/^0[7-9][0-1]\d{8}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $network = $request->input('network');
            $mobile_number = $request->input('mobile_number');
            $amount = $request->input('amount');
            $user = $request->user();
            $wallet_balance = $user->wallet_balance;

            // Validate wallet balance and amount
            if ($wallet_balance < $amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can\'t topup due to insufficient balance N' . $wallet_balance
                ], 401);
            }

            if ($amount < 100) {
                return response()->json([
                    'status' => false,
                    'message' => 'Minimum airtime topup is N100'
                ], 401);
            }

            // Get the active API service
            $apiService = $this->getActiveApiService();
            if (!$apiService) {
                return response()->json([
                    'status' => false,
                    'message' => 'Our service is currently not available.'
                ], 401);
            }

            // Validate network and number
            $validationResult = $this->validateNetworkAndNumber($network, $mobile_number);
            if ($validationResult !== true) {
                return $validationResult;
            }

            // Calculate discounted amount
            $amount_charged = $airtimePercentageService->calculateDiscountedAmount($network, $amount);

            // Process the request through the API service
            $response = $apiService->processRequest([
                'network' => $network,
                'mobile_number' => $mobile_number,
                'amount' => $amount
            ]);

            // Handle the response
            return $this->handleApiResponse($response, [
                'user' => $user,
                'amount' => $amount,
                'amount_charged' => $amount_charged,
                'mobile_number' => $mobile_number,
                'image' => $request->image,
                'network' => $network,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function getActiveApiService(): ?ApiServiceInterface
    {
        // Define the priority order of APIs to check
        $apiServices = ['glad_airtime', 'artx_airtime'];

        foreach ($apiServices as $apiName) {
            $service = ApiServiceFactory::create($apiName);
            if ($service && $service->isEnabled()) {
                Log::info("Using API service: {$apiName}");
                return $service;
            }
        }

        return null;
    }

    protected function validateNetworkAndNumber($network, $mobile_number)
    {
        $apiService = $this->getActiveApiService();

        // Get the properly formatted number for validation
        $validationNumber = ($apiService instanceof ArtxAirtimeService)
            ? '234' . substr($mobile_number, 1)
            : $mobile_number;

        if (!$apiService->validateNumberForNetwork($validationNumber, $network)) {
            $networkNames = [
                1 => 'MTN',
                2 => 'GLO',
                3 => 'Airtel',
                6 => '9Mobile'
            ];

            return response()->json([
                'status' => false,
                'message' => "This is not a valid {$networkNames[$network]} number"
            ], 400);
        }

        return true;
    }

    protected function handleApiResponse(array $response, array $context)
    {
        $apiService = $this->getActiveApiService();
        $handledResponse = $apiService->handleResponse($response, $context);

        $user = $context['user'];
        $amount = $context['amount'];
        $amount_charged = $context['amount_charged'];
        $userReference = $context['userReference'] ?? $handledResponse['userReference'] ?? null;


        // Add this block for pending transactions
        /* if (isset($handledResponse['pending']) && $handledResponse['pending']) {
        CheckArtxTransactionStatus::dispatch(
            $handledResponse['transaction_id'],
            $context
        )->delay(now()->addMinutes(2));
    }
*/

        if (isset($handledResponse['pending']) && $handledResponse['pending']) {

            $balance_before = $user->wallet_balance;
            $wallet_balance = $user->wallet_balance - $amount_charged;
            $user->wallet_balance = $wallet_balance;
            $user->save();

            $transaction = $this->createTransaction(
            $user,
            $amount,
            $handledResponse['network_name'],
            'Pending',
            $context['mobile_number'],
            $context['image'],
            $handledResponse['transaction_id'],
            $userReference
            );

            $this->createWalletTransaction(
                $user,
                $amount,
                $handledResponse['transaction_id'],
                $balance_before,
                $user->wallet_balance
            );


            return response()->json([
                'status' => true,
                'message' => 'Transaction is pending. We will update the status shortly.',
                'data' => $transaction
            ]);
        }

        if ($handledResponse['success']) {
            // Process successful transaction
            $balance_before = $user->wallet_balance;
            $wallet_balance = $user->wallet_balance - $amount_charged;
            $user->wallet_balance = $wallet_balance;
            $user->save();

            $transaction = $this->createTransaction(
                $user,
                $amount,
                $handledResponse['network_name'],
                'Successful',
                $context['mobile_number'],
                $context['image'],
                $handledResponse['transaction_id'],
                $userReference
            );


            $this->createWalletTransaction(
                $user,
                $amount,
                $handledResponse['transaction_id'],
                $balance_before,
                $user->wallet_balance
            );

            (new ReferralService())->handleFirstTransactionBonus($user, 'airtime', $amount);

            return response()->json([
                'status' => true,
                'message' => $handledResponse['message'],
                'data' => $transaction
            ]);
        }

        // Create failed transaction record
        $this->createTransaction(
            $context['user'] ?? null,
            $context['amount'],
            $context['network'],
            'Failed',
            $context['mobile_number'],
            $context['image'],
            $handledResponse['transaction_id'] ?? Str::uuid()
        );

        return response()->json([
            'status' => false,
            'message' => $handledResponse['message']
        ], $this->getHttpStatusCode($handledResponse['status_code']));
    }

    private function getHttpStatusCode(int $apiStatusCode): int
    {
        return match (true) {
            $apiStatusCode >= 400 && $apiStatusCode < 500 => 400,
            $apiStatusCode >= 500 => 503,
            default => 400
        };
    }


    protected function createTransaction($user, $amount, $network, $status, $mobile_number, $image, $transaction_id, $userReference = null)
    {
        $transaction = new Transactions();

        $transaction->user_id = $user->id;
        $transaction->username = $user->username;
        $transaction->amount = "{$amount}";
        $transaction->service_provider = $network;
        $transaction->status = $status;
        $transaction->service = 'airtime';
        $transaction->image = $image;
        $transaction->phone_number = $mobile_number;
        $transaction->transaction_id = $transaction_id;
        $transaction->reference = $userReference;
        $transaction->save();

        return $transaction;
    }

    protected function createWalletTransaction($user, $amount, $transaction_id, $balance_before, $balance_after)
    {
        $walletTrans = new WalletTransactions();
        $walletTrans->trans_type = 'debit';
        $walletTrans->user = $user->username;
        $walletTrans->amount = "{$amount}";
        $walletTrans->service = 'airtime';
        $walletTrans->status = 'Successful';
        $walletTrans->transaction_id = $transaction_id;
        $walletTrans->balance_before = $balance_before;
        $walletTrans->balance_after = $balance_after;
        $walletTrans->save();

        return $walletTrans;
    }
}
