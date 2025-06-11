<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BeneficiaryService;

class BeneficiaryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:airtime,data,electricity,cable,giftcard,smile',
            'provider' => 'required|string',
        ]);

        $user = $request->user();
        $type = $request->input('type');
        $provider = $request->input('provider');

        $beneficiaries = (new BeneficiaryService())->getByTypeAndProvider($user, $type, $provider);

        return response()->json([
            'status' => true,
            'message' => 'Saved beneficiaries retrieved',
            'data' => $beneficiaries,
        ]);
    }
}
