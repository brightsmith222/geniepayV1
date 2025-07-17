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
use App\Services\NinePsbService;
use App\Models\VirtualAccount;
use Illuminate\Support\Str;




use Illuminate\Support\Facades\Mail;


class UserController extends Controller
{

    protected $ninePsb;

public function __construct(NinePsbService $ninePsb)
{
    $this->ninePsb = $ninePsb;
}


public function user(Request $request)
{
    try {
        $user = $request->user();

        $userData = [
            'id'               => $user->id,
            'email'            => $user->email,
            'full_name'        => $user->full_name,
            'wallet_balance'   => $user->wallet_balance,
            'phone_number'     => $user->phone_number,
            'account_reference'=> $user->account_reference,
            'username'         => $user->username,
            'image'            => $user->image ? asset('storage/' . $user->image) : null,
        ];

        return response()->json(
            [
                'status' => true,
                'data' => $userData,
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
    }
}

    public function registerUser(Request $request)
{
    $isReferralEnabled = GeneralSettings::where('name', 'referral')->value('is_enabled');

    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255|unique:users',
        'full_name' => 'string|required',
        'username' => 'required|string|max:255|unique:users',
        'phone_number' => 'required|string|min:11|max:11|unique:users',
        'ref_code' => 'nullable|string',
        'password' => 'required|string|min:8',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    try {
        $user = new User();
        $user->full_name = $request->full_name;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;
        $user->username = $request->username;
        $user->password = Hash::make($request->password);

        if ($request->ref_code) {
            $referrer = User::where('username', $request->ref_code)->first();
            if ($referrer) {
                $user->referred_by = $referrer->id;
            }
        }

        $user->referral_bonus_eligible = $isReferralEnabled;

        $user->save();

        $code = rand(100000, 999999);
        $user->verification_code = $code;
        $user->verification_code_expires_at = now()->addMinutes(15);
        $user->save();

        Mail::to($user->email)->send(new EmailVerification($code));

        return response()->json([
            'status' => true,
            'message' => 'Registration successful! Please check your email for verification code.',
        ], 200);
    } catch (\Throwable $th) {
        Log::error('Registration error: ' . $th->getMessage());
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

public function uploadProfileImage(Request $request)
{
    $validator = Validator::make($request->all(), [
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:3048', // 2MB max
    ]);

    Log::info('Profile image upload request', [
        'user_id' => $request->user()->id,
        'file' => $request->file('image') ? $request->file('image')->getClientOriginalName() : null
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    try {
        $user = $request->user();

        // Store the image in the 'public/profile_images' directory
        $path = $request->file('image')->store('profile_images', 'public');

        Log::info('Profile image stored at', [
            'path' => $path,
            'user_id' => $user->id
        ]);

        // Save the image path to the user
        $user->image = $path;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile image uploaded successfully.',
            'image_url' => asset('storage/' . $path)
        ]);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to upload image: ' . $th->getMessage()
        ], 500);
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
        ], [
            'email.exists' => 'Email does not exist, please create an account.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $email = $request->input('email');
            $password = $request->input('password');

            $user = User::where('email', '=', $email)->first();

            if ($user) {
                // Check account status
                if ($user->status === 'suspended') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Your account has been suspended. Please contact support.'
                    ], 403);
                }
                if ($user->status === 'blocked') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Your account has been blocked. Please contact support.'
                    ], 403);
                }
                if ($user->status !== 'active') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Your account is not active. Please contact support.'
                    ], 403);
                }

                $userPassword = $user->password;
                $epin = $user->pin;

                if (Hash::check($password, $userPassword)) {
                    $token = $user->createToken($email)->plainTextToken;
                    $user->last_login_at = now();
                    $user->fcm_token = $request->fcm_token;
                    $user->save();
                    return response()->json([
                        'status' => true,
                        'verified' => $user->first_verification,
                        'epin' => $epin,
                        'token' => $token
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Wrong password'
                    ], 422);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'No account found for this email, please create an account'
                ], 422);
            }
        } catch (\Throwable $th) {
            Log::error('Error response: ' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error response: ' . $th->getMessage()
            ], 422);
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
                'message' => 'An error occurred. Please try again later. ' . $th->getMessage()
            ], 500);
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
                'message' => 'An error occurred. Please try again later. ' . $th->getMessage()
            ], 500);
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
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Check if the code matches and is not expired
        if (
            $user->verification_code === $code &&
            $user->verification_code_expires_at &&
            $user->verification_code_expires_at >= now()
        ) {
            // Code is valid and not expired, proceed with verification
            $user->status = 'active'; 
            $user->verification_code = null;
            $user->verification_code_expires_at = null;
            if ($request->input('first_time') === 'yes') {
                $user->first_verification = 'yes';
            }
            $user->save();

            // ✅ Step 1: Check if user already has a virtual account
            $existingAccount = VirtualAccount::where('user_id', $user->id)->first();
            if ($existingAccount) {
                return response()->json([
                    'status' => true,
                    'message' => 'Account successfully verified.'
                ], 200);
            }

            // ✅ Step 2: Create virtual account
            $reference = now()->format('YmdHis') . Str::random(6);
            $payload = [
                'transaction' => ['reference' => $reference],
                'order' => [
                    'amount' => 0,
                    'currency' => 'NGN',
                    'description' => 'User Static Account',
                    'country' => 'NGA',
                    'amounttype' => 'ANY',
                ],
                'customer' => [
                    'account' => [
                        'name' => $user->full_name,
                        'type' => 'STATIC',
                    ],
                ],
            ];

            try {
                $response = $this->ninePsb->createVirtualAccount($payload);

                // Check if the response contains the expected data
                if (
                    isset($response['customer']['account']['number']) &&
                    !empty($response['customer']['account']['number'])
                ) {
                    VirtualAccount::create([
                        'user_id' => $user->id,
                        'reference' => $reference,
                        'type' => 'STATIC',
                        'account_number' => $response['customer']['account']['number'],
                        'bank' => '9Payment Service Bank',
                        'raw_response' => json_encode($response),
                    ]);

                    return response()->json([
                        'status' => true,
                        'message' => 'Account successfully verified.'
                    ], 200);
                } else {
                    Log::error('Virtual account creation failed: Invalid response', ['response' => $response]);
                    return response()->json([
                        'status' => false,
                        'message' => 'Verification succeeded'
                    ], 500);
                }
            } catch (\Throwable $e) {
                Log::error('Virtual account creation failed: ' . $e->getMessage());
                return response()->json([
                    'status' => false,
                    'message' => 'Verification succeeded'
                ], 500);
            }
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
                'message' => 'An error occurred. Please try again later. ' . $th->getMessage()
            ], 500);
        }
    }




    public function testMail()
    {

        try {
            Mail::to('jafars4ab@gmail.com')->send(new EmailVerification('1234567'));
        } catch (RequestException $e) {

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

public function getReferredUsers(Request $request)
{
    $user = $request->user();

    // Get all users where referred_by matches the current user's id
    $referredUsers = User::where('referred_by', $user->id)
        ->select('id', 'username', 'full_name', 'email', 'phone_number', 'created_at', 'referral_bonus_given')
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'joined_at' => $user->created_at->format('F j, Y'),
                'status' => $user->referral_bonus_given ? 'Rewarded' : 'Pending',
            ];
        });

    $referralSetting = GeneralSettings::where('name', 'referral')->first();
    $isReferralEnabled = $referralSetting && $referralSetting->is_enabled;

    if ($referredUsers->count() > 0) {
        // Always show the list if user has referred users, regardless of referral status
        return response()->json([
            'status' => true,
            'count' => $referredUsers->count(),
            'referred_users' => $referredUsers
        ]);
    } else {
        // No referred users
        if (!$isReferralEnabled) {
            return response()->json([
                'status' => false,
                'message' => 'Referral is not currently available.'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'We are not currently offering referral bonuses.'
            ]);
        }
    }
}

    public function getReferralBonus()
    {
        $setting = GeneralSettings::where('name', 'referral')->first();

        if ($setting && $setting->is_enabled) {
            return response()->json([
                'status' => true,
                'referral_bonus' => (float) $setting->referral_bonus,
            ]);
        } else {
            return response()->json([
                'status' => false,
                'referral_bonus' => 0,
                'message' => 'We are not currently offering referral bonuses.'
            ]);
        }
    }

    public function getVirtualCharge()
{
    $setting = GeneralSettings::where('name', 'virtual_charge')->first();

    if ($setting && $setting->is_enabled) {
        return response()->json([
            'status' => true,
            'virtual_charge' => (float) $setting->referral_bonus,
        ]);
    } else {
        return response()->json([
            'status' => false,
            'virtual_charge' => 0,
            'message' => 'All deposits are free at the moment.'
        ]);
    }
}

}
