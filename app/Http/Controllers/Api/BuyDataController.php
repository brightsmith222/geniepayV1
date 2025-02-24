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



class BuyDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function getDataPlan(Request $request)
    {

        try {
            $url = 'https://www.gladtidingsdata.com/api/user/';
            $gladAPIKey = 'a1239eb69326d9705ffcbb47f7f1bd439e2190d1';

            // Define the headers, including the token
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $gladAPIKey,
            ];




            $response = Http::withHeaders($headers)->post($url);

            if ($response->successful()) {

                $responseData = $response->json();

                Log::info('response data: ' . $response->body());

                $mtnDataPlan = $responseData['Dataplans']['MTN_PLAN']['ALL'] ?? [];
                $gloDataPlan = $responseData['Dataplans']['GLO_PLAN']['ALL'] ?? [];
                $airtelDataPlanCorporate = $responseData['Dataplans']['AIRTEL_PLAN']['CORPORATE'] ?? [];
                $airtelDataPlanGifting = $responseData['Dataplans']['AIRTEL_PLAN']['GIFTING'] ?? [];
                $_9DataPlanCorporate = $responseData['Dataplans']['9MOBILE_PLAN']['CORPORATE'] ?? [];
                $_9DataPlanSme = $responseData['Dataplans']['9MOBILE_PLAN']['SME'] ?? [];
                $_9DataPlanGifting = $responseData['Dataplans']['9MOBILE_PLAN']['GIFTING'] ?? [];


                $allDataPlans = array_merge(
                    $mtnDataPlan,
                    $gloDataPlan,
                    $airtelDataPlanCorporate,
                    $airtelDataPlanGifting,
                    $_9DataPlanCorporate,
                    $_9DataPlanSme,
                    $_9DataPlanGifting
                );

                $myDataPlans = [];

                foreach ($allDataPlans as $dadaPlan) {
                    $duration = $dadaPlan['month_validate'];
                    $myDataPlans[] = [
                        "plan_id" => $dadaPlan['dataplan_id'],
                        "plan" => $dadaPlan['plan'],
                        "network" => $dadaPlan['network'],
                        "plan_type" => $dadaPlan['plan_type'],
                        "plan_network" => $dadaPlan['plan_network'],
                        "month_validate" => $dadaPlan['month_validate'],
                        "amount" => $dadaPlan['plan_amount'] * 2
                    ];
                }
                // Log::info('data: ' . $myDataPlans);
                $networkPercent = AirtimeTopupPercentage::all();
                return response()->json([
                    'status' => true,
                    'networkPercent' => $networkPercent,
                    'data' => $myDataPlans,
                    
                    // 'mtn_percent' => $_9DataPlanSme,
                    // 'glo_percent' => $_9DataPlanGifting,
                    // 'airtel_percent' => $_9DataPlanSme,
                    // '9mobile_percent' => $_9DataPlanSme
                ]);
            } else {
                Log::error("Error: " . $response->status());
                Log::error("Response: " . $response->body());
                return response()->json([
                    'status' => false,
                    'message' => $response->body()
                ]);
            }
        } catch (RequestException $e) {
            // Handle exceptions that occur during the HTTP request
            Log::error("Request failed: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Handle any other exceptions
            Log::error("An error occurred: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }





    public function buyData(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'network' => 'required|integer',
            'amount' => 'required',
            'plan' => 'string|required',
            'plan_size' => 'string|required',
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
            $plan = $request->input('plan');

            $user = $request->user();
            $wallet_balance = $user->wallet_balance;

            if ($wallet_balance < $amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can\'t topup due to insufficient balance N ' . $wallet_balance
                ], 401);
            }


            if ($network == 1) {
                return response()->json([
                    'status' => true,
                    'message' => 'Top-up successful',
                ]);
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

            // $networkPercent = AirtimeTopupPercentage::where('network_id', '=', $network)->first();
            $amount_charged = $amount;

            $url = 'https://www.gladtidingsdata.com/api/data/';
            $gladAPIKey = 'a1239eb69326d9705ffcbb47f7f1bd439e2190d1';

            // Define the headers, including the token
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $gladAPIKey,
            ];



            $data = [
                'network' => $network,
                'mobile_number' => $mobile_number,
                'plan' => $plan,
                'Ported_number' => true,
            ];



            $response = Http::withHeaders($headers)->post($url, $data);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $responseData = json_decode($response->getBody()->getContents(), true);

                if ($statusCode == 201 || $statusCode == 200) {
                    Log::info('API details', $responseData);

                    if (isset($responseData['Status']) && $responseData['Status'] == 'failed') {
                        $transaction = new Transactions();
                        $transaction->amount = "{$amount}";
                        $transaction->service_provider = $responseData['plan_network'];
                        $transaction->service = 'data';
                        $transaction->status = 'Failed';
                        $transaction->image = $request->image;
                        $transaction->phone_number = $mobile_number;
                        $transaction->transaction_id = $responseData['ident'];
                         $transaction->service_plan = $request->plan_size;
                        $transaction->save();
                        Log::error('API failed with details', $responseData);
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
                    $transaction->service = 'data';
                    $transaction->status = 'Successful';
                    $transaction->image = $request->image;
                    $transaction->phone_number = $mobile_number;
                    $transaction->transaction_id = $responseData['ident'];
                    $transaction->service_plan = $request->plan_size;
                    $transaction->save();

                    $walletTrans = new  WalletTransactions();
                            $walletTrans->trans_type = 'debit';
                            $walletTrans->user = $user->username;
                            $walletTrans->amount = "{$amount}";
                            $walletTrans->service = 'data';
                            $walletTrans->status = 'Successful';
                            $walletTrans->transaction_id = $responseData['ident'];
                            $walletTrans->balance_before = $balance_before;
                            $walletTrans->balance_after = $user->wallet_balance;
                            $walletTrans->save();

                    return response()->json([
                        'status' => true,
                        'message' => 'You have successfully purchased '. $request->plan_size .  'at N' . $request->amount . ' to ' . $request->mobile_number . '. Thanks for the patronage. Enjoy your',
                        'data' => $transaction
                       
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

            // Handle exceptions that occur during the HTTP request
            Log::error("Request failed:13 " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        } catch (\Exception $e) {
            // Handle any other exceptions
            // echo "An error occurred: " . $e->getMessage();
            Log::error("Request failed:14 " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }
}
