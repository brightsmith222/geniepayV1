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
use App\MyFunctions;
use App\Services\BeneficiaryService;
use App\Services\PinService;


class AirtimeController extends Controller
{
    public function buyAirtime(Request $request, PercentageService $airtimePercentageService , PinService $pinService)
    {
        $validator = Validator::make($request->all(), [
            'network' => 'required|integer',
            'amount' => 'required',
            'image' => 'string|nullable',
            'beneficiary' => 'boolean|nullable',
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
            $beneficiary = $request->input('beneficiary', false);
            $pin = $request->input('pin');
            $user = $request->user();
            

            if (!$pinService->checkPin($user, $pin)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid transaction pin.'
                ], 403);
            }

            $wallet_balance = $user->wallet_balance;
            // Validate wallet balance and amount
            if ($wallet_balance < $amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance ₦' . number_format($wallet_balance)
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
                'beneficiary' => $beneficiary,
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
        $type = "airtime";


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
                $userReference,
                $handledResponse['which_api'] ?? null,
                $context['network'] ?? null,
            );

            $this->createWalletTransaction(
                $user,
                $amount,
                $handledResponse['transaction_id'],
                $balance_before,
                $user->wallet_balance
            );

            if ($context['beneficiary'] ?? false) {
                try {
                    if (!empty($context['mobile_number'])) {

                        (new BeneficiaryService())->save([
                            'type'       => $type,
                            'identifier' => $context['mobile_number'],
                            'provider'   => $context['network'],
                        ], $user);
                    } else {
                        Log::error('Beneficiary mobile number is missing');
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to save beneficiary', ['error' => $e->getMessage()]);
                }
            }


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
                $userReference,
                $handledResponse['which_api'] ?? null,
                $context['network'] ?? null,
            );


            $this->createWalletTransaction(
                $user,
                $amount,
                $handledResponse['transaction_id'],
                $balance_before,
                $user->wallet_balance
            );

            (new ReferralService())->handleFirstTransactionBonus($user, 'airtime', $amount);

            if ($context['beneficiary'] ?? false) {
                try {
                    if (!empty($context['mobile_number'])) {
                        (new BeneficiaryService())->save([
                            'type'       => $type,
                            'identifier' => $context['mobile_number'],
                            'provider'   => $context['network'],
                        ], $user);
                    } else {
                        Log::error('Beneficiary mobile number is missing');
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to save beneficiary', ['error' => $e->getMessage()]);
                }
            }

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
            $this->mapNetworkToName($context['network']),
            'Failed',
            $context['mobile_number'],
            $context['image'],
            $handledResponse['transaction_id'] ?? MyFunctions::generateRequestId(),
            $context['which_api'] ?? null,
            $context['network'] ?? null


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


    protected function createTransaction($user, $amount, $network, $status, $mobile_number, $image, $transaction_id, $userReference = null, $which_api = null, $provider_id = null)
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
        $transaction->transaction_id = (string) $transaction_id;
        $transaction->reference = $userReference;
        $transaction->which_api = $which_api;
        $transaction->provider_id = $provider_id;
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
        $walletTrans->transaction_id = (string) $transaction_id;
        $walletTrans->balance_before = $balance_before;
        $walletTrans->balance_after = $balance_after;
        $walletTrans->save();

        return $walletTrans;
    }

    protected function mapNetworkToName(int $networkId): string
    {
        return match ($networkId) {
            1 => 'Nigeria MTN',
            2 => 'Nigeria GLO',
            3 => 'Nigeria Airtel',
            6 => 'Nigeria 9Mobile',
            default => 'Unknown',
        };
    }
}
