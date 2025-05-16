<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transactions;
use App\Models\WalletTransactions;
use App\Services\GiftCardServiceFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GiftCardController extends Controller
{
    public function getCountries()
    {
        $service = GiftCardServiceFactory::getActiveService();
        return response()->json($service->getCountries());
    }

    public function getGiftCards(Request $request)
    {
        $request->validate(['country' => 'required|string']);
        $service = GiftCardServiceFactory::getActiveService();
        return response()->json($service->getGiftCards($request->country));
    }

    public function getDenominations(Request $request)
    {
        $request->validate(['operator_id' => 'required|string']);
        $service = GiftCardServiceFactory::getActiveService();
        return response()->json($service->getCardDenominations($request->operator_id));
    }

    public function purchase(Request $request)
    {
        $request->validate([
            'operator' => 'required|string',
            'product_id' => 'required|string',
            'amount' => 'required|numeric',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = $request->user();


        $total = (float) $request->amount * (int) $request->quantity;
        if ((float) $user->wallet_balance < $total) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance'
            ], 400);
        }

        if ($user->wallet_balance < $total) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Insufficient balance'
                ],
                400
            );
        }

        $service = GiftCardServiceFactory::getActiveService();
        if (!$service) {
            return response()->json([
                'status' => false,
                'message' => 'No active gift card service available'
            ], 503);
        }

        $result = $service->purchaseCard($request->all());

        if ($result['status']) {
            // Save transaction
            $transaction = new Transactions();
            $transaction->user_id = $user->id;
            $transaction->username = $user->username;
            $transaction->service = 'giftcard';
            $transaction->amount = $total;
            $transaction->status = 'Successful';
            $transaction->transaction_id = $result['transaction_id'];
            $transaction->reference = $result['reference'] ?? null;
            $transaction->epin = $result['pin']['number'] ?? null;
            $transaction->serial = $result['pin']['serial'] ?? null;
            $transaction->instructions = $result['pin']['instructions'] ?? null;
            $transaction->save();

            // Wallet update
            $before = $user->wallet_balance;
            $user->wallet_balance -= $total;
            $user->save();

            $wallet = new WalletTransactions();
            $wallet->trans_type = 'debit';
            $wallet->user = $user->username;
            $wallet->amount = $total;
            $wallet->service = 'giftcard';
            $wallet->transaction_id = $transaction->transaction_id;
            $wallet->balance_before = $before;
            $wallet->balance_after = $user->wallet_balance;
            $wallet->status = 'Successful';
            $wallet->save();

            return response()->json([
                'status' => true,
                'message' => 'Gift card purchased',
                'pin' => $result['pin'] ?? null
            ]);
        }

        return response()->json(['status' => false, 'message' => $result['message']], 400);
    }
}
