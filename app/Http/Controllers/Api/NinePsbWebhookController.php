<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\VirtualAccount;
use App\Models\User;
use App\Models\Webhook9psbSession;
use App\Services\NinePsbService;
use App\Models\WalletTransactions;
use App\Services\PercentageService;

class NinePsbWebhookController extends Controller
{
    protected $psb;

    public function __construct(NinePsbService $psb)
    {
        $this->psb = $psb;
    }

    public function handle(Request $request, PercentageService $percentageService)
    {
        $payload = $request->all();
        Log::info('9PSB Webhook Received', $payload);
        $username = config('api.9psb.webhook_username');
        $password = config('api.9psb.webhook_password');

        // Extract Basic Auth from header
        $providedUsername = $request->getUser();
        $providedPassword = $request->getPassword();

        if ($providedUsername !== $username || $providedPassword !== $password) {
            Log::warning('Unauthorized webhook access attempt.', [
                'username' => $providedUsername,
                'ip' => $request->ip()
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }


        try {
            // Step 1: Extract data
            $accountNumber = data_get($payload, 'customer.account.number');
            $amount = number_format(data_get($payload, 'order.amount'), 2, '.', '');
            $senderAccountNumber = data_get($payload, 'customer.account.senderaccountnumber');
            $senderBankCode = data_get($payload, 'customer.account.senderbankcode');
            $receivedHash = strtoupper(data_get($payload, 'Hash'));
            $sessionId = data_get($payload, 'transaction.sessionid');

            if (!$accountNumber || !$amount || !$senderAccountNumber || !$senderBankCode || !$receivedHash || !$sessionId) {
                Log::warning('Webhook missing fields');
                return response()->json(['message' => 'Invalid data'], 400);
            }

            // Step 2: Hash validation
            $password = config('api.9psb.webhook_password');
            $stringToHash = $password . $senderAccountNumber . $senderBankCode . $accountNumber . $amount;
            $expectedHash = strtoupper(hash('sha512', $stringToHash));

            if ($receivedHash !== $expectedHash) {
                Log::warning('Invalid hash signature', [
                    'expected' => $expectedHash,
                    'received' => $receivedHash
                ]);
                return response()->json(['message' => 'Invalid message signature'], 403);
            }

            // Step 3: Lookup virtual account and user
            $vAccount = VirtualAccount::where('account_number', $accountNumber)->first();
            if (!$vAccount) {
                return response()->json(['message' => 'Account not found'], 404);
            }

            $user = User::find($vAccount->user_id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // ✅ Step 4: Prevent duplicate session ID
            if (Webhook9psbSession::where('session_id', $sessionId)->exists()) {
                Log::info("Duplicate session: $sessionId");
                return response()->json([
                    'message' => 'Duplicate transaction',
                    'code' => '00'
                ], 200);
            }

            // ✅ Step 5: Confirm payment with 9PSB
            $confirmation = $this->psb->confirmPayment([
                'sessionid' => $sessionId,
                'accountnumber' => $accountNumber,
                'amount' => (float)$amount
            ]);

            $confirmedTxn = $confirmation['transactions'][0] ?? null;
            $confirmedAmount = data_get($confirmedTxn, 'order.amount');

            if ((float)$confirmedAmount != (float)$amount) {
                Log::warning("Amount mismatch", ['confirmed' => $confirmedAmount, 'webhook' => $amount]);
                return response()->json(['message' => 'Amount mismatch'], 422);
            }

            // ✅ Step 6: Record transaction
            Webhook9psbSession::create([
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'account_number' => $accountNumber,
                'amount' => $amount,
                'status' => 'completed',
                'raw_response' => json_encode($payload),
            ]);

            // ✅ Step 7: Credit user
            $balance_before = $user->wallet_balance;
            $amount = $percentageService->virtualCharge($amount);
            $credited = $user->increment('wallet_balance', $amount);

            if ($credited) {
                $walletTrans = new  WalletTransactions();
                $walletTrans->trans_type = 'credit';
                $walletTrans->user = $user->username;
                $walletTrans->amount = $amount;
                $walletTrans->service = 'Wallet Funded';
                $walletTrans->status = 'Successful';
                $walletTrans->transaction_id = $sessionId;
                $walletTrans->balance_before = $balance_before;
                $walletTrans->balance_after = $user->wallet_balance;
                $walletTrans->save();
            }

            return response()->json([
                'message' => 'Wallet credited successfully',
                'data' => ['Successful'],
                'code' => '00'
            ], 200);
        } catch (\Throwable $e) {
            Log::error('9PSB Webhook Error: ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
