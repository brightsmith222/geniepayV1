<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Transactions;
use App\Models\WalletTransactions;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Log;
use App\Services\VtpassService;
use App\MyFunctions;
use Illuminate\Support\Facades\Validator;
use App\Services\PercentageService;
use App\Services\BeneficiaryService;
use App\Services\PinService;

class SpectranetController extends Controller
{
    public function getSpectranetPlans(PercentageService $percentageService)
    {
        $vtpass = new VtpassService();
        $url = config('api.vtpass.base_url') . "service-variations?serviceID=spectranet";

        if (!$vtpass->isVtpassEnabled()) {
            return response()->json([
                'status' => false,
                'message' => 'Spectranet service is currently disabled.',
            ]);
        }

        $headers = $vtpass->getHeaders();

        $response = Http::withHeaders($headers)
            ->withoutVerifying()
            ->get($url);

        $data = $response->json();
        Log::info('Spectranet Plans Response', ['response' => $data]);

        $plans = collect($data['content']['variations'] ?? [])->map(function ($plan) use ($percentageService) {
            $plan['amount'] = $percentageService->calculateSpectranetDiscountedAmount((float) $plan['variation_amount']);
            return [
                'variation_code' => $plan['variation_code'],
                'plan' => $plan['name'],
                'fixedPrice' => $plan['fixedPrice'],
                'amount' => $plan['amount'],
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $plans
        ]);
    }

    public function purchaseSpectranetData(Request $request, PinService $pinService)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string',
            'variation_code' => 'required|string',
            'amount' => 'required|numeric',
            'beneficiary' => 'required|boolean',
        ]);

        $customer_id = $request->input('customer_id');
        $variation_code = $request->input('variation_code');
        $amount = $request->input('amount');
        $beneficiary = $request->input('beneficiary', false);
        $type = 'spectranet';
        $provider = $request->input('provider', 2);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $pin = $request->input('pin');
        $user = $request->user();


        if (!$pinService->checkPin($user, $pin)) {
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
                    'message' => 'Spectranet service is currently disabled.'
                ]);
            }

            $headers = $vtpass->getHeaders();
            $transactionId = MyFunctions::generateRequestId();
            $payUrl = config('api.vtpass.base_url') . "pay";
            $payload = [
                'request_id'     => $transactionId,
                'serviceID'      => 'spectranet',
                'billersCode'    => $customer_id,
                'variation_code' => $variation_code,
                'phone'          => $customer_id,
                'quantity'          => 1,
            ];

            $payResponse = Http::withHeaders($headers)
                ->withoutVerifying()
                ->post($payUrl, $payload);

            if (!$payResponse->ok()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Server error. Contact support.',
                    'http_code' => $payResponse->status(),
                    'body' => $payResponse->body(),
                ], 502);
            }

            $payData = $payResponse->json();
            $tx = $payData['content']['transactions'] ?? [];

            // Check transaction status
            $vtpassStatus = strtolower($tx['status'] ?? 'failed');
            $status = match ($vtpassStatus) {
                'delivered' => 'Successful',
                'pending'   => 'Pending',
                default     => 'Failed',
            };

            $balance_before = $wallet_balance;
            if (in_array($status, ['Successful', 'Pending'])) {
                $wallet_balance = $wallet_balance - $amount;
                $user->wallet_balance = $wallet_balance;
                $user->save();

                $walletTrans = new  WalletTransactions();
                $walletTrans->trans_type = 'debit';
                $walletTrans->user = $user->username;
                $walletTrans->amount = $request->amount;
                $walletTrans->service = $type;
                $walletTrans->transaction_id = $transactionId;
                $walletTrans->balance_before = $balance_before;
                $walletTrans->balance_after = $wallet_balance;
                $walletTrans->status = $status;
                $walletTrans->save();

                if ($beneficiary ?? false) {
                    try {
                        if (!empty($customer_id)) {
                            (new BeneficiaryService())->save([
                                'type'       => $type,
                                'identifier' => $customer_id,
                                'provider'   => $provider,
                            ], $user);
                        } else {
                            Log::error('Beneficiary customer ID is missing');
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to save beneficiary', ['error' => $e->getMessage()]);
                    }
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
            $transaction->phone_number       = $tx['unique_element'] ?? $customer_id;
            $transaction->service_plan       = $request->plan ?? null;
            $transaction->image              = $request->image ?? null;
            $transaction->transaction_id     = $transactionId;
            $transaction->quantity           = $tx['quantity'] ?? 1;
            $transaction->commission         = $tx['commission'] ?? '0';
            $transaction->which_api          = 'vtpass';
            $transaction->save();

            if ($status === 'Successful') {
                (new ReferralService())->handleFirstTransactionBonus($user, 'spectranet', $request->amount);
            }

            return response()->json([
                'status'  => $status !== 'Failed',
                'message' => $payData['response_description'] ?? 'Unknown result',
                'data'    => $transaction,
            ], $status === 'Successful' ? 200 : ($status === 'Pending' ? 202 : 500));
        } catch (\Exception $e) {
            Log::error('Spectranet Purchase Error', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred during the purchase process',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
