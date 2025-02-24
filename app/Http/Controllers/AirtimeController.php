<?php

namespace App\Http\Controllers;

use App\Models\AirtimeTopupPercentage;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AirtimeController extends Controller
{

    public function buyAirtime(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'network' =>  'required|string',
            'amount' => 'string|required',
            'mobile_number' => [
                'required',
                'string',
                'min:11',
                'max:11'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                // 'message' => $validator->errors()
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        try {

            $network = $request->input('network');
            $mobile_number = $request->input('mobile_number');
            $amount = $request->input('amount');


            $mtn_numbers = ['0803', '0703', '0903', '0806', '0706', '0813', '0810', '0814', '0816', '0906', '0913', '0801', '0707'];
            $glo_numbers = ['0805', '0705', '0905', '0807', '0815', '0811', '0905', '0801'];
            $etisalat_numbers = ['0809', '0909', '0817', '0818'];
            $airtel_numbers = ['0802', '0902', '0701', '0808', '0708', '0812', '0904', '0901'];

            // $mobile_number = $request->input('mobile_number');
            // $select_network = $request->input('select_network');

            $number_network_type = substr($mobile_number, 0, 4);

            $number_error_txt = "This is not  {} number, please enter the correct number";

            switch ($network) {
                case '1':
                    if (!in_array($number_network_type, $mtn_numbers)) {
                        return response()->json(['error' => sprintf($number_error_txt, 'MTN')], 401);
                    }
                    break;

                case '2':
                    if (!in_array($number_network_type, $glo_numbers)) {
                        return response()->json(['error' => sprintf($number_error_txt, 'GLO')], 401);
                    }
                    break;

                case '3':
                    if (!in_array($number_network_type, $airtel_numbers)) {
                        return response()->json(['error' => sprintf($number_error_txt, 'AIRTEL')], 401);
                    }
                    break;

                case '6':
                    if (!in_array($number_network_type, $etisalat_numbers)) {
                        return response()->json(['error' => sprintf($number_error_txt, '9MOBILE')], 401);
                    }
                    break;

                default:
                    return response()->json(['error' => 'Invalid network selected'], 400);
            }

            $networkPercent = AirtimeTopupPercentage::where('network_id', '=', $network)->first();
            $amount_charged = $amount - (100 - $networkPercent->network_percentage)/100;

            $url = 'https://www.gladtidingsdata.com/api/airtime/';
            $gladAPIKey = 'a1239eb69326d9705ffcbb47f7f1bd439e2190d1';

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



            $response = Http::withHeaders($headers)->post($url, $data);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $responseData = json_decode($response->getBody()->getContents(), true);

                if ($statusCode == 201 || $statusCode == 200) {
                    Log::info('API details', $responseData);

                    if (isset($responseData['Status']) && $responseData['Status'] == 'failed') {
                        $transaction = new Transactions();
                        $transaction->amount = $amount;
                        $transaction->service_provider = 'Network Name';
                        $transaction->status = 'Failed';
                        $transaction->image = null;
                        $transaction->phone_number =  $mobile_number;
                        $transaction->transaction_id = $responseData['ident'];
                        $transaction->save();
                        Log::error('API failed with details', $responseData);
                        return response()->json(['error' => 'Transaction failed, please try again'], 401);
                    }

                    // Assuming you have a user model and authentication setup
                    $user = $request->user();
                    $wallet_balance = $user->account_balance;
                     // You might need to adjust this based on your logic

                    $user->account_balance = $wallet_balance - $amount_charged;
                    $user->save();

                    $transaction = new Transactions();
                    $transaction->amount = $amount;
                    $transaction->service_provider = 'Network Name';
                    $transaction->status = 'Successful';
                    $transaction->image = null;
                    $transaction->phone_number =  $mobile_number;
                    $transaction->transaction_id = $responseData['ident'];
                    $transaction->save();

                    return response()->json([
                        'success' => true,
                        'message' => 'Top-up successful',
                        'data' => $responseData
                    ]);
                }
                Log::info("Request failed: " . $responseData);
                return response()->json([
                    'success' => false,
                    'error' => 'Unexpected response, please try again!'
                ], $statusCode);
            }
            Log::info("Request failed: " . $response);
            return response()->json([
                'success' => false,
                'error' => 'Unexpected response, please try again!'
            ], $statusCode);

            // if ($response->successful()) {
            //     $responseData = $response->json();
            //     echo "API data: ";
            //     return $responseData;
            // } else {
            //     // Handle unsuccessful response
            //     echo "Error: " . $response->status();
            //     echo "Response: " . $response->body();
            // }
        } catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            Log::error("Request failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' =>  $e->getMessage()
            ], $statusCode);
        } catch (\Exception $e) {
            // Handle any other exceptions
            // echo "An error occurred: " . $e->getMessage();
            Log::error("Request failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' =>  $e->getMessage()
            ], $statusCode);
        }
    }
}
