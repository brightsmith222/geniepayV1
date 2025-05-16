<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerification;
use App\Mail\EmailForgotPassword;
use App\Mail\EmailChangePin;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Client\RequestException;
use App\MyFunctions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\GeneralSettings;
use Jenssegers\Agent\Agent;



use Illuminate\Support\Facades\Mail;


class UserController extends Controller
{

    public function user(Request $request)
    {
        $user = $request->user()->only('email', 'full_name', 'wallet_balance', 'phone_number', 'image', 'account_reference', 'username');
        // $credentials = $request->only('email', 'password');

        // Log::info('user details: ' . $user);

        try {

            $accessToken = MyFunctions::monnifyAuth();

            $monnifyHeaders = [

                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $accessToken

            ];

            $accountReference = $request->user()->account_reference;

            $url = "https://sandbox.monnify.com/api/v2/bank-transfer/reserved-accounts/" . $accountReference;

            // Send POST request to Monnify API for authentication
            $response = Http::withHeaders($monnifyHeaders)->get($url);

            // Check if the response status is 200
            Log::info("Monnify V Accounts response: " . $response);
            $bank_accounts = [];
            if ($response->successful()) {
                // Get the access token from the response
                $data = $response->json();
                $bank_accounts = $data['responseBody']['accounts'];
            } else {
                Log::error("Request for monnify V Accounts failed");
            }

            return response()->json(
                [
                    'status' => true,
                    'data' => $user,
                    'bank_accounts' => $bank_accounts

                ],
                200
            );
        } catch (\Throwable $th) {
            Log::info('user details: ' . $th);
            return response()->json(
                [
                    'status' => false,
                    'message' => $th->getMessage()
                ],
                422
            );
        };
    }

    public function registerUser(Request $request)
    {
        $isReferralEnabled = GeneralSettings::where('name', 'referral')->value('is_enabled');
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'full_name' => 'string|required',
            // 'last_name' => 'string|required',
            'username' => 'required|string|max:255|unique:users',
            'phone_number' => 'required|string|min:11|max:11|unique:users',
            // 'country' => 'required|string',
            'ref_code' => 'nullable|string',
            'password' => [
                'required',
                'string',
                'min:8',
                // 'regex:/[A-Z]/', // Must contain an uppercase letter
                // 'regex:/[0-9]/', // Must contain a number
                // 'regex:/[@$!%*?&#]/' // Must contain a special character
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
            $user = new User();

            $username = $request->input('username');
            $fullName = $request->input('full_name');
            // $lastName = $request->input('last_name');
            $email = $request->input('email');
            $phone = $request->input('phone_number');
            $password = $request->input('password');
            $ref_code = $request->input('ref_code');
            // $country     = $request->input('country');

            $user->full_name = $fullName;
            // $user->last_name = $lastName;
            $user->email = $email;
            $user->phone_number = $phone;
            // $user->status  = $status;
            $user->username  = $username;
            if ($ref_code) {
                $referrer = User::where('username', $ref_code)->first();
                if ($referrer) {
                    $user->referred_by = $referrer->id;
                }
            }
            $user->referral_bonus_eligible = $isReferralEnabled;
            // $user->photo  = $photo;
            // $user->country  = $country;

            $accountReference = MyFunctions::reserveAccount(
               $email,  $fullName, $username
            );

            $user->account_reference = $accountReference == false ? null : $accountReference;

            $user->password = Hash::make($password);


            if ($user->save()) {
                $code = rand(100000, 999999);
                $user->verification_code = $code;
                $user->verification_code_expires_at = now()->addMinutes(15); // Code expires in 15 minutes
                $user->save();

                // Send the code via email
                Mail::to($email)->send(new EmailVerification($code));

                // $token = $user->createToken($email)->plainTextToken;
                // return GeneralResource::collection($user);
                // Send the code via email

                return response()->json(
                    [
                        'status' => true,
                        'message' => 'User saved successfully',
                        // 'token' => $token
                    ],
                    200

                );
            }else{
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Something went wrong, user could not be saved'
                    ],
                    422
                ); 
            }


            
        } catch (\Throwable $th) {
            log::error('check error: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 422);
        }
    }

    public function updateUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string',
            'username' => 'required|string',
            'phone_number' => 'required|string|min:11|max:11',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }


        try {
            $fullName = $request->input('full_name');
            $userName = $request->input('username');
            $phone = $request->input('phone_number');



            $user = $request->user();

            if ($user->phone_number != $phone) {
                $checkUserPhone = User::where('phone_number', $phone)->first();
                if ($checkUserPhone) {
                    return response()->json(
                        [
                            'status' => false,
                            'message' => 'Oops! This phone number already exist, please use another phone number'
                        ],
                        422
                    );
                }
            }

            if ($user->username != $userName) {
                $checkUserName = User::where('username', $userName)->first();
                if ($checkUserName) {
                    return response()->json(
                        [
                            'status' => false,
                            'message' => 'Oops! This username already exist, please use another username'
                        ],
                        422
                    );
                }
            }


            $user->phone_number = $phone;
            $user->full_name = $fullName;
            $user->username = $userName;

           

            if ($user->save()) {
                // return GeneralResource::collection($user);
                return response()->json(
                    [
                        'status' => true,
                        'message' => 'User details updated successfully'
                    ],
                    200

                );
            } else {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Oops! Something went wrong, user could not be updated'
                    ],
                    422
                );
            }


        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 422);
        }
    }


    public function userLogin(Request $request)
    {



        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => [
                'required',
                'string',
            ],
            'fcm_token' => 'string|nullable'

        ],  [
            'email.exists' => 'Email does not exist, please create an account.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422); // 422 Unprocessable Entity
        }

        try {

            $email = $request->input('email');
            $password = $request->input('password');

            $user = User::where('email', '=', $email)->first();

            if ($user) {
                $userPassword = $user->password;
                $epin = $user->pin;

                if (Hash::check($password, $userPassword)) {

                    // $credentials = $request->only('email', 'password');

                    $token = $user->createToken($email)->plainTextToken;
                    $user->last_login_at = now();
                    $user->fcm_token = $request->fcm_token;
                    $user->save();
                    return response()->json([
                        'status' => true,
                        'verified' => $user->first_verification,
                        'epin' =>$epin,
                        'token' => $token
                    ]);
                }else{
                    return response()->json(
                        [
                            'status' => false,
                            'message' => 'Wrong password'
                        ],
                        422
                    );
                }
                
            } else {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'No acount found for this email, please create an account'
                    ],
                    422
                );
            }
        } catch (\Throwable $th) {
            Log::error('Error response: ' . $th->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Error response: ' . $th->getMessage()
                ],
                422
            );
        }
    }


    public function sendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors()->first()
                ],
                422
            );
        }

        try {
            $email = $request->input('email');
            // $code = Str::random(4, '0123456789');
            // $code = Random::number(4);
            $code = rand(100000, 999999);

            // Update user with the verification code and expiration time
            $user = User::where('email', $email)->first();
            $user->verification_code = $code;
            $user->verification_code_expires_at = now()->addMinutes(15); // Code expires in 15 minutes
            $user->save();

            // Send the code via email
            Mail::to($email)->send(new EmailVerification($code));

            return response()->json([
                'status' => true,
                'message' => 'Verification code sent to your email! ' . $email
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error sending verification code: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'An error occurred. Please try again later. ' . $th->getMessage()], 500);
        }
    }

    public function sendForgotPasswordCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors()->first()
                ],
                422
            );
        }

        try {
            $email = $request->input('email');
            // $code = Str::random(4, '0123456789');
            // $code = Random::number(4);
            $code = rand(100000, 999999);

            // Update user with the verification code and expiration time
            $user = User::where('email', $email)->first();
            $user->verification_code = $code;
            $user->verification_code_expires_at = now()->addMinutes(15); // Code expires in 15 minutes
            $user->save();
            $ip = request()->ip();

            // Get the user's device information
            $agent = new Agent();
            $device = $agent->device();
            $platform = $agent->platform();
            $browser = $agent->browser();
            $deviceInfo = "{$device} ({$platform}, {$browser})";

            // Send the code via email
            Mail::to($email)->send(new EmailForgotPassword($code, $deviceInfo, $ip));

            return response()->json([
                'status' => true,
                'message' => 'Verification code sent to your email! ' . $email
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error sending verification code: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'An error occurred. Please try again later. ' . $th->getMessage()], 500);
        }
    }

    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'first_time' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([

                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $email = $request->input('email');
            $code = $request->input('code');

            // Retrieve the user by email
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'User not found.'
                    ],
                    404
                );
            }

            // Check if the code matches and is not expired
            if (
                $user->verification_code === $code && $user->verification_code_expires_at &&
                $user->verification_code_expires_at >= now()
            ) {
                // Code is valid and not expired, proceed with verification

                // Optionally, you might want to clear the verification code
                $user->verification_code = null;
                $user->verification_code_expires_at = null;
                if ($request->input('first_time') === 'yes') {
                    $user->first_verification = 'yes';
                }
                $user->save();

                return response()->json(
                    [
                        'status' => true,
                        'message' => 'Verification code is valid.'
                    ],
                    200
                );
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or expired verification code.'
                ], 400);
            }
        } catch (\Throwable $th) {
            Log::error('Error verifying code: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'An error occurred. Please try again later.' . $th->getMessage()
            ], 500);
        }
    }


    public function isAccountVerified(Request $request)
    {

        try {
            $user = User::where('email', $request->input('email'))->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'No user associated with this email'
                ]);
            }

            if ($user->first_verification === 'yes') {
                return response()->json([
                    'status' => true,
                    'verified' => $user->first_verification
                ]);
            } else {
                $code = rand(100000, 999999);
                // Mail::to($request->input('email'))->send(new VerificationCodeMail($code));
                return response()->json([
                    'status' => true,
                    'verified' => $user->first_verification
                ]);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    public function changePassword1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'email' => 'required|email|exists:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                // 'regex:/[A-Z]/', // Must contain an uppercase letter
                // 'regex:/[0-9]/', // Must contain a number
                // 'regex:/[@$!%*?&#]/' // Must contain a special character
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors()->first()
                ],
                422
            );
        }

        try {
            // $email = $request->input('email');
            $password = $request->input('password');

            $user = $request->user();

            // $user->password = $password;
            $user->password = Hash::make($password);
            $user->save();

            return response()->json(
                [
                    'status' => true,
                    'message' => 'Password changed successfully.'
                ],
                200
            );
        } catch (\Throwable $th) {
            Log::error('Error verifying code: ' . $th->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'An error occurred. Please try again later.'
                ],
                500
            );
        }
    }


    public function recoverPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                // 'regex:/[A-Z]/', // Must contain an uppercase letter
                // 'regex:/[0-9]/', // Must contain a number
                // 'regex:/[@$!%*?&#]/' // Must contain a special character
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors()->first()
                ],
                422
            );
        }

        try {
            $email = $request->input('email');
            $password = $request->input('password');

            $user = User::where('email', $email)->first();

            // $user->password = $password;
            $user->password = Hash::make($password);
            $user->first_verification = 'yes';
            $user->save();

            return response()->json(
                [
                    'status' => true,
                    'message' => 'Password recovered successfully.'
                ],
                200
            );
        } catch (\Throwable $th) {
            Log::error('Error verifying code: ' . $th->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'An error occurred. Please try again later.'
                ],
                500
            );
        }
    }

    public function transferCredit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiverphone' => 'required|string|exists:users,phone',
            'amount_transfered' => 'required|string'

        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors()->first()
                ],
                422
            );
        }


        try {
            $receiverphone = $request->input('receiverphone');
            $amount_transfered = $request->input('amount_transfered');

            $user = $request->user();

            if ($user->phone_number === $receiverphone) {

                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Oops, You can\'t make transfer to yourself, please try another number'
                    ]
                );
            }


            $checkReceiverPhone = User::where('phone_number', '=', $receiverphone)->first();

            if ($checkReceiverPhone) {

                // $checkSenderPhone = AppUser::where('phone_number', '=', $senderphone)->first();

                // $sender = AppUser::find($checkSenderPhone->id);

                if ($user->wallet_balance > $amount_transfered) {

                    $user->wallet_balance = $user->wallet_balance - $amount_transfered;

                    if ($user->save()) {

                        // $receiver = AppUser::find($checkReceiverPhone->id);

                        // $checkReceiverPhone->wallet_balance = $checkReceiverPhone->wallet_balance + $amount_transfered;

                        // if ($checkReceiverPhone->save()) {

                        //     $wallet = new Wallet();

                        //     $wallet->sender_phone = $user->phone_number;
                        //     $wallet->sender_img = $user->prof_image;
                        //     $wallet->sender_firstname = $user->first_name;

                        //     $wallet->receiver_phone = $receiverphone;
                        //     $wallet->receiver_img = $checkReceiverPhone->prof_image;
                        //     $wallet->receiver_firstname = $checkReceiverPhone->first_name;

                        //     // $wallet->funded_account = $voucher->amount;
                        //     $wallet->trans_amount = $amount_transfered;


                        //     if ($wallet->save()) {
                        //         return response()->json(
                        //             [
                        //                 'status' => true,
                        //                 'message' => 'Transfer was successful'
                        //             ]
                        //         );
                        //     } else {

                        //         return response()->json(
                        //             [
                        //                 'status' => true,
                        //                 'message' => 'Transfer failed, please try again'
                        //             ]
                        //         );
                        //     }
                        // } else {

                        //     $user->wallet_balance = $user->wallet_balance + $amount_transfered;
                        //     $user->save();

                        //     return response()->json(
                        //         [
                        //             'status' => false,
                        //             'message' => 'Oops! Transfer failed, please try again'
                        //         ]
                        //     );
                        // }
                    } else {

                        return response()->json(
                            [
                                'status' => false,
                                'message' => 'Oops! Transfer failed, please try again'
                            ]
                        );
                    }
                } else {

                    return response()->json(
                        [
                            'status' => false,
                            'message' => 'Oops! Insufient fund, please top up your wallet to enable you make transfer'
                        ]
                    );
                }
            } else {

                return response()->json(
                    [
                        'status' => false,
                        'message' => 'This user is not registerd in our database, please check the number and try again'
                    ]
                );
            }
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Error: ' . $th->getMessage()
                ]
            );
        }
    }

    public function setTransactionPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string',
          
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors()->first()
                ],
                422
            );
        }

        try {
            // $email = $request->input('email');
            $pin = $request->input('pin');

            $user = $request->user();

            // $user->password = $password;
            $user->pin = $pin;
            $user->save();

            return response()->json(
                [
                    'status' => true,
                    'message' => 'Pin saved successfully.'
                ],
                200
            );
        } catch (\Throwable $th) {
            Log::error('Error verifying code: ' . $th->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'An error occurred. Please try again later.'
                ],
                500
            );
        }
    }

    public function sendChangePinVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors()->first()
                ],
                422
            );
        }

        try {
            $email = $request->input('email');
            // $code = Str::random(4, '0123456789');
            // $code = Random::number(4);
            $code = rand(100000, 999999);

            // Update user with the verification code and expiration time
            $user = User::where('email', $email)->first();
            $user->verification_code = $code;
            $user->verification_code_expires_at = now()->addMinutes(15); // Code expires in 15 minutes
            $user->save();

            // Get the user's IP address
            $ip = request()->ip();

            // Get the user's device information
            $agent = new Agent();
            $device = $agent->device();
            $platform = $agent->platform();
            $browser = $agent->browser();
            $deviceInfo = "{$device} ({$platform}, {$browser})";
            // Send the code via email
            Mail::to($email)->send(new EmailChangePin($code, $deviceInfo, $ip));

            return response()->json([
                'status' => true,
                'message' => 'Verification code sent to your email! ' . $email
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error sending verification code: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'An error occurred. Please try again later. ' . $th->getMessage()], 500);
        }
    }




    public function testMail(){

        try {
            Mail::to('jafars4ab@gmail.com')->send(new EmailVerification('1234567'));
        }catch (RequestException $e) {

            // Handle exceptions that occur during the HTTP request
            Log::error("Request for email  failed" . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        } catch (\Exception $e) {

            Log::error("Request for email general error: " . $e->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Something went wrong,  please try again'
                ],
                422
            );
        }
        
    }


    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldPassword' => 'string|required',
            'password' => [
                'required',
                'string',
                'min:6',
                // 'regex:/[A-Z]/', // Must contain an uppercase letter
                // 'regex:/[0-9]/', // Must contain a number
                // 'regex:/[@$!%*?&#]/' // Must contain a special character
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // $email = $request->input('email');
            $password = $request->input('password');
            $oldPassword = $request->input('oldPassword');

            // Retrieve the user by email
            $user = $request->user();

            // if (!$user) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'User not found.'
            //     ], 404);
            // }
            if (!Hash::check($oldPassword, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please enter the correct old password'
                ], 404);
            }


            // $user->password = $password;
            // $user->save();
            $user->password = Hash::make($password);
            $user->save();

            return response()->json(
                [
                    'status' => true,
                    'message' => 'Password changed successfully.'
                ],
                200
            );
        } catch (\Throwable $th) {
            Log::error('Error verifying code: ' . $th->getMessage());
            return response()->json(
                [
                    'status' => false,
                    'message' => 'An error occurred. Please try again later.'
                ],
                500
            );
        }
    }


}
