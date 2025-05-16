<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransactionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionReportController extends Controller
{
   
    public function transactionReport(Request $request){
    $validator = Validator::make($request->all(), [
        'amount' => 'required',
        'username' => 'string|required',
        'service' => 'string|required',
        'status' => 'string|required',
        'service_plan' => 'string|nullable',
        'transaction_id' => 'string|nullable',
        'phone_number' => 'string|nullable',
        'smart_card_number' => 'string|nullable',
        'meter_number' => 'string|nullable',
        'quantity' => 'string|nullable',
        'electricity_token' => 'string|nullable',
        'balance_before' => 'string|nullable',
        'balance_after' => 'string|nullable',
    ]);


    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            // 'message' => $validator->errors()
            'message' => $validator->errors()->first()
        ], 422); // 422 Unprocessable Entity
    }

                $transaction = new TransactionReport();
                $transaction->amount = $request->amount;
                $transaction->username = $request->username;
                $transaction->service = $request->service;
                $transaction->status = $request->status;
                $transaction->service_plan = $request->service_plan;
                $transaction->transaction_id = $request->transaction_id;
                $transaction->phone_number = $request->phone_number;
                $transaction->smart_card_number = $request->smart_card_number;
                $transaction->meter_number = $request->meter_number;
                $transaction->quantity = $request->quantity;
                $transaction->electricity_token = $request->electricity_token;
                $transaction->balance_before = $request->balance_before;
                $transaction->balance_after = $request->balance_after;
                $transaction->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Report sent successful',
                   
                ]);

   }
}
