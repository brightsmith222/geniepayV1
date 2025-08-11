<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransactionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Transactions;

class TransactionReportController extends Controller
{
    /**
     * Report a transaction.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
public function transactionReport(Request $request)
{
    $validator = Validator::make($request->all(), [
        'transaction_id' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    $user = $request->user();

    // Find the transaction for this user
    $transaction = Transactions::where('transaction_id', $request->transaction_id)
        ->where('user_id', $user->id)
        ->first();

    if (!$transaction) {
        return response()->json([
            'status' => false,
            'message' => 'Transaction not found for this user.'
        ], 404);
    }

    // Check if this transaction has already been reported by this user
    $alreadyReported = TransactionReport::where('transaction_id', $transaction->transaction_id)
        ->where('user_id', $user->id)
        ->exists();

    if ($alreadyReported) {
        return response()->json([
            'status' => false,
            'message' => 'This transaction has already been reported.'
        ], 409); // 409 Conflict
    }

    // Save the transaction details to TransactionReport
    $report = new TransactionReport();
    $report->amount = $transaction->amount;
    $report->user_id = $user->id; 
    $report->username = $transaction->username;
    $report->service = $transaction->service ?? null;
    $report->status = 'Reported';
    $report->service_plan = $transaction->service_plan ?? null;
    $report->transaction_id = $transaction->transaction_id;
    $report->phone_number = $transaction->phone_number ?? null;
    $report->smart_card_number = $transaction->smart_card_number ?? null;
    $report->meter_number = $transaction->meter_number ?? null;
    $report->quantity = $transaction->quantity ?? null;
    $report->electricity_token = $transaction->electricity_token ?? null;
    $report->amount = $transaction->amount ?? null;
    $report->quantity = $transaction->quantity ?? null;
    $report->epin = $transaction->epin ?? null;
    $report->serial = $transaction->serial ?? null;
    $report->instructions = $transaction->instructions ?? null;
    $report->image = $transaction->image ?? null;
    $report->which_api = $transaction->which_api ?? null;
    $report->save();

    return response()->json([
        'status' => true,
        'message' => 'Report sent successfully',
    ]);
}

}
