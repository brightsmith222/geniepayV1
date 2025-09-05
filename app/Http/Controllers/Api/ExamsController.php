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
use App\Services\VtpassJambService;
use App\Services\ReferralService;
use App\Services\PinService;


class ExamsController extends Controller
{


    public function buyResultChecker(Request $request, PinService $pinService)
    {
        $validator = Validator::make($request->all(), [
            'exam' =>  'required|string',
            'amount' => 'string|required',
            'quantity' => 'string|required',
            'image' => 'string|nullable'
        ]);

        if ($validator->fails()) {
            Log::warning('buyResultChecker validation failed', [
                'errors' => $validator->errors()->all(),
                'request' => $request->all()
            ]);
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $exam = $request->input('exam');
            $quantity = $request->input('quantity');
            $amount = $request->input('amount');
            $pin = $request->input('pin');
            $user = $request->user();

            if (!$pinService->checkPin($user, $pin)) {
                Log::warning('buyResultChecker: Invalid transaction pin', [
                    'user_id' => $user->id,
                    'username' => $user->username
                ]);
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid transaction pin.'
                ], 403);
            }

            $wallet_balance = $user->wallet_balance;

            if ($wallet_balance < $amount) {
                Log::warning('buyResultChecker: Insufficient balance', [
                    'user_id' => $user->id,
                    'wallet_balance' => $wallet_balance,
                    'amount' => $amount
                ]);
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance ₦' . number_format($wallet_balance)
                ], 400);
            }

            $amount_charged = $amount;
            $url = config('api.glad.base_url') . "api/epin/";
            $gladAPIKey = config('api.glad.api_key');

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . $gladAPIKey,
            ];

            $data = [
                'exam_name' => strtoupper($exam),
                'quantity' => $quantity,
                'amount' => $amount,
            ];

            Log::info('buyResultChecker: Sending request to Glad API', [
                'url' => $url,
                'headers' => $headers,
                'data' => $data
            ]);

            $response = Http::withoutVerifying()->withHeaders($headers)->post($url, $data);
            $statusCode = $response->getStatusCode();

            Log::info('buyResultChecker: Glad API response status code', [
                'status_code' => $statusCode
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                $responseData = json_decode($response->getBody()->getContents(), true);

                if (!is_array($responseData)) {
                    Log::error('buyResultChecker: Glad API returned invalid or empty response', [
                        'raw_response' => $response->getBody()->getContents()
                    ]);
                    return response()->json([
                        'status' => false,
                        'message' => 'An error occurred. Please try again later.'
                    ], 500);
                }

                Log::info('buyResultChecker: Glad API response data', [
                    'response' => $responseData
                ]);

                if ($statusCode == 201 || $statusCode == 200) {
                    if (isset($responseData['Status']) && $responseData['Status'] == 'failed') {
                        $transaction = new Transactions();
                        $transaction->amount = $amount;
                        $transaction->service_provider = strtoupper($exam);
                        $transaction->service = 'exam';
                        $transaction->status = 'Failed';
                        $transaction->image = $request->image;
                        $transaction->quantity =  $quantity;
                        $transaction->epin = $responseData['pins'] ?? null;
                        $transaction->transaction_id = (string) ($responseData['id'] ?? null);
                        $transaction->save();
                        Log::error('buyResultChecker: API returned failed status', [
                            'response' => $responseData
                        ]);
                        return response()->json([
                            'status' => false,
                            'message' => 'Transaction failed, please try again'
                        ], 400);
                    }

                    $user->wallet_balance = $wallet_balance - $amount_charged;
                    $balance_before = $wallet_balance;
                    $user->save();

                    $transaction = new Transactions();
                    $transaction->amount = $amount;
                    $transaction->service_provider = strtoupper($exam);
                    $transaction->service = 'exam';
                    $transaction->status = 'Successful';
                    $transaction->image = $request->image;
                    $transaction->quantity =  $quantity;
                    $transaction->epin = $responseData['pins'] ?? null;
                    $transaction->transaction_id = (string) ($responseData['id'] ?? null);
                    $transaction->save();

                    $walletTrans = new  WalletTransactions();
                    $walletTrans->trans_type = 'debit';
                    $walletTrans->user_id = $user->id;
                    $walletTrans->user = $user->username;
                    $walletTrans->amount = "{$amount}";
                    $walletTrans->service = 'exam';
                    $walletTrans->status = 'Successful';
                    $walletTrans->transaction_id = (string) ($responseData['id'] ?? null);
                    $walletTrans->balance_before = $balance_before;
                    $walletTrans->balance_after = $user->wallet_balance;
                    $walletTrans->save();

                    Log::info('buyResultChecker: Transaction and wallet transaction saved', [
                        'transaction_id' => $transaction->transaction_id,
                        'user_id' => $user->id
                    ]);

                    return response()->json([
                        'status' => true,
                        'message' => 'Result checker purchased successfully.',
                        'data' => [
                            'transaction' => $transaction,
                            'pins' => $responseData['pins'] ?? null
                        ]
                    ]);
                }

                Log::warning("buyResultChecker: Unexpected API response", [
                    'response' => $responseData
                ]);
                $errorMsg = $responseData['error'] ?? 'Transaction could not be processed, please try again.';
                return response()->json([
                    'status' => false,
                    'message' => $errorMsg
                ], $statusCode);
            }

            // Handle non-2xx responses
            $responseBody = $response->getBody()->getContents();
            $responseArr = json_decode($responseBody, true);
            $service_error = $responseArr['error'][0] ?? 'Something went wrong, please contact admin';
            if (Str::contains($service_error, 'insufficient balance')) {
                $service_error = 'Something went wrong, please contact admin';
            }
            Log::error("buyResultChecker: Non-2xx response", [
                'status_code' => $statusCode,
                'error' => $service_error,
                'response' => $responseArr
            ]);
            return response()->json([
                'status' => false,
                'message' => $service_error
            ], $statusCode);
        } catch (RequestException $e) {
            Log::error("buyResultChecker: RequestException", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'A network error occurred. Please try again later.'
            ], 500);
        } catch (\Exception $e) {
            Log::error("buyResultChecker: Exception", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }
    }

    public function fetchJambVariations(VtpassJambService $vtpass)
    {
        try {
            $serviceID = 'jamb';
            $variations = $vtpass->getJambVariations($serviceID);

            Log::info('Fetched JAMB variations', [
                'variations' => $variations,
            ]);

            if (isset($variations['content']['variations'])) {
                return response()->json([
                    'status' => true,
                    'variations' => $variations['content']['variations']
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to fetch variations'
            ], 400);
        } catch (\Throwable $th) {
            Log::error('Error fetching JAMB variations: ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching variations. Please try again later.'
            ], 500);
        }
    }

    public function verifyJambProfile(Request $request, VtpassJambService $vtpass)
    {
        $request->validate([
            'profile_id' => 'required|string',
            'variation_code' => 'required|string',
        ]);

        $result = $vtpass->verifyJambProfile($request->profile_id, $request->variation_code);

        if (isset($result['content']['Customer_Name'])) {
            return response()->json([
                'status' => true,
                'data' => [
                    'full_name' => $result['content']['Customer_Name'],
                ],

            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['response'] ?? 'Verification failed',
        ], 422);
    }

    public function purchaseJambPin(Request $request, VtpassJambService $vtpass, PinService $pinService)
    {
        try {
            $request->validate([
                'profile_id' => 'required|string',
                'variation_code' => 'required|string',
                'phone' => 'required|string',
                'amount' => 'required|numeric',
                'image' => 'nullable|string',
            ]);

            $profile_id = $request->input('profile_id');
            $variation_code = $request->input('variation_code');
            $phone = $request->input('phone');
            $amount = $request->input('amount');
            $image = $request->input('image', null);

            $pin = $request->input('pin');
            $user = $request->user();


            if (!$pinService->checkPin($user, $pin)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid transaction pin.'
                ], 403);
            }

            $examType = 'jamb';
            $totalAmount = $request->amount;
            $wallet_balance = $user->wallet_balance;

            if ($wallet_balance < $totalAmount) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Insufficient balance ₦' . number_format($wallet_balance)
                    ],
                    400
                );
            }

            // Initial purchase call
            $response = $vtpass->purchaseJambPin(
                $profile_id,
                $variation_code,
                $amount,
                $phone
            );

            Log::info('VTpass JAMB PIN purchase response', ['response' => $response]);

            if (!is_array($response) || !isset($response['code'])) {
                return response()->json(['status' => false, 'message' => 'Invalid response from VTpass'], 500);
            }

            if ($response['code'] !== '000' && isset($response['response_description'])) {
                return response()->json([
                    'status' => false,
                    'message' => $response['response_description']
                ], 400);
            }

            // Skip requery if pin is already present in initial response
            $requeryResponse = null;
            if ($response['code'] === '000' && isset($response['Pin'])) {
                $requeryResponse = $response;
            } else {
                // Fallback to requery
                $requeryResponse = $vtpass->requeryTransaction($response['requestId'] ?? $request->request_id);

                if (!is_array($requeryResponse) || !in_array($requeryResponse['code'], ['000', '099'])) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Transaction could not be processed, please try again'
                    ], 400);
                }
            }

            $trans_status = $requeryResponse['content']['transactions']['status'] ?? 'pending';
            $status = $trans_status === 'delivered' ? 'Successful' : 'Processing';

            $balance_before = $user->wallet_balance;
            $user->wallet_balance -= $totalAmount;
            $user->save();

            $transaction = new Transactions();
            $transaction->amount = $totalAmount;
            $transaction->user_id = $user->id;
            $transaction->username = $user->username;
            $transaction->status = $status;
            $transaction->service = 'exam';
            $transaction->service_provider = $examType;
            $transaction->service_plan = $requeryResponse['content']['transactions']['product_name'] ?? null;
            $transaction->plan_id = $request->variation_code;
            $transaction->phone_number = $request->phone;
            $transaction->epin = $requeryResponse['Pin'] ?? null;
            $transaction->image = $image ?? null;
            $transaction->transaction_id = (string) $requeryResponse['requestId'] ?? null;
            $transaction->reference = (string) $requeryResponse['content']['transactions']['transactionId'] ?? null;
            $transaction->smart_card_number = $requeryResponse['content']['transactions']['unique_element'] ?? null;
            $transaction->which_api = 'vtpass';
            $transaction->save();

            $walletTrans = new WalletTransactions();
            $walletTrans->trans_type = 'debit';
            $walletTrans->user_id = $user->id;
            $walletTrans->user = $user->username;
            $walletTrans->amount = $totalAmount;
            $walletTrans->service = $examType;
            $walletTrans->transaction_id = (string) $transaction->transaction_id;
            $walletTrans->balance_before = $balance_before;
            $walletTrans->balance_after = $user->wallet_balance;
            $walletTrans->status = $status;
            $walletTrans->save();

            (new ReferralService())->handleFirstTransactionBonus($user, $examType, $totalAmount);

            return response()->json([
                'status' => true,
                'message' => $requeryResponse['response_description'] ?? 'Transaction completed',
                'data' => $transaction
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error purchasing JAMB PIN: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while purchasing JAMB PIN. Please try again later.'
            ], 500);
        }
    }


    public function getAllWaecVariations(VtpassJambService $vtpass)
    {
        try {
            $services = ['waec', 'waec-registration'];
            $allVariations = [];

            foreach ($services as $serviceID) {
                $response = $vtpass->getWaecVariations($serviceID);

                $data = $response;
                Log::info('Fetched WAEC variations for service ID: ' . $serviceID, [
                    'response' => $data,
                ]);

                if (isset($data['response_description']) && $data['response_description'] === '000') {
                    foreach ($data['content']['variations'] as $variation) {
                        $allVariations[] = [
                            'serviceID' => $serviceID,
                            'variation_code' => $variation['variation_code'],
                            'name' => $variation['name'],
                            'service_name' => $variation['ServiceName'] ?? null,
                            'amount' => $variation['variation_amount']
                        ];
                    }
                }
            }

            return response()->json([
                'status' => true,
                'variations' => $allVariations
            ]);
        } catch (\Throwable $e) {
            Log::error('Error fetching WAEC variations: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to load WAEC variations'
            ], 500);
        }
    }

    public function purchaseWaecPin(Request $request, VtpassJambService $vtpass, PinService $pinService)
    {
        try {
            $request->validate([
                'serviceID' => 'required|string',
                'variation_code' => 'required|string',
                'phone' => 'required|string',
                'quantity' => 'required|integer|min:1',
                'image' => 'nullable|string',
                'amount' => 'required|numeric',
            ]);

            $serviceID = $request->input('serviceID');
            $variation_code = $request->input('variation_code');
            $phone = $request->input('phone');
            $quantity = $request->input('quantity');
            $amount = $request->input('amount');

            $pin = $request->input('pin');
            $user = $request->user();


            if (!$pinService->checkPin($user, $pin)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid transaction pin.'
                ], 403);
            }

            $examType = 'waec';
            $totalAmount = $request->amount;
            $wallet_balance = $user->wallet_balance;

            if ($wallet_balance < $totalAmount) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Insufficient balance ₦' . number_format($wallet_balance)
                    ],
                    400
                );
            }

            // Step 1: Attempt purchase
            $response = $vtpass->purchaseWaecPin(
                $serviceID,
                $variation_code,
                $amount,
                $phone,
                $quantity
            );

            Log::info('response from VTpass purchaseWaecPin', ['response' => $response]);

            if (!is_array($response) || !isset($response['code'])) {
                return response()->json(['status' => false, 'message' => 'Unexpected response format'], 500);
            }

            // Step 2: Handle failure response early
            if ($response['code'] !== '000' && isset($response['response_description'])) {
                $errorMsg = $response['response_description'];
                if (stripos($errorMsg, 'VARIATION CODE DOES NOT EXIST FOR SELECTED PRODUCT') !== false) {
                    $errorMsg = 'The selected WAEC PIN type is not available. Please choose a another option.';
                }
                return response()->json([
                    'status' => false,
                    'message' => $errorMsg
                ], 400);
            }

            // Step 3: Determine whether to requery or not
            $requeryResponse = null;
            $cards = $response['cards'] ?? null;

            if ($response['code'] === '000' && is_array($cards)) {
                // No need to requery — already successful
                $requeryResponse = $response;
            } else {
                // Requery if pending or missing data
                $requeryResponse = $vtpass->requeryTransaction($response['requestId'] ?? $request->request_id);

                if (!is_array($requeryResponse) || !in_array($requeryResponse['code'], ['000', '099'])) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Transaction could not be processed, please try again'
                    ], 400);
                }
            }

            // Step 4: Process the final response
            $trans_status = $requeryResponse['content']['transactions']['status'] ?? 'pending';
            $status = $trans_status === 'delivered' ? 'Successful' : 'Processing';

            $serials = '';
            $pins = '';

            // Case 1: cards array (serial and pin)
            if (!empty($requeryResponse['cards'])) {
                $serials = collect($requeryResponse['cards'])->pluck('Serial')->implode(',');
                $pins = collect($requeryResponse['cards'])->pluck('Pin')->implode(',');
            }
            // Case 2: tokens array (token only)
            elseif (!empty($requeryResponse['tokens'])) {
                $pins = is_array($requeryResponse['tokens'])
                    ? implode(',', $requeryResponse['tokens'])
                    : $requeryResponse['tokens'];
                $serials = null;
            }
            // Case 3: purchased_code (fallback)
            elseif (!empty($requeryResponse['purchased_code'])) {
                $pins = $requeryResponse['purchased_code'];
                $serials = null;
            }
            $balance_before = $user->wallet_balance;
            $user->wallet_balance -= $totalAmount;
            $user->save();

            $transaction = new Transactions();
            $transaction->amount = $totalAmount;
            $transaction->user_id = $user->id;
            $transaction->username = $user->username;
            $transaction->status = $status;
            $transaction->service = 'waec';
            $transaction->service_provider = $examType;
            $transaction->service_plan = $requeryResponse['content']['transactions']['product_name'] ?? null;
            $transaction->plan_id = $request->variation_code;
            $transaction->phone_number = $request->phone;
            $transaction->serial = $serials;
            $transaction->epin = $pins;
            $transaction->image = $request->image ?? null;
            $transaction->transaction_id = (string) $requeryResponse['requestId'] ?? null;
            $transaction->reference = (string) $requeryResponse['content']['transactions']['transactionId'] ?? null;
            $transaction->smart_card_number = $requeryResponse['content']['transactions']['unique_element'] ?? null;
            $transaction->which_api = 'vtpass';
            $transaction->save();

            $walletTrans = new WalletTransactions();
            $walletTrans->trans_type = 'debit';
            $walletTrans->user = $user->username;
            $walletTrans->amount = $totalAmount;
            $walletTrans->service = $examType;
            $walletTrans->transaction_id = (string) $transaction->transaction_id;
            $walletTrans->balance_before = $balance_before;
            $walletTrans->balance_after = $user->wallet_balance;
            $walletTrans->status = $status;
            $walletTrans->save();

            (new ReferralService())->handleFirstTransactionBonus($user, $examType, $totalAmount);

            return response()->json([
                'status' => true,
                'message' => $requeryResponse['response_description'] ?? 'Transaction completed',
                'data' => $transaction
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error purchasing WAEC PIN: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while purchasing WAEC PIN. Please try again later.'
            ], 500);
        }
    }
}
