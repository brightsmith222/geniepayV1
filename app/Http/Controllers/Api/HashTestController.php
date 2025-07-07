<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HashTestController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'sender_account_number' => 'required|string',
            'sender_bank_code' => 'required|string',
            'virtual_account_number' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $password = config('api.9psb.webhook_password');

        $stringToHash = $password .
            $request->sender_account_number .
            $request->sender_bank_code .
            $request->virtual_account_number .
            number_format($request->amount, 2, '.', '');

        $hash = strtoupper(hash('sha512', $stringToHash));

        return response()->json([
            'hash' => $hash,
            'string' => $stringToHash
        ]);
    }
}

