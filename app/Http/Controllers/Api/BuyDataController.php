<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use App\Services\PercentageService;
use App\Models\DataTopupPercentage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\MyFunctions;
use App\Services\BeneficiaryService;
use App\Services\PinService;


class BuyDataController extends BaseDataController
{
    public function getDataPlan(Request $request, PercentageService $percentageService)
    {
        try {
            $apiService = $this->getActiveApiService();

            if (!$apiService) {
                return response()->json([
                    'status' => false,
                    'message' => 'No active data service available'
                ], 503);
            }

            // Define a unique cache key based on the active API service
            $cacheKey = "data_plans:{$apiService->getServiceName()}";

            // Check if data plans are cached
            $allPlans = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($apiService) {
                // Fetch plans by API
                if ($apiService instanceof \App\Services\ArtxDataService) {
                    $mtnPlans     = $apiService->getDataPlans(1);
                    $gloPlans     = $apiService->getDataPlans(2);
                    $airtelPlans  = $apiService->getDataPlans(3);
                    $mobile9Plans = $apiService->getDataPlans(6);
                    return array_merge($mtnPlans, $gloPlans, $airtelPlans, $mobile9Plans);
                }

                if ($apiService instanceof \App\Services\GladDataService) {
                    // Fetch plans for each network
                    $mtnPlans     = $apiService->getDataPlans(1); // MTN
                    $gloPlans     = $apiService->getDataPlans(2); // GLO
                    $airtelPlans  = $apiService->getDataPlans(3); // Airtel
                    $mobile9Plans = $apiService->getDataPlans(6); // 9Mobile
                    return array_merge($mtnPlans, $gloPlans, $airtelPlans, $mobile9Plans);
                }
            });

            Log::info ('This are all the plans', ['plans' => $allPlans]);


            $plansById = collect($allPlans)->keyBy('plan_id');

            // 🔁 Adjust the amount for each plan based on the percentage
            $adjustedPlans = collect($allPlans)->map(function ($plan) use ($percentageService) {
                $networkId = $this->mapNetworkToId($plan['network']); // Map network name to ID

                // Add the original amount from the response
                $plan['original_amount'] = $plan['amount'];

                // Adjust the amount using the percentage service
                $plan['amount'] = round($percentageService->calculateDataDiscountedAmount($networkId, (float) str_replace(',', '', $plan['amount'])), 2);
                

                return $plan;
            });


            // 🔁 Group plans by network and then by validity
            $groupedPlans = collect($adjustedPlans)->groupBy('network')->map(function ($plans) {
                return $plans->groupBy(function ($plan) {
                    $validity = strtolower($plan['validity'] ?? '');

                    // Extract the numeric value from the validity string
                    preg_match('/\d+/', $validity, $matches);
                    $days = isset($matches[0]) ? (int) $matches[0] : 0;

                    if ($days > 0) {
                        if ($days < 7) {
                            return 'daily';
                        } elseif ($days >= 7 && $days < 30) {
                            return 'weekly';
                        } elseif ($days >= 30) {
                            return 'monthly';
                        }
                    }

                    return 'others'; // For plans that don't fit into the above categories
                });
            });

            // 🔥 Fetch hot deals from transaction history
            $activeApi = $apiService->getServiceName();
            $whichApi = $this->mapApiServiceToWhichApi($activeApi); // Map activeApi to which_api


            $cacheKey = "hot_deals:{$whichApi}";

            $rawHotDeals = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($whichApi) {
                return \App\Models\Transactions::query()
                    ->where('status', 'Successful')
                    ->where('which_api', $whichApi)
                    ->where('service', 'data')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->selectRaw('provider_id, service_plan, plan_id, COUNT(*) as purchases')
                    ->groupBy('provider_id', 'service_plan', 'plan_id')
                    ->orderByDesc('purchases')
                    ->get();
            });

            // 🔁 Map plans by ID for matching
            //$plansById = $adjustedPlans->keyBy('plan_id');

            // Group hot deals by network
            $hotDealsByProvider = $rawHotDeals->groupBy('provider_id')->map(function ($deals) use ($plansById, $percentageService) {
    return $deals->map(function ($deal) use ($plansById, $percentageService) {
        $plan = $plansById[$deal->plan_id] ?? null;
        if (!$plan) {
            return null; // skip if plan not found in API
        }
        $networkId = $plan['provider_id'] ?? $deal->provider_id;
        $originalAmount = $plan['original_amount'] ?? $plan['amount'];
        // Calculate the discounted amount using the original API price
        $discountedAmount = round($percentageService->calculateDataDiscountedAmount($networkId, (float) str_replace(',', '', $originalAmount)), 2);

        return [
            'provider_id'     => $deal->provider_id,
            'service_plan'    => $plan['plan_name'] ?? null,
            'plan_id'         => $deal->plan_id,
            'purchases'       => $deal->purchases,
            'amount'          => $discountedAmount,           // Discounted price
            'original_amount' => $originalAmount,             // API price
            'network'         => $plan['network'] ?? null,
            'validity'        => $plan['validity'] ?? null,
            'data_volume'     => $plan['data_volume'] ?? null,
        ];
    })->filter()->sortByDesc('purchases')->take(10)->values(); // filter() removes nulls
});

            // 🧠 Get special plans (cheapest top 10 for each network)
            $specialPlansCacheKey = "special_plans:{$apiService->getServiceName()}";
            $specialPlans = Cache::remember($specialPlansCacheKey, now()->addMinutes(60), function () use ($adjustedPlans) {
                // Group plans by network ID
                $groupedByNetworkId = $adjustedPlans->groupBy(function ($plan) {
                    return $this->mapNetworkToId($plan['network']); // Use network ID
                });

                // Fetch the cheapest 10 plans for each network ID
                return $groupedByNetworkId->map(function ($plans) {
                    return $plans->sortBy('amount')->take(9)->values();
                });
            });

            return response()->json([
                'status'         => true,
                'data'           => $groupedPlans,
                'special_plans'  => $specialPlans,
                'hot_deals'      => $hotDealsByProvider,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Data Plans Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Failed to retrieve data plans'
            ], 500);
        }
    }


    public function buyData(Request $request, PinService $pinService)
    {
        $validator = $this->validateRequest($request);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $network = $request->input('network');
            $mobile_number = $request->input('mobile_number');
            $amount = $request->input('amount');
            $original_amount = $request->input('original_amount', $amount); // Use original amount if provided
            $plan = $request->input('plan');
            $plan_size = $request->input('plan_size');
            $beneficiary = $request->input('beneficiary', false);

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

            $apiService = $this->getActiveApiService();

            if (!$apiService) {
                return response()->json([
                    'status' => false,
                    'message' => 'Our data service is currently not available'
                ], 503);
            }

            // Validate the network and phone number
            $validationResult = $this->validateNetworkAndNumber($apiService, $network, $mobile_number);
            if ($validationResult !== true) {
                // If validation fails, return the error response
                return $validationResult;
            }

            $response = $apiService->processRequest([
                'network' => $network,
                'mobile_number' => $mobile_number,
                'amount' => $amount,
                'plan' => $plan,
                'original_amount' => $original_amount,
            ]);

            return $this->handleApiResponse($apiService, $response, [
                'user' => $user,
                'amount' => $amount,
                'amount_charged' => $amount,
                'mobile_number' => $mobile_number,
                'image' => $request->image,
                'network' => $network,
                'plan_size' => $plan_size,
                'plan_id' => $plan,
                'beneficiary' => $beneficiary
            ]);
        } catch (\Exception $e) {
            Log::error('Buy Datas Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while processing your request'
            ], 500);
        }
    }


    protected function mapNetworkToId(string $network): int
    {
        return match (strtolower($network)) {
            'mtn' => 1,
            'glo' => 2,
            'airtel' => 3,
            '9mobile' => 6,
            default => 0
        };
    }

    protected function mapApiServiceToWhichApi(string $apiServiceName): string
    {
        return match ($apiServiceName) {
            'artx_data' => 'artx',
            'glad_data' => 'glad',
            default => 'unknown', // Handle unexpected cases
        };
    }
}
