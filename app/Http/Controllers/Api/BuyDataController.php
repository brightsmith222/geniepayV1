<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use App\Services\PercentageService;
use App\Models\DataTopupPercentage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class BuyDataController extends BaseDataController
{
    public function getDataPlan(Request $request)
    {
        try {
            $apiService = $this->getActiveApiService();
            
            if (!$apiService) {
                return response()->json([
                    'status' => false,
                    'message' => 'No active data service available'
                ], 503);
            }

            // For ARTX, we need to get plans for each network
            if ($apiService instanceof \App\Services\ArtxDataService) {
                $mtnPlans = $apiService->getDataPlans(1);
                $gloPlans = $apiService->getDataPlans(2);
                $airtelPlans = $apiService->getDataPlans(3);
                $_9mobilePlans = $apiService->getDataPlans(6);
                
                $allPlans = array_merge($mtnPlans, $gloPlans, $airtelPlans, $_9mobilePlans);
            } else {
                // GladTidings returns all plans at once
                $allPlans = $apiService->getDataPlans(0);
            }

            $networkPercent = DataTopupPercentage::all();
            
            return response()->json([
                'status' => true,
                'networkPercent' => $networkPercent,
                'data' => $allPlans
            ]);

        } catch (\Exception $e) {
            Log::error('Get Data Plans Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
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
            $amount_charged = $percentageService->calculateDataDiscountedAmount($network, $amount);

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
                'amount_charged' => $amount_charged,
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
}