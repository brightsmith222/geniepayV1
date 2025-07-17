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
use App\Services\ReferralService;
use App\Services\BeneficiaryService;
use Illuminate\Support\Facades\Cache;
use App\Services\PinService;

class CableTvController extends Controller
{

    public function getCablePlan($serviceID)
    {
        try {
            $cacheKey = "cable_plan_{$serviceID}";
            $cacheMinutes = 60 * 24; // 24 hours

            $responseData = Cache::remember($cacheKey, $cacheMinutes * 60, function () use ($serviceID) {
                $vtpass = new VtpassService();
                $url = config('api.vtpass.base_url') . "service-variations?serviceID=" . $serviceID;

                if (!$vtpass->isVtpassEnabled()) {
                    return [
                        'status' => false,
                        'message' => 'Server call service is currently disabled.',
                    ];
                }

                $headers = $vtpass->getHeaders();
                $response = Http::withoutVerifying()->withHeaders($headers)->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    if ($data['response_description'] == '000') {
                        $content = [
                            'ServiceName' => $data['content']['ServiceName'] ?? $data['content']['service_name'] ?? ucfirst($serviceID) . ' Subscription',
                            'serviceID' => $serviceID,
                            'convinience_fee' => $data['content']['convinience_fee'] ?? 'N0',
                            'variations' => $data['content']['variations'] ?? $data['content']['varations'] ?? [],
                        ];

                        // Clean up the variations array
                        $content['variations'] = array_map(function ($variation) {
                            return [
                                'variation_code' => $variation['variation_code'],
                                'name' => $variation['name'],
                                'variation_amount' => $variation['variation_amount'],
                                'fixedPrice' => $variation['fixedPrice'] ?? 'Yes'
                            ];
                        }, $content['variations']);

                        return [
                            'status' => true,
                            'data' => [
                                'response_description' => '000',
                                'content' => $content
                            ]
                        ];
                    } else {
                        return [
                            'status' => false,
                            'message' => $data['response_description'] ?? 'Could not fetch data'
                        ];
                    }
                } else {
                    return [
                        'status' => false,
                        'message' => 'Could not fetch data'
                    ];
                }
            });

            if ($responseData['status']) {
                return response()->json($responseData, 200);
            } else {
                return response()->json($responseData, 400);
            }
        } catch (\Exception $e) {
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


            $url = config('api.vtpass.base_url') . "services?identifier=tv-subscription";
            if ($vtpass->isVtpassEnabled()) {
                $headers = $vtpass->getHeaders();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Server call service is currently disabled.',
                ]);
            }

            $response = Http::withoutVerifying()->withHeaders($headers)->get($url);

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
    $validator = Validator::make($request->all(), [
        'billersCode' => 'required|string',
        'serviceID' => 'string|required',
        'planName' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    if ($request->isMethod('post')) {
        try {
            $billersCode = $request->input('billersCode');
            $serviceId = $request->input('serviceID');
            $planName = $request->input('planName');

            $url = config('api.vtpass.base_url') . "merchant-verify";
            $headers = $vtpass->getHeaders();

            $data = [
                'billersCode' => $billersCode,
                'serviceID' => $serviceId,
                'type' => $planName
            ];

            $response = Http::withoutVerifying()->withHeaders($headers)->post($url, $data);

            if (!$response->successful()) {
                $data = $response->json();
                $message = $data['message'] ?? 'Could not verify smart card. Please try again later.';
                Log::error("Cable verifySmartCard API error: " . $message);
                return response()->json([
                    'status' => false,
                    'message' => $message
                ], $response->status());
            }

            $data = $response->json();

            // Handle vtpass error codes and missing keys
            if (!isset($data['code']) || $data['code'] != '000') {
                $message = $data['response_description'] ?? $data['message'] ?? 'Verification failed. Please check your details and try again.';
                return response()->json([
                    'status' => false,
                    'message' => $message
                ], 400);
            }

            if (isset($data['content']['error'])) {
                return response()->json([
                    'status' => false,
                    'message' => $data['content']['error']
                ], 400);
            }

            // Prepare safe data extraction
            $customerName = $data['content']['Customer_Name'] ?? null;
            $address = $data['content']['Address'] ?? null;
            $cardNumber = $data['content']['Meter_Number'] ?? $data['content']['Customer_Number'] ?? $billersCode;
            $meterType = $data['content']['Meter_Type'] ?? null;

            $serviceType = 'Smart Card/IUC';
            if ($planName == 'prepaid' || $planName == 'postpaid') {
                $serviceType = 'Meter';
            }

            $message = "You are about to purchase {$serviceId}, {$planName} for (Customer Name: {$customerName}, Customer {$serviceType} Number: {$cardNumber})";

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    "Customer_Name" => $customerName,
                    "Address" => $address,
                    "Card_Number" => $cardNumber,
                    "Meter_Type" => $meterType,
                ]
            ]);
        } catch (RequestException $e) {
            Log::error("verifySmartCard RequestException: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Network error. Please try again later.'
            ], 400);
        } catch (\Throwable $e) {
            Log::error("verifySmartCard Exception: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }
    }

    return response()->json(['status' => false, 'message' => 'Invalid request method'], 405);
}


    public function cableSubscription(Request $request, VtpassService $vtpass, PinService $pinService)
    {


        $validator = Validator::make($request->all(), [
            'smart_card' => 'required|string',
            'cable_Id' => 'required|string',
            'cable_plan_Id' => 'required|string',
            'cable_plan' => 'string|required',
            'amount' => 'string|required',
            'image' => 'string|nullable',
            'beneficiary' => 'boolean|required',

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
            $beneficiary = $request->input('beneficiary', false);

            $requestId = MyFunctions::generateRequestId();

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

            $serviceType = 'Smart Card/IUC';
            $service = 'cable';
            $provider = 1;

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

            $url = config('api.vtpass.base_url') . "pay";

            if ($vtpass->isVtpassEnabled()) {
                $headers = $vtpass->getHeaders();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Server call service is currently disabled.',
                ]);
            }

            $response = Http::withoutVerifying()->withHeaders($headers)->post($url, $data);

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
                    $url = $baseUrl . "requery";
                    $payload = [
                        'request_id' => $data['requestId']
                    ];
                    $response = Http::withoutVerifying()->withHeaders($headers)->post($url, $payload);
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
                            $transaction->user_id = $user->id;
                            $transaction->username = $user->username;
                            $transaction->status = $status;
                            $transaction->service_provider = strtoupper($cableId);
                            $transaction->service = $service;
                            $transaction->plan_id = $cablePlanId;
                            $transaction->smart_card_number = $smartCard;
                            $transaction->service_plan = $cablePlan;
                            $transaction->image = $request->image;
                            // $transaction->quantity =  $quantity;
                            // $transaction->epin = $responseData['pins'];
                            $transaction->transaction_id = (string) $data['requestId'];
                            $transaction->electricity_token = $data['token'] ?? $data['purchased_code'] ?? null;
                            $transaction->which_api = 'vtpass';
                            $transaction->save();

                            $walletTrans = new  WalletTransactions();
                            $walletTrans->trans_type = 'debit';
                            $walletTrans->user = $user->username;
                            $walletTrans->amount = $amount;
                            $walletTrans->service = $service;
                            $walletTrans->transaction_id = (string) $data['requestId'];
                            $walletTrans->balance_before = $balance_before;
                            $walletTrans->balance_after = $user->wallet_balance;
                            $walletTrans->status = $status;
                            $walletTrans->save();

                            (new ReferralService())->handleFirstTransactionBonus($user, $service, $amount);

                            if ($beneficiary ?? false) {
                                try {
                                    if (!empty($smartCard) || !empty($cableId)) {
                                        (new BeneficiaryService())->save([
                                            'type'       => $service,
                                            'identifier' => $smartCard ?? $cableId,
                                            'provider'   => $provider,
                                        ], $user);
                                    } else {
                                        Log::error('Beneficiary mobile number is missing');
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Failed to save beneficiary', ['error' => $e->getMessage()]);
                                }
                            }

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
    // Helper function to clean the plan name
    protected function extractCleanName($name)
    {
        // Remove price patterns like "N2,565", "N850,000", "N3,500", "N2000", etc.
        $name = preg_replace('/\s*-?\s*N[\d,\.]+/i', '', $name);

        // Remove "Add-on", "Service", "Bundle", "Subscription", "Access", "Standalone", etc. at the end
        $name = preg_replace('/\s*(Add-on|Service|Bundle|Subscription|Access|Standalone|Premier|Membership|Amount)\b/i', '', $name);

        // If there's a "+", keep only the first part (e.g., "DStv Premium + French + Extra View" => "DStv Premium")
        $parts = preg_split('/\s*\+\s*/', $name);
        $name = $parts[0];

        // If there's a "-", keep only the first part (e.g., "Showmax Standalone - N3,500" => "Showmax Standalone")
        $parts = preg_split('/\s*-\s*/', $name);
        $name = $parts[0];

        // Remove trailing/leading whitespace
        return trim($name);
    }

    protected function extractValidity($name)
    {
        // Look for patterns like "1 Month", "3 Months", "7 Days", "1 Week", etc.
        if (preg_match('/(\d+)\s*(month|day|week|year)s?/i', $name, $matches)) {
            $num = (int)$matches[1];
            $unit = strtolower($matches[2]);
            // Pluralize if number > 1
            if ($num > 1) {
                $unit = match ($unit) {
                    'month' => 'months',
                    'day' => 'days',
                    'week' => 'weeks',
                    'year' => 'years',
                    default => $unit
                };
            }
            return "{$num} {$unit}";
        }
        return null;
    }
}
