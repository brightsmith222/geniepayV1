<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ArtxHelper;

class MobilePinController extends Controller
{
    public function parseMsisdn(Request $request)
    {
        $request->validate(['msisdn' => 'required|string']);

        $response = ArtxHelper::request('parseMsisdn', [
            'msisdn' => $request->msisdn,
        ]);

        return response()->json($response);
    }

    public function getOperators($country)
    {
        $response = ArtxHelper::request('getOperators', [
            'country' => $country,
            'productType' => 2 // 2 = Mobile PIN
        ]);

        return response()->json($response);
    }

    public function getOperatorProducts($operator)
    {
        $response = ArtxHelper::request('getOperatorProducts', [
            'operator' => $operator
        ]);

        return response()->json($response);
    }

    public function execTransaction(Request $request)
    {
        $request->validate([
            'operator' => 'required|integer',
            'productId' => 'required|integer',
            'amountOperator' => 'required|numeric',
            'msisdn' => 'nullable|string', // Optional for Mobile PIN
        ]);

        $params = [
            'operator' => $request->operator,
            'productId' => $request->productId,
            'amountOperator' => $request->amountOperator,
            'simulate' => 0,
        ];

        if ($request->filled('msisdn')) {
            $params['msisdn'] = $request->msisdn;
        }

        $response = ArtxHelper::request('execTransaction', $params);

        return response()->json($response);
    }
}
