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
        $cacheKey = "data_plans:{$apiService->serviceName}";

        // Check if data plans are cached
        $allPlans = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($apiService) {
            // Fetch plans by API
            if ($apiService instanceof \App\Services\ArtxDataService) {
                $mtnPlans     = $apiService->getDataPlans(1);
                $gloPlans     = $apiService->getDataPlans(2);
                $airtelPlans  = $apiService->getDataPlans(3);
                $mobile9Plans = $apiService->getDataPlans(6);
                return array_merge($mtnPlans, $gloPlans, $airtelPlans, $mobile9Plans);
            } else {
                return $apiService->getDataPlans(0); // Glad
            }
        });

        // 🔁 Adjust the amount for each plan based on the percentage
        $adjustedPlans = collect($allPlans)->map(function ($plan) use ($percentageService) {
            $networkId = $this->mapNetworkToId($plan['network']); // Map network name to ID
            $plan['amount'] = $percentageService->calculateDataDiscountedAmount($networkId, $plan['amount']);
            return $plan;
        });

        // 🔁 Get network percentage
        $networkPercent = DataTopupPercentage::all();

        // 🔥 Fetch hot deals from transaction history
        $activeApi = $apiService->serviceName;

        $cacheKey = "hot_deals:{$activeApi}";

        $rawHotDeals = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($activeApi) {
            return \App\Models\Transactions::query()
                ->where('status', 'Successful')
                ->where('which_api', $activeApi)
                ->where('service', 'data')
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('service_provider, service_plan, plan_id, COUNT(*) as purchases')
                ->groupBy('service_provider', 'service_plan', 'plan_id')
                ->orderByDesc('purchases')
                ->limit(10)
                ->get();
        });

        // 🔁 Map plans by ID for matching
        $plansById = $adjustedPlans->keyBy('plan_id');

        $hotDeals = $rawHotDeals->map(function ($deal) use ($plansById) {
            $plan = $plansById[$deal->plan_id] ?? null;

            return [
                'service_provider' => $deal->service_provider,
                'service_plan'     => $deal->service_plan,
                'plan_id'          => $deal->plan_id,
                'purchases'        => $deal->purchases,
                'amount'           => $plan['amount'] ?? null,
                'network'          => $plan['network'] ?? null,
                'validity'         => $plan['validity'] ?? null,
                'data_volume'      => $plan['data_volume'] ?? null,
            ];
        });

        // 🧠 Get special plans (cheapest top 10)
        $specialPlansCacheKey = "special_plans:{$apiService->serviceName}";
        $specialPlans = Cache::remember($specialPlansCacheKey, now()->addMinutes(60), function () use ($adjustedPlans) {
            return $adjustedPlans
                ->sortBy('amount')
                ->take(10)
                ->values();
        });

        return response()->json([
            'status'         => true,
            'networkPercent' => $networkPercent,
            'data'           => $adjustedPlans,
            'special_plans'  => $specialPlans,
            'hot_deals'      => $hotDeals,
        ]);
    } catch (\Exception $e) {
        Log::error('Get Data Plans Error', ['error' => $e->getMessage()]);
        return response()->json([
            'status'  => false,
            'message' => 'Failed to retrieve data plans'
        ], 500);
    }
}


    public function buyData(Request $request, PercentageService $percentageService)
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
            $plan = $request->input('plan');
            $plan_size = $request->input('plan_size');

            $user = $request->user();
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

            $validationResult = $this->validateNetworkAndNumber($apiService, $network, $mobile_number);
            if ($validationResult != true) {
                return $validationResult;
            }
            // Calculate discounted amount
            // $amount_charged = $percentageService->calculateDataDiscountedAmount($network, $amount);

            $response = $apiService->processRequest([
                'network' => $network,
                'mobile_number' => $mobile_number,
                'amount' => $amount,
                'plan' => $plan,
                'plan_id' => $request->input('plan_id') // Needed for ARTX
            ]);

            return $this->handleApiResponse($apiService, $response, [
                'user' => $user,
                'amount' => $amount,
                'amount_charged' => $amount,
                //'amount_charged' => $amount_charged,
                'mobile_number' => $mobile_number,
                'image' => $request->image,
                'network' => $network,
                'plan_size' => $plan_size
            ]);
        } catch (\Exception $e) {
            Log::error('Buy Data Error', ['error' => $e->getMessage()]);
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
}
