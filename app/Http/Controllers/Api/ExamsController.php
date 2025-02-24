<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\WalletTransactions;


class ExamsController extends Controller
{
    

    public function buyResultChecker(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'exam' =>  'required|string',
            'amount' => 'string|required',
            'quantity' => 'string|required',
            'image' => 'string|nullable'
          
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        try {

            $exam = $request->input('exam');
            $quantity = $request->input('quantity');
            $amount = $request->input('amount');

            // if($exam != null){
            //     return response()->json([
            //         'status' => false,
            //         'message' =>'exam to uppercase: '. strtoupper($exam)
            //     ], 401);  
            // }

            $user = $request->user();
            $wallet_balance = $user->wallet_balance;

            if($wallet_balance < $amount){
                return response()->json([
                    'status' => false,
                    'message' =>'You can\'t topup due to insufficient balance N'. $wallet_balance
                ], 401);
            }

            $amount_charged = $amount;
            
            $url = 'https://www.gladtidingsdata.com/api/epin/';
            $gladAPIKey = '59d56fab621bcaa57180cd8ce6619b2dc5d99598';

            // Define the headers, including the token
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $gladAPIKey,
            ];

            $data = [
                'exam_name' =>strtoupper($exam),
                'quantity' => $quantity,
                'amount' => $amount,
            ];

            $response = Http::withHeaders($headers)->post($url, $data);

            $statusCode = $response->getStatusCode();
            
            Log::info('status code: ' . $statusCode);

            if ($statusCode >= 200 && $statusCode < 300) {
                $responseData = json_decode($response->getBody()->getContents(), true);

                if ($statusCode == 201 || $statusCode == 200) {
                    Log::info('API details'. $response);

                    if (isset($responseData['Status']) && $responseData['Status'] == 'failed') {
                        $transaction = new Transactions();
                        $transaction->amount = $amount;
                        $transaction->service_provider = strtoupper($exam);
                        $transaction->service = 'exam';
                        $transaction->status = 'Failed';
                        $transaction->image = $request->image;
                        $transaction->quantity =  $quantity;
                        $transaction->epin = $responseData['pins'];
                        $transaction->transaction_id = $responseData['id'];
                        $transaction->save();
                        Log::error('API failed with details', $responseData);
                        return response()->json([
                            'status' => false,
                            'message' => 'Transaction failed, please try again'], 401);
                    }


                    $user->wallet_balance = $wallet_balance - $amount_charged;
                    $balance_before = $user->wallet_balance;
                    $user->save();

                    $transaction = new Transactions();
                    $transaction->amount = $amount;
                    $transaction->service_provider = strtoupper($exam);
                    $transaction->service = 'exam';
                    $transaction->status = 'Successful';
                    $transaction->image = $request->image;
                    $transaction->quantity =  $quantity;
                    $transaction->epin = $responseData['pins'];
                    $transaction->transaction_id = $responseData['id'];
                    $transaction->save();

                    $walletTrans = new  WalletTransactions();
                            $walletTrans->trans_type = 'debit';
                            $walletTrans->user = $user->username;
                            $walletTrans->amount = "{$amount}";
                            $walletTrans->service = 'exam';
                            $walletTrans->status = 'Successful';
                            $walletTrans->transaction_id = $responseData['id'];
                            $walletTrans->balance_before = $balance_before;
                            $walletTrans->balance_after = $user->wallet_balance;
                            $walletTrans->save();
                    

                    return response()->json([
                        'status' => true,
                        'message' =>  $transaction,
                        'data' => $responseData
                    ]);
                }
                Log::info("Request failed: 11 " . $responseData);
                return response()->json([
                    'status' => false,
                    'message' => $responseData['error']
                ], $statusCode);
            }
            Log::info("Request failed12: " . $response);
            $service_error = $response['error'][0];
            if(Str::contains($service_error,'insufficient balance')) {
                $service_error = 'Something went wrong, please contact admin';
            }
            return response()->json([

                'status' => false,
                'message' => $service_error
            ], $statusCode);

        } catch (RequestException $e) {

            Log::error("Request failed:13 " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' =>  $e->getMessage()
            ], $statusCode);
        } catch (\Exception $e) {

            Log::error("Request failed:14 " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' =>  $e->getMessage()
            ], $statusCode);
        }
    }


}
