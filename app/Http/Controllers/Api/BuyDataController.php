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
    public function getDataPlan(Request $request, PercentageService $percentageService, BeneficiaryService $beneficiaryService)
{
    try {
        $networkId = $request->input('network');
        $user = $request->user();
        $beneficiaries = $beneficiaryService->getByTypeAndProvider($user, 'data', $networkId);

        if (!$networkId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide a valid network ID.'
            ], 400);
        }

        $apiService = $this->getActiveApiService();

        if (!$apiService) {
            return response()->json([
                'status' => false,
                'message' => 'No active data service available'
            ], 503);
        }

        $cacheKey = "data_plans:{$apiService->getServiceName()}:network:{$networkId}";

        $networkPlans = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($apiService, $networkId) {
            return $apiService->getDataPlans($networkId);
        });

        Log::info('Fetched plans for network', ['network_id' => $networkId, 'plans' => $networkPlans]);

        $plansById = collect($networkPlans)->keyBy('plan_id');

        $adjustedPlans = collect($networkPlans)->map(function ($plan) use ($percentageService) {
            $networkId = $this->mapNetworkToId($plan['network']);
            $plan['original_amount'] = $plan['amount'];
            $plan['amount'] = round($percentageService->calculateDataDiscountedAmount($networkId, (float) str_replace(',', '', $plan['amount'])), 2);

            // Determine type from validity
            $validity = strtolower($plan['validity'] ?? '');
            preg_match('/\d+/', $validity, $matches);
            $days = isset($matches[0]) ? (int) $matches[0] : 0;

            if ($days > 0) {
                if ($days < 7) {
                    $plan['type'] = 'daily';
                } elseif ($days >= 7 && $days < 30) {
                    $plan['type'] = 'weekly';
                } elseif ($days >= 30) {
                    $plan['type'] = 'monthly';
                } else {
                    $plan['type'] = 'others';
                }
            } else {
                $plan['type'] = 'others';
            }

            return $plan;
        });

        $activeApi = $apiService->getServiceName();
        $whichApi = $this->mapApiServiceToWhichApi($activeApi);

        // Hot Deals
        $hotDealsKey = "hot_deals:{$whichApi}:{$networkId}";
        $rawHotDeals = Cache::remember($hotDealsKey, now()->addMinutes(60), function () use ($whichApi, $networkId) {
            return \App\Models\Transactions::query()
                ->where('status', 'Successful')
                ->where('which_api', $whichApi)
                ->where('service', 'data')
                ->where('provider_id', $networkId)
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('provider_id, service_plan, plan_id, COUNT(*) as purchases')
                ->groupBy('provider_id', 'service_plan', 'plan_id')
                ->orderByDesc('purchases')
                ->get();
        });

        $hotDeals = $rawHotDeals->map(function ($deal) use ($plansById, $percentageService) {
            $plan = $plansById[$deal->plan_id] ?? null;
            if (!$plan) return null;

            $networkId = $plan['provider_id'] ?? $deal->provider_id;
            $originalAmount = $plan['amount'];
            $discountedAmount = round($percentageService->calculateDataDiscountedAmount($networkId, (float) str_replace(',', '', $originalAmount)), 2);

            $validity = strtolower($plan['validity'] ?? '');
            preg_match('/\d+/', $validity, $matches);
            $days = isset($matches[0]) ? (int) $matches[0] : 0;

            if ($days > 0) {
                if ($days < 7) {
                    $type = 'daily';
                } elseif ($days >= 7 && $days < 30) {
                    $type = 'weekly';
                } elseif ($days >= 30) {
                    $type = 'monthly';
                } else {
                    $type = 'others';
                }
            } else {
                $type = 'others';
            }

            return [
                'provider_id'     => $deal->provider_id,
                'service_plan'    => $plan['plan_name'] ?? null,
                'plan_id'         => $deal->plan_id,
                'purchases'       => $deal->purchases,
                'amount'          => $discountedAmount,
                'original_amount' => $originalAmount,
                'network'         => $plan['network'] ?? null,
                'validity'        => $plan['validity'] ?? null,
                'data_volume'     => $plan['data_volume'] ?? null,
                'type'            => 'hot_deal',
            ];
        })->filter()->sortByDesc('purchases')->take(10)->values();

        // Special Plans
        $specialKey = "special_plans:{$apiService->getServiceName()}:network:{$networkId}";
        $specialPlans = Cache::remember($specialKey, now()->addMinutes(60), function () use ($adjustedPlans) {
            return $adjustedPlans->sortBy('amount')->take(10)->values();
        })->map(function ($plan) {
            $plan['type'] = 'special_deal';
            return $plan;
        });

        // Merge all plans into one data response
        $mergedPlans = $adjustedPlans
            ->merge($hotDeals)
            ->merge($specialPlans)
            ->values();

        return response()->json([
            'status'        => true,
            'data'          => $mergedPlans,
            'beneficiaries' => $beneficiaries,
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
