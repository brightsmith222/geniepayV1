<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transactions;
use App\Models\WalletTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use App\MyFunctions;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Services\PinService;



class PaymentController extends Controller
{
    public function monnifyPayWithCard(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'amount' => 'required|string',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        try {
            $accessToken = MyFunctions::monnifyAuth();
            if ($accessToken == false) {
                return false;
            }
            $url = 'https://sandbox.monnify.com/api/v1/merchant/transactions/init-transaction';

            $payload = [
                "amount" => $request->amount,
                "customerName" => "Stephen Ikhane",
                "customerEmail" => "stephen@ikhane.com",
                "paymentReference" => "Geniepay|" . MyFunctions::generateRequestId(),
                "paymentDescription" => "Trial transaction",
                "currencyCode" => "NGN",
                "contractCode" => "3466853259",
                // "redirectUrl" => "https =>//my-merchants-page.com/transaction/confirm",
                "paymentMethods" => ["CARD", "ACCOUNT_TRANSFER"]
            ];

            $headers = [

                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $accessToken

            ];

            // Send POST request to Monnify API for authentication
            $response = Http::withHeaders($headers)->post($url, $payload);

            // Check if the response status is 200
            Log::info("Monnify Auth response: " . $response);
            if ($response->successful()) {
                // Get the access token from the response
                $data = $response->json();
                if ($data['requestSuccessful'] == true) {

                    $checkoutUrl = $data['responseBody']['checkoutUrl'];
                    return response()->json(
                        [
                            'status' => true,
                            'data' => $checkoutUrl
                        ],
                        200
                    );
                } else {
                    return response()->json(
                        [
                            'status' => false,
                            'message' => $data['responseMessage']
                        ],
                        422
                    );
                }
            } else {
                Log::error("Request for monnify payment failed");
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Something went wrong,  please try again'
                    ],
                    422
                );
            }
        } catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            Log::error("Request for monnify payment  failed" . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        } catch (\Exception $e) {

            Log::error("Request for monnify payment general error: " . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        }
    }

    public function monnifyWebhook(Request $request)
    {
        Log::info('Monnify Webhook Data:');

        $data = json_decode($request->getContent(), true);

        Log::info('Monnify Webhook Data:', $data);

        try {
            $receivedSignature = $request->header('monnify-signature');
            $secretKey = 'X9FV8PP9R0W4MYP259690KK77UM6RME5';

            if ($receivedSignature) {
                // Generate expected signature
                $expectedSignature = hash_hmac(
                    'sha512',
                    $request->getContent(),
                    $secretKey
                );

                // Compare signatures
                if (hash_equals($expectedSignature, $receivedSignature)) {
                    // Retrieve the request's body
                    $data = json_decode($request->getContent(), true);

                    // Handle the webhook event here (you can add logic to process the event)
                    // Example: Log the data
                    Log::info('Paystack Webhook Data:', $data);

                    return response()->json(['status' => 'success'], 200);
                }
            }

            // If the signature doesn't match or is missing, return a 400 Bad Request
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        } catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            Log::error("Request for monnify webhook  failed" . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        } catch (\Exception $e) {

            Log::error("Request for monnify webhook general error: " . $e->getMessage());
            return
                response()->json(
                    [
                        'status' => false,
                        'message' => 'Something went wrong,  please try again'
                    ],
                    422
                );
        }
    }



    public function paystackPayWithCard(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'amount' => 'required|string',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        try {
            // $accessToken = 'sk_live_8e8bd77578eb2daa2ded52faca4541205cd26a68';
            $accessToken = 'sk_test_8c3ada1bedbdf69f78b042862196b7b43a24d1d9';

            $url = 'https://api.paystack.co/transaction/initialize';

            $payload = [
                "amount" => $request->amount * 100,
                "email" => $request->user()->email,
                "reference" => "Geniepay" . MyFunctions::generateRequestId(),
                "callback_url" => "https://geniepayapi.geniepay.ng",
                "channels" => ["card", "bank_transfer", "bank", "ussd"]
            ];

            $headers = [

                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $accessToken

            ];

            // Send POST request to Monnify API for authentication
            $response = Http::withHeaders($headers)->post($url, $payload);

            // Check if the response status is 200
            // Log::info("Paystack Payment response: " . $response);
            if ($response->successful()) {
                // Get the access token from the response
                $data = $response->json();
                if ($data['status'] == true) {

                    $checkoutUrl = $data['data']['authorization_url'];
                    $formattedUrl = stripslashes($checkoutUrl);
                    // Log::info('check the formatted url: ' . $formattedUrl);

                    return response()->json(
                        [
                            'status' => true,
                            'data' => $formattedUrl
                        ],
                        200
                    );
                } else {
                    return response()->json(
                        [
                            'status' => false,
                            'message' => $data['data']['message']
                        ],
                        422
                    );
                }
            } else {
                Log::error("Request for paystack payment failed");
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Something went wrong,  please try again'
                    ],
                    422
                );
            }
        } catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            Log::error("Request for paystack payment  failed" . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        } catch (\Exception $e) {

            Log::error("Request for monnify payment general error: " . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        }
    }






    public function paystackWebhook(Request $request)
    {

        try {
            // Get the website configuration values
            $webconfig = config('website'); // Assuming you have website configuration stored in a config file
            // $secretKey = 'sk_test_8c3ada1bedbdf69f78b042862196b7b43a24d1d9';
            // $secretKey = 'sk_live_8e8bd77578eb2daa2ded52faca4541205cd26a68';

            $secretKey = 'sk_test_8c3ada1bedbdf69f78b042862196b7b43a24d1d9';

            // Log::info('Paystack Webhook Data:');


            // Validate event by checking the Paystack signature
            $receivedSignature = $request->header('X-Paystack-Signature');
            if ($receivedSignature) {
                // Generate expected signature
                $expectedSignature = hash_hmac(
                    'sha512',
                    $request->getContent(),
                    $secretKey
                );

                // Compare signatures
                if (hash_equals($expectedSignature, $receivedSignature)) {
                    // Retrieve the request's body
                    $data = json_decode($request->getContent(), true);

                    // Handle the webhook event here (you can add logic to process the event)
                    // Example: Log the data
                    Log::info('Paystack Webhook Data:', $data);
                    $evenStatus = $data['event'];


                    // if($evenStatus == 'charge.success'){

                    $data = $data['data'];
                    $paymentMethod = $data['channel'];
                    $email = $data['customer']['email'];
                    $amount = $data['amount'] / 100;
                    $reference = $data['reference'];
                    $user = User::where('email', '=', $email)->first();

                    Log::info('here is 1');

                    $checkTransaction = Transactions::where('transaction_id', '=', $reference)->first();
                    if ($checkTransaction != null && $checkTransaction->status == 'Successful') {
                        return response()->json(['status' => 'success'], 200);
                    }

                    Log::info('here is 2');
                    $balance_before = $user->wallet_balance;

                    if ($evenStatus == 'charge.success') {
                        $user->wallet_balance += $amount;
                        $user->save();
                    }

                    Log::info('here is 3');

                    $status = $data['status'] == 'success' ? 'Successful' : $data['status'];

                    Log::info('here is 4');

                    // $transaction = Transactions::updateOrCreate(
                    //     ['transaction_id' => $reference],
                    //     [
                    //         'status' => $status,
                    //         'username' => $user->username,
                    //         'transaction_id' => $reference,
                    //         'amount' => $amount,
                    //         'phone_numbetr' => '07056642288',
                    //         'image' => 'assets/images/card-transfer.png',
                    //         'service' => $paymentMethod . ' payment',
                    //         'service_provider' => 'Paystack',
                    //         // 'username' => $user->username,
                    //     ]
                    // );

                    Log::info('paystack webhook payments');
                    $transaction = new Transactions();
                    $transaction->status = $status;
                    $transaction->username = $user->username;
                    $transaction->transaction_id = $reference;
                    $transaction->amount = $amount;
                    $transaction->phone_number = '07056642288';
                    $transaction->image = 'assets/images/card.png';
                    $transaction->service = 'Wallet Funded';
                    $transaction->service_provider = $paymentMethod;
                    $transaction->service_plan = 'Paystack';

                    $transaction->save();


                    $walletTrans = new  WalletTransactions();
                    $walletTrans->trans_type = 'credit';
                    $walletTrans->user_id = $user->id;
                    $walletTrans->user = $user->username;
                    $walletTrans->amount = $amount;
                    $walletTrans->service = 'Wallet Funded';
                    $walletTrans->status = 'Successful';
                    $walletTrans->transaction_id = $reference;
                    $walletTrans->balance_before = $balance_before;
                    $walletTrans->balance_after = $user->wallet_balance;
                    $walletTrans->save();



                    // }

                    return response()->json(['status' => 'success'], 200);
                }
            }

            // If the signature doesn't match or is missing, return a 400 Bad Request
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        } catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            Log::error("Request for paystack payment  failed" . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        } catch (\Exception $e) {

            Log::error("Request for monnify payment general error: " . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        }
    }


public function transactions(Request $request)
{
    try {
        $user = $request->user();

        $transactions = Transactions::where('user_id', $user->id)->get();

        return response()->json([
            'status' => true,
            'data' => $transactions
        ]);
    } catch (RequestException $e) {
        Log::error("Request for transactions failed" . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, please try again'
        ], 422);
    } catch (\Exception $e) {
        Log::error("Request for transactions general error: " . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, please try again'
        ], 422);
    }
}



public function walletTransactions(Request $request)
{
    try {
        $user = $request->user();

        $transactions = WalletTransactions::where('user', $user->username)
            ->orWhere('receiver_email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $transactions
        ]);
    } catch (RequestException $e) {
        Log::error("Request for wallet transactions failed: " . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, please try again'
        ], 422);
    } catch (\Exception $e) {
        Log::error("Request for wallet transactions general error: " . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, please try again'
        ], 422);
    }
}




    public function verifyReceiver(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|string'

        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        try {
            $user = $request->user();

            $receiverId = $request->receiver_id;
            if ($receiverId == $user->email || $receiverId == $user->username || $receiverId == $user->phone) {
                return response()->json([
                    'status' => false,
                    'message' => 'Oops! Sorry, you can\'t make transfer to yourself.'
                ], 422);
            }
            $receiver = User::where('email', '=', $receiverId)->orWhere('username', '=', $receiverId)->orWhere('phone_number', '=', $receiverId)->first();

            if ($receiver) {
                $message = 'You about to make a transfer to ' . $receiver->full_name;
                return response()->json([
                    'status' => true,
                    'message' => $message
                ],);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'No user found with the data provided, please confirm and try again'
                ], 422);
            }
        } catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            Log::error("Request for transactions  failed" . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        } catch (\Exception $e) {

            Log::error("Request for transactions general error: " . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        }
    }


    public function transfer(Request $request, PinService $pinService)
    {

        $validator = Validator::make($request->all(), [
            'amount' => 'required|string',
            'receiver_id' => 'required|string'

        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        try {

            $pin = $request->input('pin');
            $user = $request->user();


            if (!$pinService->checkPin($user, $pin)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid transaction pin.'
                ], 403);
            }
            $amount = $request->amount;
            $receiverId = $request->receiver_id;

            if ($receiverId == $user->email || $receiverId == $user->username || $receiverId == $user->phone) {
                return response()->json([
                    'status' => false,
                    'message' => 'Oops! Sorry, you can\'t make transfer to yourself.'
                ], 422);
            }

            $receiver = User::where('email', '=', $receiverId)->orWhere('username', '=', $receiverId)->orWhere('phone_number', '=', $receiverId)->first();

            if ($user->wallet_balance < $amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have sufficient balance to make this transfer, please fund your wallet and try again'
                ], 422);
            }

            if ($receiver) {

                $balance_before = $user->wallet_balance;
                $user->wallet_balance -= $amount;
                $user->save();

                $receiver->wallet_balance += $amount;
                $receiver->save();

                // Debit record for sender
                $walletTransSender = new WalletTransactions();
                $walletTransSender->trans_type = 'debit';
                $walletTransSender->amount = $amount;
                $walletTransSender->user_id = $user->id;
                $walletTransSender->user = $user->username;
                $walletTransSender->sender_email = $user->email;
                $walletTransSender->sender_name = $user->full_name;
                $walletTransSender->receiver_email = $receiver->email;
                $walletTransSender->receiver_name = $receiver->full_name;
                $walletTransSender->service = 'transfer';
                $walletTransSender->transaction_id = MyFunctions::generateRequestId();
                $walletTransSender->balance_before = $balance_before;
                $walletTransSender->balance_after = $user->wallet_balance;
                $walletTransSender->save();

                // Credit record for receiver
                $walletTransReceiver = new WalletTransactions();
                $walletTransReceiver->trans_type = 'credit';
                $walletTransReceiver->amount = $amount;
                $walletTransReceiver->user_id = $receiver->id;
                $walletTransReceiver->user = $receiver->username;
                $walletTransReceiver->sender_email = $user->email;
                $walletTransReceiver->sender_name = $user->full_name;
                $walletTransReceiver->receiver_email = $receiver->email;
                $walletTransReceiver->receiver_name = $receiver->full_name;
                $walletTransReceiver->service = 'transfer';
                $walletTransReceiver->transaction_id = MyFunctions::generateRequestId();
                $walletTransReceiver->balance_before = $receiver->wallet_balance - $amount;
                $walletTransReceiver->balance_after = $receiver->wallet_balance;
                $walletTransReceiver->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Transfer completed successfully',
                    'data' => $walletTransSender
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'No user found with the data provided, please confirm and try again'
                ], 422);
            }
        } catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            Log::error("Request for transactions  failed" . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        } catch (\Exception $e) {

            Log::error("Request for transactions general error: " . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        }
    }

    public function walletTrans() {}
}
