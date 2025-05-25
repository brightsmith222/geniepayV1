<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transactions;
use App\Models\WalletTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\ApiServiceFactory;
use Illuminate\Support\Facades\Log;
use App\Services\ReferralService;
use Illuminate\Support\Str;



class BaseDataController extends Controller
{
    protected function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'network' => 'required|integer',
            'amount' => 'required',
            'original_amount' => 'nullable',
            'plan' => 'required',
            'plan_size' => 'string|required',
            'image' => 'string|nullable',
            'mobile_number' => ['required', 'string', 'min:11', 'max:11'],
        ]);
    }

    protected function getActiveApiService(string $serviceType = 'data'): ?object
    {
        $apiServices = ['artx_data', 'glad_data'];

        foreach ($apiServices as $apiName) {
            Log::info("Attempting to create API service: {$apiName}");
            $service = ApiServiceFactory::create($apiName, $serviceType);

            if ($service) {
                Log::info("Created API service: {$apiName}");
                if ($service->isEnabled()) {
                    Log::info("Using API service: {$apiName} for {$serviceType}");
                    return $service;
                } else {
                    Log::info("API service {$apiName} is not enabled.");
                }
            } else {
                Log::warning("Failed to create API service: {$apiName}");
            }
        }

        Log::error("No active API service found for {$serviceType}");
        return null;
    }

    protected function validateNetworkAndNumber($apiService, $network, $mobile_number)
{
    // Normalize the phone number for validation
    $validationNumber = ($apiService instanceof \App\Services\ArtxDataService)
        ? '234' . substr($mobile_number, 1)
        : $mobile_number;

    // Get the network prefixes
    $networkPrefixes = $apiService->getNetworkPrefixes();

    // Check if the network is valid and has prefixes
    if (!isset($networkPrefixes[$network])) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid phone number'
        ], 400);
    }

    // Check if the number matches any prefix for the selected network
    $isValidPrefix = false;
    foreach ($networkPrefixes[$network] as $prefix) {
        if (str_starts_with($validationNumber, $prefix)) {
            $isValidPrefix = true;
            break;
        }
    }

    if (!$isValidPrefix) {
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

    // Validate the number using the API service
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

    protected function createTransaction($user, $amount, $network, $status, $mobile_number, $image, $transaction_id, $plan_size, $plan_id = null, $userReference = null)
    {
        $transaction = new Transactions();

        $transaction->user_id = $user->id;
        $transaction->username = $user->username;
        $transaction->amount = "{$amount}";
        $transaction->service_provider = $network;
        $transaction->status = $status;
        $transaction->service = 'data';
        $transaction->image = $image;
        $transaction->phone_number = $mobile_number;
        $transaction->transaction_id = $transaction_id;
        $transaction->service_plan = $plan_size;
        $transaction->reference = $userReference;
        $transaction->plan_id = $plan_id;
        $transaction->save();

        return $transaction;
    }

    protected function createWalletTransaction($user, $amount, $transaction_id, $balance_before, $balance_after)
    {
        $walletTrans = new WalletTransactions();
        $walletTrans->trans_type = 'debit';
        $walletTrans->user = $user->username;
        $walletTrans->amount = "{$amount}";
        $walletTrans->service = 'data';
        $walletTrans->status = 'Successful';
        $walletTrans->transaction_id = $transaction_id;
        $walletTrans->balance_before = $balance_before;
        $walletTrans->balance_after = $balance_after;
        $walletTrans->save();

        return $walletTrans;
    }

    protected function handleApiResponse($apiService, array $response, array $context)
    {
        $handledResponse = $apiService->handleResponse($response, $context);

        $user = $context['user'];
        $amount = $context['amount'];
        $amount_charged = $context['amount_charged'];

        if ($handledResponse['success']) {
            $balance_before = $user->wallet_balance;
            $user->wallet_balance = $balance_before - $amount_charged;
            $user->save();

            $transaction = $this->createTransaction(
                $user,
                $amount,
                $handledResponse['network_name'],
                'Successful', // Set status to 'Successful'
                $context['mobile_number'],
                $context['image'],
                $handledResponse['transaction_id'],
                $context['plan_size'],
                $handledResponse['plan_id'],
                $handledResponse['api_reference']
            );

            $this->createWalletTransaction(
                $user,
                $amount,
                $handledResponse['transaction_id'],
                $balance_before,
                $user->wallet_balance
            );

            (new ReferralService())->handleFirstTransactionBonus($user, 'data', $amount);

            return response()->json([
                'status' => true,
                'message' => $handledResponse['message'],
                'data' => $transaction
            ]);
        }

        if ($handledResponse['pending']) {
            $balance_before = $user->wallet_balance;
            $user->wallet_balance = $balance_before - $amount_charged;
            $user->save();

            $transaction = $this->createTransaction(
                $user,
                $amount,
                $handledResponse['network_name'],
                'Pending', // Set status to 'Pending'
                $context['mobile_number'],
                $context['image'],
                $handledResponse['transaction_id'],
                $context['plan_size'],
                $handledResponse['plan_id'],
                $handledResponse['api_reference']
            );

            $this->createWalletTransaction(
                $user,
                $amount,
                $handledResponse['transaction_id'],
                $balance_before,
                $user->wallet_balance
            );

            (new ReferralService())->handleFirstTransactionBonus($user, 'data', $amount);

            return response()->json([
                'status' => true,
                'message' => $handledResponse['message'],
                'data' => $transaction
            ]);
        }


        $this->createTransaction(
            $context['user'] ?? null,
            $context['amount'],
            $context['network'],
            'Failed',
            $context['mobile_number'],
            $context['image'],
            $handledResponse['transaction_id'] ?? Str::uuid(),
            $context['plan_size']
        );

        return response()->json([
            'status' => false,
            'message' => $handledResponse['message']
        ], $this->getHttpStatusCode($handledResponse['status_code']));
    }

    protected function getHttpStatusCode(int $apiStatusCode): int
    {
        return match (true) {
            $apiStatusCode >= 400 && $apiStatusCode < 500 => 400,
            $apiStatusCode >= 500 => 503,
            default => 400
        };
    }
}
