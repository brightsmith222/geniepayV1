<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Transactions;
use App\Models\WalletTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\MyFunctions;
use App\Services\VtpassService;

class CableTvController extends Controller
{

    public function getCablePlan($serviceID)
    {


        try {

            $vtpass = new VtpassService();
            $url = config('api.vtpass.base_url')."service-variations?serviceID=" . $serviceID;
            if ($vtpass->isVtpassEnabled()) {
                $headers = $vtpass->getHeaders();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Server call service is currently disabled.',
                ]);
            }

            $response = Http::withHeaders($headers)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                // Log::info('response data: ' . $response->body());

                if ($data['response_description'] == '000') {
                    return response()->json(
                        [
                            'status' => true,
                            'data' => $data['content'] ?? []
                        ],
                        200
                    );
                }
            } else {
                // Log::error("Error Occured: " . $data['message']);
                return response()->json([
                    'status' => false,
                    'message' => 'Could not fetch data'
                ], $response->status());
            }
        } catch (RequestException $e) {
            // Handle exceptions that occur during the HTTP request
            Log::error("Request failed: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error("An error occurred: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getCableProviders(VtpassService $vtpass)
    {

        try {


            $url = config('api.vtpass.base_url')."services?identifier=tv-subscription";
            if ($vtpass->isVtpassEnabled()) {
                $headers = $vtpass->getHeaders();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Server call service is currently disabled.',
                ]);
            }

            $response = Http::withHeaders($headers)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['response_description'] == '000') {
                    return response()->json(
                        [
                            'status' => true,
                            'data' => $data['content'] ?? []
                        ],
                        200
                    );
                }
            } else {
                // Log::error("Error Occured: " . $data['message']);
                return response()->json([
                    'status' => false,
                    'message' => 'Could not fetch data'
                ], $response->status());
            }
        } catch (RequestException $e) {
            // Handle exceptions that occur during the HTTP request
            Log::error("Request failed: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error("An error occurred: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }




    public function verifySmartCard(Request $request, VtpassService $vtpass)
    {
        //$webconfig = config('webconfig'); // Assuming you have your web configurations in config/webconfig.php

        $validator = Validator::make($request->all(), [
            'billersCode' => 'required|string',
            'serviceID' => 'string|required',
            'planName' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                // 'message' => $validator->errors()
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        if ($request->isMethod('post')) {

            try {
                $billersCode = $request->input('billersCode');
                $serviceId = $request->input('serviceID');
                $planName = $request->input('planName');

                $url = config('api.vtpass.base_url')."merchant-verify";

                $headers = $vtpass->getHeaders();

                $data = [
                    'billersCode' => $billersCode,
                    'serviceID' => $serviceId,
                    'type' => $planName
                ];

                $response = Http::withHeaders($headers)->post($url, $data);
                Log::info('API details' . $response);

                if ($response->successful()) {
                    $data = $response->json();
                    if ($data['code'] != '000' && array_key_exists('response_description', $data)) {
                        return response()->json([
                            'status' => false,
                            'message' => $data['response_description']
                        ], 401);
                    }
                    if ($data['code'] == '000' && array_key_exists('error', $data['content'])) {
                        return response()->json([
                            'status' => false,
                            'message' => $data['content']['error']
                        ], 401);
                    }

                    $customerName = $data['content']['Customer_Name'];
                    $serviceType = 'Smart Card/IUC';

                    if ($planName == 'prepaid' || $planName == 'postpaid') {
                        $serviceType = 'Meter';
                    }

                    $message = "You are about to purchase " . $serviceId . ", " . $planName . " for " . "( Customer Name: " . $customerName . ", Customer " . $serviceType . " Number: " . $billersCode . ")";
                    return response()->json([
                        'status' => true,
                        'message' => $message

                    ]);
                } else {
                    $data = $response->json();
                    Log::error("Error Occured: " . $data['message']);
                    return response()->json([
                        'status' => false,
                        'message' => $data['message']
                    ], $response->status());
                }
            } catch (RequestException $e) {
                // Handle exceptions that occur during the HTTP request
                Log::error("Request failed: " . $e->getMessage());
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage()
                ], 400);
            } catch (\Exception $e) {
                // Handle any other exceptions
                Log::error("An error occurred: " . $e->getMessage());
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json(['message' => 'Invalid request method'], 405);
    }

    public function cableSubscription(Request $request, VtpassService $vtpass)
    {


        $validator = Validator::make($request->all(), [
            'smart_card' => 'required|string',
            'cable_Id' => 'required|string',
            'cable_plan_Id' => 'required|string',
            'cable_plan' => 'string|required',
            'amount' => 'string|required',
            'image' => 'string|nullable'

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                // 'message' => $validator->errors()
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        try {
            $smartCard = $request->input('smart_card');
            $cableId = $request->input('cable_Id');
            $cablePlanId = $request->input('cable_plan_Id');
            $cablePlan = $request->input('cable_plan');
            $amount = $request->input('amount');

            $requestId = MyFunctions::generateRequestId();

            $user = $request->user();

            $wallet_balance = $user->wallet_balance;

            if ($wallet_balance < $amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can\'t make transaction due to insufficient balance N ' . $wallet_balance
                ], 401);
            }

            $serviceType = 'Smart Card/IUC';
            $service = 'cable';

            if (strtolower($cablePlanId) == 'prepaid' || strtolower($cablePlanId) == 'postpaid') {
                $serviceType = 'Meter';
                $service = 'electricity';
            }

            $data = [
                'request_id' => $requestId,
                'billersCode' => $smartCard,
                'serviceID' => $cableId,
                'variation_code' => $cablePlanId,
                'subscription_type' => 'change',
                'amount' => $amount,
                'phone' => $user->phone_number

            ];

            $url = config('api.vtpass.base_url')."pay";

            if ($vtpass->isVtpassEnabled()) {
                $headers = $vtpass->getHeaders();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Server call service is currently disabled.',
                ]);
            }

            $response = Http::withHeaders($headers)->post($url, $data);

            Log::info('API  payment details' . $response);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['code'] != '000' && array_key_exists('response_description', $data)) {
                    return response()->json([
                        'status' => false,
                        'message' => $data['response_description']
                    ], 401);
                }
                if ($data['code'] == '000' && array_key_exists('error', $data['content'])) {
                    return response()->json([
                        'status' => false,
                        'message' => $data['content']['error']
                    ], 401);
                }

                if ($data['code'] == '000' || $data['code'] == '099') {

                    $baseUrl = config('api.vtpass.base_url');
                    $url = $baseUrl."requery";
                    $payload = [
                        'request_id' => $data['requestId']
                    ];
                    $response = Http::withHeaders($headers)->post($url, $payload);
                    Log::info('API  requery details' . $response);
                    if ($response->successful()) {
                        $data = $response->json();
                        if ($data['code'] != '000' && $data['code'] != '099') {
                            return response()->json([
                                'status' => false,
                                'message' => 'Transaction could not be processed, please try again'
                            ], 401);
                        }

                        if ($data['code'] == '000' || $data['code'] == '099') {
                            $trans_status = $data['content']['transactions']['status'];
                            # if trans_status == 'delivered' or trans_status == 'pending' :
                            $status = $trans_status == 'delivered' ? 'Successful' : 'Processing';

                            // $user->wallet_balance -=  $amount_charged;
                            $balance_before = $user->wallet_balance;
                            $user->wallet_balance -=  $amount;
                            $user->save();

                            $transaction = new Transactions();
                            $transaction->amount = $amount;
                            $transaction->username = $user->username;
                            $transaction->status = $status;
                            $transaction->service_provider = strtoupper($cableId);
                            $transaction->service = $service;
                            $transaction->smart_card_number = $smartCard;
                            $transaction->service_plan = $cablePlan;
                            $transaction->image = $request->image;
                            // $transaction->quantity =  $quantity;
                            // $transaction->epin = $responseData['pins'];
                            $transaction->transaction_id = $data['requestId'];
                            $transaction->electricity_token = $data['token'] ?? $data['purchased_code'] ?? null;
                            $transaction->save();

                            $walletTrans = new  WalletTransactions();
                            $walletTrans->trans_type = 'debit';
                            $walletTrans->user = $user->username;
                            $walletTrans->amount = $amount;
                            $walletTrans->service = $service;
                            $walletTrans->transaction_id = $data['requestId'];
                            $walletTrans->balance_before = $balance_before;
                            $walletTrans->balance_after = $user->wallet_balance;
                            $walletTrans->status = $status;
                            $walletTrans->save();

                            return response()->json([
                                'status' => true,
                                'message' => '',
                                'data' => $transaction
                            ], 200);
                        }
                    }

                    // return response()->json($data, 200);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => $data['content']['error']
                    ]);
                }
            } else {
                $data = $response->json();
                Log::error("Error Occured: " . $data['message']);
                return response()->json(['message' => $data['message']], $response->status());
            }
        } catch (RequestException $e) {
            // Handle exceptions that occur during the HTTP request
            Log::error("Request failed: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error("An error occurred: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
