<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BeneficiaryService;
use Illuminate\Support\Facades\Log;
use App\Models\Beneficiary;

class BeneficiaryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
            'type' => 'required|string|in:airtime,data,electricity,cable,giftcard,smile',
            'provider' => 'required|integer',
        ]);

        $user = $request->user();
        $type = $request->input('type');
        $provider = $request->input('provider');


        $beneficiaries = (new BeneficiaryService())->getByTypeAndProvider($user, $type, $provider);

        Log::info('Retrieved beneficiaries', [
            'user_id' => $user->id,
            'type' => $type,
            'provider' => $provider,
        ]);
        
        return response()->json([
            'status' => true,
            'message' => 'Saved beneficiaries retrieved',
            'data' => $beneficiaries,
        ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving beneficiaries: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'type' => $request->input('type'),
                'provider' => $request->input('provider'),
            ]);
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }catch (\InvalidArgumentException $e) {
            Log::error('Validation error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'type' => $request->input('type'),
                'provider' => $request->input('provider'),
            ]);
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

public function all(Request $request)
{
    try {
        $user = $request->user();

        $beneficiaries = (new BeneficiaryService())->getAllForUser($user);

        Log::info('Retrieved all beneficiaries', [
            'user_id' => $user->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'All beneficiaries retrieved',
            'data' => $beneficiaries,
        ]);
    } catch (\Exception $e) {
        Log::error('Error retrieving all beneficiaries: ' . $e->getMessage(), [
            'user_id' => $request->user()->id,
        ]);
        return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
    }
}

public function delete(Request $request, $id)
{
    try {
        $user = $request->user();

        $beneficiary = Beneficiary::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$beneficiary) {
            return response()->json([
                'status' => false,
                'message' => 'Beneficiary not found or does not belong to you.'
            ], 404);
        }

        $beneficiary->delete();

        Log::info('Beneficiary deleted', [
            'user_id' => $user->id,
            'beneficiary_id' => $id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Beneficiary deleted successfully.'
        ]);
    } catch (\Exception $e) {
        Log::error('Error deleting beneficiary: ' . $e->getMessage(), [
            'user_id' => $request->user()->id,
            'beneficiary_id' => $id,
        ]);
        return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
    }
}

}
