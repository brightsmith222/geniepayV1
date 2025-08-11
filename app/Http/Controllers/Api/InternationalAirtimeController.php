<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Services\VtpassService;
use Illuminate\Support\Facades\Log;
use libphonenumber\NumberParseException;
use App\Helpers\ReloadlyHelper;
use App\MyFunctions;
use App\Services\PercentageService;
use Illuminate\Support\Facades\DB;
use App\Services\ReferralService;
use App\Jobs\FetchGiftCardPin;
use Illuminate\Support\Facades\Mail;
use App\Models\Transactions;
use App\Models\WalletTransactions;
use App\Services\PinService;
use App\Mail\TransactionSuccessMail;


class InternationalAirtimeController extends Controller
{
    public function detectCountry(Request $request)
    {
        $request->validate([
            'phone' => 'required'
        ]);

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneProto = $phoneUtil->parse($request->phone, null); // Null for auto-detect
            $countryCode = $phoneUtil->getRegionCodeForNumber($phoneProto); // e.g. "NG"
            return response()->json(['status' => true, 'country_code' => $countryCode]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Invalid phone number']);
        }
    }

    public function getAllOperators()
    {
        try {
            // Step 1: Get access token
            $token = ReloadlyHelper::getAccessTokenForAirtime();
            Log::info('getAllOperators: Access token retrieved');

            // Step 2: Call Reloadly API to get all operators
            $url = "https://topups-sandbox.reloadly.com/operators";

            $response = Http::withToken($token)
                ->withoutVerifying()
                ->get($url);

            Log::info('getAllOperators: Response from Reloadly', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            if (!$response->ok()) {
                Log::error('getAllOperators: Failed to fetch operators', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json(['status' => false, 'message' => 'Failed to retrieve operators']);
            }

            $operators = $response->json();
            Log::info('getAllOperators: Operators fetched successfully', ['count' => count($operators)]);

            return response()->json([
                'status' => true,
                'operators' => $operators
            ]);
        } catch (\Exception $e) {
            Log::error('getAllOperators: Exception occurred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => false, 'message' => 'Something went wrong']);
        }
    }


    public function getAirtimeOperators(Request $request, PercentageService $percentageService)
    {
        $request->validate(['phone' => 'required']);

        Log::info('getAirtimeOperators: Received request', ['phone' => $request->phone]);

        $countryISO = ReloadlyHelper::getCountryISO($request->phone);
        Log::info('getAirtimeOperators: Detected country ISO', ['countryISO' => $countryISO]);

        if (!$countryISO) {
            Log::warning('getAirtimeOperators: Invalid phone number', ['phone' => $request->phone]);
            return response()->json(['status' => false, 'message' => 'Invalid phone number']);
        }

        $token = ReloadlyHelper::getAccessTokenForAirtime();

        $url = "https://topups-sandbox.reloadly.com/operators/countries/{$countryISO}";
        Log::info('getAirtimeOperators: Fetching operators from Reloadly', ['url' => $url]);

        $response = Http::withToken($token)->withoutVerifying()->get($url);
        Log::info('getAirtimeOperators: Response from Reloadly', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if (!$response->ok()) {
            Log::error('getAirtimeOperators: Failed to fetch operators', [
                'countryISO' => $countryISO,
                'response' => $response->body()
            ]);
            return response()->json(['status' => false, 'message' => 'Failed to fetch operators']);
        }

        $airtimeOperators = collect($response->json())
            ->filter(function ($op) {
                // Only airtime operators (not data/bundle/pin)
                return !$op['bundle'] && !$op['data'] && !$op['pin'];
            })
            ->map(function ($op) use ($percentageService) {
                $denominationType = $op['denominationType'] ?? '';
                $senderSymbol = $op['senderCurrencySymbol'] ?? '$';
                $destinationSymbol = $op['destinationCurrencySymbol'] ?? '₦';
                $supportsLocal = $op['supportsLocalAmounts'] ?? false;
                $supportsGeo = $op['supportsGeographicalRechargePlans'] ?? false;

                // Define and extend $predefined INSIDE the map for each operator
                $predefined = [5, 10, 15, 20, 25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500, 1000];
                $max = $op['maxAmount'] ?? $op['localMaxAmount'] ?? null;
                if ($max !== null && $max > 1000) {
                    $last = 1000;
                    while (($last + 1000) <= $max) {
                        $last += 1000;
                        $predefined[] = $last;
                    }
                    if ($last < $max) {
                        $predefined[] = $max;
                    }
                }

                // FIXED denomination
                if ($denominationType === 'FIXED') {
                    // If supportsGeographicalRechargePlans, group by unique plan
                    if ($supportsGeo && !empty($op['geographicalRechargePlans'])) {
                        $amount_pairs = [];
                        $userAmounts = [];
                        $operatorAmounts = [];
                        $seen = [];
                        foreach ($op['geographicalRechargePlans'] as $plan) {
                            $fixedAmounts = $plan['fixedAmounts'] ?? [];
                            $localAmounts = $plan['localAmounts'] ?? [];
                            $planNames = $plan['localFixedAmountsPlanNames'] ?? [];
                            $count = min(count($fixedAmounts), count($localAmounts));
                            for ($i = 0; $i < $count; $i++) {
                                $operatorAmount = $localAmounts[$i];
                                $userAmountRaw = $fixedAmounts[$i];
                                $userAmount = round($percentageService->calculateIntAirtimeDiscountedAmount($userAmountRaw), 2);

                                // Use plan name for this amount if available
                                $planName = '';
                                if (is_array($planNames)) {
                                    $key = number_format($operatorAmount, 2, '.', '');
                                    $planName = $planNames[$key] ?? '';
                                } elseif (is_string($planNames)) {
                                    $planName = $planNames;
                                }

                                // Create a unique key to avoid duplicates
                                $pairKey = $operatorAmount . '-' . $userAmount . '-' . $planName;
                                if (isset($seen[$pairKey])) {
                                    continue;
                                }
                                $seen[$pairKey] = true;

                                $operatorAmounts[] = $operatorAmount;
                                $userAmounts[] = $userAmount;

                                $pair = "{$destinationSymbol}{$operatorAmount} - {$senderSymbol}" . number_format($userAmount, 2);
                                if ($planName) {
                                    $pair .= " ({$planName})";
                                }
                                $amount_pairs[] = $pair;
                            }
                        }
                        return [
                            'name' => $op['name'] ?? '',
                            'productId' => $op['operatorId'] ?? null,
                            'denominationType' => $denominationType,
                            'senderCurrencySymbol' => $op['senderCurrencySymbol'] ?? null,
                            'destinationCurrencySymbol' => $op['destinationCurrencySymbol'] ?? null,
                            'userAmount' => $userAmounts,
                            'operatorAmount' => $operatorAmounts,
                            'amount_pairs' => $amount_pairs,
                            'supportsGeographicalRechargePlans' => $supportsGeo,
                            'supportsLocalAmounts' => $supportsLocal,
                            'logo' => $op['logoUrls'][0] ?? null,
                        ];
                    } else {
                        // No geo plans: use fixedAmounts/localFixedAmounts
                        $fixedAmounts = $op['fixedAmounts'] ?? [];
                        $localFixedAmounts = $op['localFixedAmounts'] ?? [];

                        // If localFixedAmounts is empty but fixedAmounts exists, use fixedAmounts for both
                        if (empty($localFixedAmounts) && !empty($fixedAmounts)) {
                            $localFixedAmounts = $fixedAmounts;
                        }

                        $operatorAmounts = [];
                        $userAmounts = [];
                        $amount_pairs = [];

                        $count = min(count($fixedAmounts), count($localFixedAmounts));
                        for ($i = 0; $i < $count; $i++) {
                            $operatorAmount = $localFixedAmounts[$i];
                            $userAmountRaw = $fixedAmounts[$i];
                            $userAmount = round($percentageService->calculateIntAirtimeDiscountedAmount($userAmountRaw), 2);

                            $operatorAmounts[] = $operatorAmount;
                            $userAmounts[] = $userAmount;
                            $amount_pairs[] = "({$destinationSymbol}{$operatorAmount} - {$senderSymbol}{$userAmount})";
                        }
                        return [
                            'name' => $op['name'] ?? '',
                            'productId' => $op['operatorId'] ?? null,
                            'denominationType' => $denominationType,
                            'senderCurrencySymbol' => $op['senderCurrencySymbol'] ?? null,
                            'destinationCurrencySymbol' => $op['destinationCurrencySymbol'] ?? null,
                            'userAmount' => $userAmounts,
                            'operatorAmount' => $operatorAmounts,
                            'amount_pairs' => $amount_pairs,
                            'supportsGeographicalRechargePlans' => $supportsGeo,
                            'supportsLocalAmounts' => $supportsLocal,
                            'logo' => $op['logoUrls'][0] ?? null,
                        ];
                    }
                }
                // RANGE denomination
                elseif ($denominationType === 'RANGE') {
                    $minAmount = $op['minAmount'] ?? null;
                    $maxAmount = $op['maxAmount'] ?? null;
                    $localMinAmount = $op['localMinAmount'] ?? null;
                    $localMaxAmount = $op['localMaxAmount'] ?? null;

                    // If localMin/Max are missing, use min/max for both
                    if ($localMinAmount === null || $localMaxAmount === null) {
                        $localMinAmount = $minAmount;
                        $localMaxAmount = $maxAmount;
                    }

                    $operatorAmounts = [];
                    $userAmounts = [];
                    $amount_pairs = [];

                    foreach ($predefined as $amount) {
                        if (
                            $minAmount !== null && $maxAmount !== null &&
                            $localMinAmount !== null && $localMaxAmount !== null &&
                            $amount >= $minAmount && $amount <= $maxAmount &&
                            $amount >= $localMinAmount && $amount <= $localMaxAmount
                        ) {
                            $operatorAmounts[] = $amount; // original, not discounted
                            $userAmount = round($percentageService->calculateIntAirtimeDiscountedAmount($amount), 2);
                            $userAmounts[] = $userAmount;
                            $amount_pairs[] = "({$destinationSymbol}{$amount} - {$senderSymbol}{$userAmount})";
                        }
                    }
                    return [
                        'name' => $op['name'] ?? '',
                        'productId' => $op['operatorId'] ?? null,
                        'denominationType' => $denominationType,
                        'senderCurrencySymbol' => $op['senderCurrencySymbol'] ?? null,
                        'destinationCurrencySymbol' => $op['destinationCurrencySymbol'] ?? null,
                        'userAmount' => $userAmounts,
                        'operatorAmount' => $operatorAmounts,
                        'amount_pairs' => $amount_pairs,
                        'supportsGeographicalRechargePlans' => $supportsGeo,
                        'supportsLocalAmounts' => $supportsLocal,
                        'logo' => $op['logoUrls'][0] ?? null,
                    ];
                }
                // fallback for other types
                return [
                    'name' => $op['name'] ?? '',
                    'productId' => $op['operatorId'] ?? null,
                    'denominationType' => $denominationType,
                    'senderCurrencySymbol' => $op['senderCurrencySymbol'] ?? null,
                    'destinationCurrencySymbol' => $op['destinationCurrencySymbol'] ?? null,
                    'userAmount' => [],
                    'operatorAmount' => [],
                    'amount_pairs' => [],
                    'supportsGeographicalRechargePlans' => $supportsGeo,
                    'supportsLocalAmounts' => $supportsLocal,
                    'logo' => $op['logoUrls'][0] ?? null,
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'countryISO' => $countryISO,
            'airtimeOperators' => $airtimeOperators,
        ]);
    }



    public function getDataOperators(Request $request, PercentageService $percentageService)
    {
        $request->validate(['phone' => 'required']);

        Log::info('getDataOperators: Received request', ['phone' => $request->phone]);

        $countryISO = ReloadlyHelper::getCountryISO($request->phone);
        Log::info('getDataOperators: Detected country ISO', ['countryISO' => $countryISO]);

        if (!$countryISO) {
            Log::warning('getDataOperators: Invalid phone number', ['phone' => $request->phone]);
            return response()->json(['status' => false, 'message' => 'Invalid phone number']);
        }

        $token = ReloadlyHelper::getAccessTokenForAirtime();

        $url = "https://topups-sandbox.reloadly.com/operators/countries/{$countryISO}";
        Log::info('getDataOperators: Fetching operators from Reloadly', ['url' => $url]);

        $response = Http::withToken($token)->withoutVerifying()->get($url);
        Log::info('getDataOperators: Response from Reloadly', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if (!$response->ok()) {
            Log::error('getDataOperators: Failed to fetch operators', [
                'countryISO' => $countryISO,
                'response' => $response->body()
            ]);
            return response()->json(['status' => false, 'message' => 'Failed to fetch operators']);
        }

        $dataOperators = collect($response->json())
            ->filter(function ($op) {
                // Filter out operators that do not support data bundles or pins
                return ($op['bundle'] ?? false) || ($op['data'] ?? false) || ($op['pin'] ?? false);
            })
            ->map(function ($op) use ($percentageService) {
                $denominationType = $op['denominationType'] ?? '';
                $destinationSymbol = $op['destinationCurrencySymbol'] ?? '$';
                $senderSymbol = $op['senderCurrencySymbol'] ?? '₦';
                $supportsLocal = $op['supportsLocalAmounts'] ?? false;
                $supportsGeo = $op['supportsGeographicalRechargePlans'] ?? false;



                // Define and extend $predefined INSIDE the map for each operator
                $predefined = [5, 10, 15, 20, 25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500, 1000];
                $max = $op['maxAmount'] ?? $op['localMaxAmount'] ?? null;
                if ($max !== null && $max > 1000) {
                    $last = 1000;
                    while (($last + 1000) <= $max) {
                        $last += 1000;
                        $predefined[] = $last;
                    }
                    if ($last < $max) {
                        $predefined[] = $max;
                    }
                }

                // FIXED denomination
                if ($denominationType === 'FIXED') {
                    if ($supportsGeo && !empty($op['geographicalRechargePlans'])) {
                        $amount_pairs = [];
                        $userAmounts = [];
                        $operatorAmounts = [];
                        $seen = [];
                        foreach ($op['geographicalRechargePlans'] as $plan) {
                            $fixedAmounts = $plan['fixedAmounts'] ?? [];
                            $localAmounts = $plan['localAmounts'] ?? [];
                            $planNames = $plan['localFixedAmountsPlanNames'] ?? [];
                            $count = min(count($fixedAmounts), count($localAmounts));
                            for ($i = 0; $i < $count; $i++) {
                                $operatorAmount = $localAmounts[$i];
                                $userAmountRaw = $fixedAmounts[$i];
                                $userAmount = round($percentageService->calculateInternationalDiscountedAmount($userAmountRaw), 2);

                                $planName = '';
                                if (is_array($planNames)) {
                                    $key = number_format($operatorAmount, 2, '.', '');
                                    $planName = $planNames[$key] ?? '';
                                } elseif (is_string($planNames)) {
                                    $planName = $planNames;
                                }

                                $pairKey = $operatorAmount . '-' . $userAmount . '-' . $planName;
                                if (isset($seen[$pairKey])) {
                                    continue;
                                }
                                $seen[$pairKey] = true;

                                $operatorAmounts[] = $operatorAmount;
                                $userAmounts[] = $userAmount;

                                $pair = "{$destinationSymbol}{$operatorAmount} - {$senderSymbol}" . number_format($userAmount, 2);
                                if ($planName) {
                                    $pair .= " ({$planName})";
                                }
                                $amount_pairs[] = $pair;
                            }
                        }
                        return [
                            'name' => $op['name'] ?? '',
                            'productId' => $op['operatorId'] ?? null,
                            'denominationType' => $denominationType,
                            'senderCurrencySymbol' => $op['senderCurrencySymbol'] ?? null,
                            'destinationCurrencySymbol' => $op['destinationCurrencySymbol'] ?? null,
                            'userAmount' => $userAmounts,
                            'operatorAmount' => $operatorAmounts,
                            'amount_pairs' => $amount_pairs,
                            'supportsGeographicalRechargePlans' => $supportsGeo,
                            'supportsLocalAmounts' => $supportsLocal,
                            'logo' => $op['logoUrls'][0] ?? null,
                        ];
                    } else {
                        $fixedAmounts = $op['fixedAmounts'] ?? [];
                        $localFixedAmounts = $op['localFixedAmounts'] ?? [];
                        $fixedAmountsDescriptions = $op['fixedAmountsDescriptions'] ?? [];
                        $localFixedAmountsDescriptions = $op['localFixedAmountsDescriptions'] ?? [];
                        $localFixedAmountsPlanNames = $op['localFixedAmountsPlanNames'] ?? [];

                        // Log the raw arrays for debugging
                        Log::info('FIXED ELSE: fixedAmounts', $fixedAmounts);
                        Log::info('FIXED ELSE: localFixedAmounts', $localFixedAmounts);
                        Log::info('FIXED ELSE: fixedAmountsDescriptions', $fixedAmountsDescriptions);
                        Log::info('FIXED ELSE: localFixedAmountsPlanNames', $localFixedAmountsPlanNames);

                        $useLocal = !empty($localFixedAmounts);
                        $operatorAmounts = $useLocal ? $localFixedAmounts : $fixedAmounts;
                        $userAmounts = [];
                        $amount_pairs = [];

                        $count = min(count($fixedAmounts), count($operatorAmounts));
                        for ($i = 0; $i < $count; $i++) {
                            $operatorAmount = $operatorAmounts[$i];
                            $userAmountRaw = $fixedAmounts[$i];
                            $userAmount = round($percentageService->calculateInternationalDiscountedAmount($userAmountRaw), 2);
                            $userAmounts[] = $userAmount;



                            if ($useLocal) {
                                $pair = "{$destinationSymbol}{$operatorAmount} - {$senderSymbol}" . number_format($userAmount, 2);
                                // Use plan name if available
                                $planName = '';
                                if (is_array($localFixedAmountsDescriptions)) {
                                    $key = number_format($operatorAmount, 2, '.', '');
                                    $planName = $localFixedAmountsDescriptions[$key] ?? '';
                                    Log::info('FIXED ELSE: PlanName lookup', [
                                        'operatorAmount' => $operatorAmount,
                                        'key' => $key,
                                        'planName' => $planName
                                    ]);
                                } elseif (is_string($fixedAmountsDescriptions)) {
                                    $planName = $fixedAmountsDescriptions;
                                    Log::info('FIXED ELSE: PlanName string', ['planName' => $planName]);
                                }
                                if ($planName) {
                                    $pair .= " ({$planName})";
                                }
                            } else {
                                $pair = "{$senderSymbol}" . number_format($userAmount, 2);
                                // Use description if available
                                $descKey = number_format($operatorAmount, 2, '.', '');
                                $desc = $fixedAmountsDescriptions[$descKey] ?? '';
                                Log::info('FIXED ELSE: Description lookup', [
                                    'operatorAmount' => $operatorAmount,
                                    'descKey' => $descKey,
                                    'desc' => $desc
                                ]);
                                if ($desc) {
                                    $pair .= " ({$desc})";
                                }
                            }
                            $amount_pairs[] = $pair;
                            Log::info('FIXED ELSE: Final amount_pair', ['pair' => $pair]);
                        }

                        return [
                            'name' => $op['name'] ?? '',
                            'productId' => $op['operatorId'] ?? null,
                            'denominationType' => $denominationType,
                            'senderCurrencySymbol' => $op['senderCurrencySymbol'] ?? null,
                            'destinationCurrencySymbol' => $op['destinationCurrencySymbol'] ?? null,
                            'userAmount' => $userAmounts,
                            'operatorAmount' => $operatorAmounts,
                            'amount_pairs' => $amount_pairs,
                            'supportsGeographicalRechargePlans' => $supportsGeo,
                            'supportsLocalAmounts' => $supportsLocal,
                            'logo' => $op['logoUrls'][0] ?? null,
                        ];
                    }
                }
                // RANGE denomination
                elseif ($denominationType === 'RANGE') {
                    $minAmount = $op['minAmount'] ?? null;
                    $maxAmount = $op['maxAmount'] ?? null;
                    $localMinAmount = $op['localMinAmount'] ?? null;
                    $localMaxAmount = $op['localMaxAmount'] ?? null;

                    if ($localMinAmount === null || $localMaxAmount === null) {
                        $localMinAmount = $minAmount;
                        $localMaxAmount = $maxAmount;
                    }

                    $operatorAmounts = [];
                    $userAmounts = [];
                    $amount_pairs = [];

                    foreach ($predefined as $amount) {
                        if (
                            $minAmount !== null && $maxAmount !== null &&
                            $localMinAmount !== null && $localMaxAmount !== null &&
                            $amount >= $minAmount && $amount <= $maxAmount &&
                            $amount >= $localMinAmount && $amount <= $localMaxAmount
                        ) {
                            $operatorAmounts[] = $amount;
                            $userAmount = round($percentageService->calculateInternationalDiscountedAmount($amount), 2);
                            $userAmounts[] = $userAmount;
                            $pair = "{$op['name']} - {$senderSymbol}" . number_format($userAmount, 2);
                            $amount_pairs[] = $pair;
                        }
                    }
                    return [
                        'name' => $op['name'] ?? '',
                        'productId' => $op['operatorId'] ?? null,
                        'denominationType' => $denominationType,
                        'senderCurrencySymbol' => $op['senderCurrencySymbol'] ?? null,
                        'destinationCurrencySymbol' => $op['destinationCurrencySymbol'] ?? null,
                        'userAmount' => $userAmounts,
                        'operatorAmount' => $operatorAmounts,
                        'amount_pairs' => $amount_pairs,
                        'supportsGeographicalRechargePlans' => $supportsGeo,
                        'supportsLocalAmounts' => $supportsLocal,
                        'logo' => $op['logoUrls'][0] ?? null,
                    ];
                }
                // fallback for other types
                return [
                    'name' => $op['name'] ?? '',
                    'productId' => $op['operatorId'] ?? null,
                    'denominationType' => $denominationType,
                    'senderCurrencySymbol' => $op['senderCurrencySymbol'] ?? null,
                    'destinationCurrencySymbol' => $op['destinationCurrencySymbol'] ?? null,
                    'userAmount' => [],
                    'operatorAmount' => [],
                    'amount_pairs' => [],
                    'supportsGeographicalRechargePlans' => $supportsGeo,
                    'supportsLocalAmounts' => $supportsLocal,
                    'logo' => $op['logoUrls'][0] ?? null,
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'countryISO' => $countryISO,
            'dataOperators' => $dataOperators,
        ]);
    }




    public function getOperatorById($operatorId)
    {
        try {
            $token = ReloadlyHelper::getAccessTokenForAirtime();
            Log::info('getOperatorById: Access token retrieved');

            $url = "https://topups-sandbox.reloadly.com/operators/{$operatorId}";
            Log::info('getOperatorById: Fetching operator details', ['url' => $url]);

            $response = Http::withToken($token)
                ->withoutVerifying()
                ->get($url);

            if (!$response->ok()) {
                Log::error('getOperatorById: Failed to fetch operator', [
                    'operatorId' => $operatorId,
                    'response' => $response->body()
                ]);
                return response()->json(['status' => false, 'message' => 'Failed to fetch operator details']);
            }

            $data = $response->json();
            Log::info('getOperatorById: Operator details fetched', ['operator' => $data]);

            return response()->json(['status' => true, 'operator' => $data]);
        } catch (\Exception $e) {
            Log::error('getOperatorById: Exception occurred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }


    public function getOperatorDetails($id)
    {
        $token = ReloadlyHelper::getAccessTokenForAirtime();

        $response = Http::withToken($token)->get("https://topups.reloadly.com/operators/{$id}");

        if (!$response->ok()) {
            return response()->json(['status' => false, 'message' => 'Operator not found']);
        }

        return response()->json([
            'status' => true,
            'operator' => $response->json()
        ]);
    }

    public function purchase(Request $request, PinService $pinService)
    {
        $request->validate([
            'operatorId' => 'required|numeric',
            'user_amount' => 'required|numeric',
            'operator_amount' => 'required|numeric',
            'phone' => 'required',
            'countryCode' => 'required|string|size:2',
            'pin' => 'required|string|min:4|max:6',
            'image' => 'nullable',
            'supportsLocalAmounts' => 'required|boolean',
            'serviceType' => 'nullable|string',
        ]);

        $useLocalAmount = $request->supportsLocalAmounts;
        $serviceType = $request->serviceType ?? '';

        $pin = $request->input('pin');
        $user = $request->user();


        if (!$pinService->checkPin($user, $pin)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid transaction pin.'
            ], 403);
        }

        $userAmount = $request->user_amount;
        $operatorAmount = $request->operator_amount;

        if ($user->wallet_balance < $userAmount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient wallet balance.'
            ], 400);
        }

        try {
            DB::beginTransaction();
            // Step 1: Prepare values
            $transactionId = "txn_" . MyFunctions::generateRequestId();
            $operatorId = $request->operatorId;
            $amount = $operatorAmount;
            $recipientPhone = $request->phone;
            $countryCode = strtoupper($request->countryCode);

            // Step 2: Get access token
            $token = ReloadlyHelper::getAccessTokenForAirtime();
            Log::info('purchaseAirtime: Access token retrieved');

            // Step 3: Prepare payload
            $payload = [
                "operatorId" => $operatorId,
                "amount" => $amount,
                "useLocalAmount" => $useLocalAmount,
                "recipientPhone" => [
                    "countryCode" => $countryCode,
                    "number" => $recipientPhone
                ],
                "customIdentifier" => $transactionId
            ];

            Log::info('purchaseAirtime: Sending request to Reloadly', ['payload' => $payload]);

            // Step 4: Send request
            $response = Http::withToken($token)
                ->withoutVerifying()
                ->post("https://topups-sandbox.reloadly.com/topups", $payload);

            if (!$response->ok()) {
                $body = $response->json();
                $errorMessage = $body['message'] ?? 'Purchase failed';
                Log::error('purchaseAirtime: Reloadly API error', ['body' => $response->body()]);
                return response()->json(['status' => false, 'message' => $errorMessage]);
            }

            $data = $response->json();
            Log::info('purchaseAirtime: Airtime purchased successfully', ['response' => $data]);

            // Optionally store the transaction in DB here...

            $status = strtoupper($data['status'] ?? '');

            if (in_array($status, ['REFUNDED', 'FAILED'])) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => $data['status'] ?? 'Purchase failed. Status: ' . $status,
                    'details' => $data
                ], 400);
            }

            if (in_array($status, ['SUCCESSFUL', 'PROCESSING', 'PENDING'])) {
                // Debit user
                $balanceBefore = $user->wallet_balance;
                $balanceAfter = $balanceBefore - $userAmount;
                $user->wallet_balance = $balanceAfter;
                $user->save();

                // Save transaction
                $walletTrans = new WalletTransactions();
                $walletTrans->trans_type = 'debit';
                $walletTrans->user_id = $user->id;
                $walletTrans->user = $user->username;
                $walletTrans->amount = $userAmount;
                $walletTrans->service = $serviceType;
                $walletTrans->status = 'Successful';
                $walletTrans->transaction_id = (string) $data['transactionId'];
                $walletTrans->balance_before = $balanceBefore;
                $walletTrans->balance_after = $balanceAfter;
                $walletTrans->save();

                $transaction = new Transactions();
                $transaction->user_id = $user->id;
                $transaction->username = $user->username;
                $transaction->amount = $userAmount;
                $transaction->phone_number = $recipientPhone;
                $transaction->service_provider = $data['operatorName']  ?? '';
                $transaction->provider_id = $request->operatorId;
                $transaction->status = ucfirst(strtolower($status));
                $transaction->service = $serviceType;
                $transaction->image = $request->image;
                $transaction->transaction_id = (string) $data['transactionId'] ?? '';
                $transaction->reference = $data['customIdentifier'] ?? '';
                $transaction->plan_id = $request->operatorId;
                $transaction->epin = $data['pinDetail']['code'] ?? null;
                $transaction->serial = $data['pinDetail']['serial'] ?? null;
                // $transaction->instructions = $instructions;
                $transaction->which_api = 'reloadly';
                $transaction->save();

                if (in_array($status, ['SUCCESSFUL'])) {
                    (new ReferralService())->handleFirstTransactionBonus($user, 'international airtime', $userAmount);
                    if ($data['pinDetail'] && isset($data['pinDetail']['code'])) {
                        // Send success email
                        $details = [
                            'Transaction ID' =>  $data['transactionId'] ?? '',
                            'Amount' => number_format($userAmount, 2),
                            'Phone Number' => $recipientPhone,
                            'Service Provider' => $data['operatorName'] ?? '',
                            'Status' => ucfirst(strtolower($status)),
                            'PIN Code' => $data['pinDetail']['code'] ?? null,
                            'Serial' => $data['pinDetail']['serial'] ?? null
                        ];
                        Mail::to($user->email)->send(new TransactionSuccessMail($details, 'Airtime Purchase Details', 'Your Airtime Purchase Details'));
                    }
                } elseif (in_array($status, ['PROCESSING', 'PENDING'])) {
                    // Queue job to fetch PIN later
                    FetchGiftCardPin::dispatch($data['transactionId'], $transaction->id)->delay(now()->addSeconds(10));
                }


                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Purchased successful',
                    'data' => $transaction,
                    'card_details' => [
                        'pin_code' => $data['pinDetail']['code'] ?? null,
                        'serial' => $data['pinDetail']['serial'] ?? null
                    ]
                    // 'card_details' => $cardDetails['data'] ?? null,
                    // 'card_instructions' => $cardInstructions['data']['verbose'] ?? null
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Unexpected status: ' . $status,
                    'details' => $data
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('purchaseAirtime: Exception occurred', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }
}
