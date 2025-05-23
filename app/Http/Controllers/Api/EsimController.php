<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transactions;
use App\Models\WalletTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\EsimServiceFactory;
use App\Services\ReferralService;
use App\Services\PercentageService;

class EsimController extends Controller
{
    public function getCountries()
    {
        $service = EsimServiceFactory::getActiveService();
        return response()->json($service->getCountries());
    }

    public function getPlans(Request $request)
    {
        $request->validate(['country' => 'required|string']);
        $service = EsimServiceFactory::getActiveService();
        return response()->json($service->getPlans($request->country));

        $request->validate(['country' => 'required|string']);
        $service = EsimServiceFactory::getActiveService();
        return response()->json($service->getGiftCards($request->country));
    }

    public function purchase(Request $request, PercentageService $percentageService)
    {
        $request->validate([
            'operator' => 'required|string',
            'product_id' => 'required|string',
            'amount' => 'required|numeric',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = $request->user();
        $total = $request->amount * $request->quantity;

        if ($user->wallet_balance < $total) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance'], 400);
        }

        $service = EsimServiceFactory::getActiveService();
        $amount_charged = $percentageService->calculateDiscountedAmount($request->operator, $total);

        $result = $service->purchaseEsim($request->all());

        if ($result['status']) {
            $transaction = new Transactions();
            $transaction->user_id = $user->id;
            $transaction->username = $user->username;
            $transaction->service = 'esim';
            $transaction->amount = $total;
            $transaction->status = 'Successful';
            $transaction->transaction_id = $result['transaction_id'];
            $transaction->reference = $result['reference'] ?? null;
            $transaction->epin = $result['pin']['number'] ?? null;
            $transaction->serial = $result['pin']['serial'] ?? null;
            $transaction->instructions = $result['pin']['instructions'] ?? null;
            $transaction->save();

            $before = $user->wallet_balance;
            $user->wallet_balance -= $amount_charged;
            $user->save();

            $wallet = new WalletTransactions();
            $wallet->trans_type = 'debit';
            $wallet->user = $user->username;
            $wallet->amount = $total;
            $wallet->service = 'esim';
            $wallet->transaction_id = $transaction->transaction_id;
            $wallet->balance_before = $before;
            $wallet->balance_after = $user->wallet_balance;
            $wallet->status = 'Successful';
            $wallet->save();

            (new ReferralService())->handleFirstTransactionBonus($user, 'esim', $total);

            return response()->json([
                'status' => true,
                'message' => 'eSIM purchased successfully',
                'pin' => $result['pin'] ?? null,
                'data' => $transaction
            ]);
        }

        return response()->json(['status' => false, 'message' => $result['message']], 400);
    }
}
