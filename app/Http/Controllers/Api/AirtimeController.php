<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\AirtimeTopupPercentage;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\WalletTransactions;



class AirtimeController extends Controller
{


    public function buyAirtime(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'network' => 'required|integer',
            'amount' => 'required',
            'image' => 'string|nullable',
            'mobile_number' => [
                'required',
                'string',
                'min:11',
                'max:11'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                // 'message' => $validator->errors()
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        try {

            $network = $request->input('network');
            $mobile_number = $request->input('mobile_number');
            $amount = $request->input('amount');

            $user = $request->user();
            $wallet_balance = $user->wallet_balance;

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




            $mtn_numbers = ['0803', '0703', '0903', '0806', '0706', '0813', '0810', '0814', '0816', '0906', '0913', '0801', '0707'];
            $glo_numbers = ['0805', '0705', '0905', '0807', '0815', '0811', '0905', '0801'];
            $etisalat_numbers = ['0809', '0909', '0817', '0818'];
            $airtel_numbers = ['0802', '0902', '0701', '0808', '0708', '0812', '0904', '0901'];

            // $mobile_number = $request->input('mobile_number');
            // $select_network = $request->input('select_network');

            $number_network_type = substr($mobile_number, 0, 4);

            // $number_error_txt = "This is not  {} number, please enter the correct number";

            switch ($network) {
                case '1':
                    if (!in_array($number_network_type, $mtn_numbers)) {
                        return response()->json([
                            'status' => false,
                            'message' => "This is not  MTN number, please enter the correct number" //sprintf($number_error_txt, 'MTN')
                        ], 401);
                    }
                    break;

                case '2':
                    if (!in_array($number_network_type, $glo_numbers)) {
                        return response()->json([
                            'status' => false,
                            'message' => "This is not GLO number, please enter the correct number" //sprintf($number_error_txt, 'GLO')
                        ], 401);
                    }
                    break;

                case '3':
                    if (!in_array($number_network_type, $airtel_numbers)) {
                        return response()->json([
                            'status' => false,
                            'message' => "This is not  AIRTEL number, please enter the correct number" //sprintf($number_error_txt, 'AIRTEL')
                        ], 401);
                    }
                    break;

                case '6':
                    if (!in_array($number_network_type, $etisalat_numbers)) {
                        return response()->json([
                            'status' => false,
                            'message' => "This is not 9MOBILE number, please enter the correct number" //sprintf($number_error_txt, '9MOBILE')
                        ], 401);
                    }
                    break;

                default:
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid network selected'
                    ], 400);
            }

            $networkPercent = AirtimeTopupPercentage::where('network_id', '=', $network)->first();
            $amount_charged = $amount - (100 - $networkPercent->network_percentage) / 100;


            $url = 'https://www.gladtidingsdata.com/api/topup/';
            $gladAPIKey = '59d56fab621bcaa57180cd8ce6619b2dc5d99598';

            // Define the headers, including the token
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $gladAPIKey,
            ];

            $data = [
                'network' => $network,
                'mobile_number' => $mobile_number,
                'amount' => $amount,
                "airtime_type" => "VTU",
                "Ported_number" => true

            ];


            // if ($network != null) {
            //     return response()->json([
            //         'status' => true,
            //         'message' => 'Top-up successful',
            //     ]);
            // }



            $response = Http::withHeaders($headers)->post($url, $data);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $responseData = json_decode($response->getBody()->getContents(), true);

                if ($statusCode == 201 || $statusCode == 200) {

                    if (isset($responseData['Status']) && $responseData['Status'] == 'failed') {
                        $transaction = new Transactions();
                        $transaction->amount = "{$amount}";
                        $transaction->service_provider = $responseData['plan_network'];
                        $transaction->status = 'Failed';
                        $transaction->service = 'airtime';
                        $transaction->image = $request->image;
                        $transaction->phone_number = $mobile_number;
                        $transaction->transaction_id = $responseData['ident'];
                        $transaction->save();
                        return response()->json([
                            'status' => false,
                            'message' => 'Transaction failed, please try again'
                        ], 401);
                    }



                    $user->wallet_balance = $wallet_balance - $amount_charged;
                    $balance_before = $user->wallet_balance;
                    $user->save();

                    $transaction = new Transactions();
                    $transaction->amount = "{$amount}";
                    $transaction->service_provider = $responseData['plan_network'];
                    $transaction->status = 'Successful';
                    $transaction->service = 'airtime';
                    $transaction->image = $request->image;
                    $transaction->phone_number = $mobile_number;
                    $transaction->transaction_id = $responseData['ident'];
                    $transaction->save();

                    $walletTrans = new  WalletTransactions();
                            $walletTrans->trans_type = 'debit';
                            $walletTrans->user = $user->username;
                            $walletTrans->amount = "{$amount}";
                            $walletTrans->service = 'airtime';
                            $walletTrans->status = 'Successful';
                            $walletTrans->transaction_id = $responseData['ident'];
                            $walletTrans->balance_before = $balance_before;
                            $walletTrans->balance_after = $user->wallet_balance;
                            $walletTrans->save();

                    return response()->json([
                        'status' => true,
                        'message' => 'Top-up successful',
                        'data' => $transaction
                    ]);
                }
                return response()->json([
                    'status' => false,
                    'message' => $responseData['error']
                ], $statusCode);
            }
            $service_error = $response['error'][0];
            if(Str::contains($service_error,'insufficient balance')) {
                $service_error = 'Something went wrong, please contact admin';
            }
            return response()->json([

                'status' => false,
                'message' => $service_error
            ], $statusCode);

        } catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        } catch (\Exception $e) {
            // Handle any other exceptions
            // echo "An error occurred: " . $e->getMessage();
            // Log::error("Request failed:14 " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }


}
