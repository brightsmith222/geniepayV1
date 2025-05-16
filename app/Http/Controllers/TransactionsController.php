<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Services\VtpassService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    // Validate sort parameters
    $sortColumn = $request->get('sort_column', 'created_at');
    $sortDirection = $request->get('sort_direction', 'desc');
    $searchTerm = $request->get('search');
    $type = $request->get('type');
    
    $validColumns = ['id', 'amount', 'created_at', 'status'];
    $validDirections = ['asc', 'desc'];
    
    if (!in_array($sortColumn, $validColumns)) {
        $sortColumn = 'created_at';
    }
    
    if (!in_array($sortDirection, $validDirections)) {
        $sortDirection = 'desc';
    }

    // Base query builder function
    $buildQuery = function($type = null) use ($sortColumn, $sortDirection, $searchTerm) {
        return Transactions::query()
            ->when($type, function($query) use ($type) {
                $query->where('service', ucfirst($type));
            })
            ->when($searchTerm, function($query) use ($searchTerm) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('transaction_id', 'like', "%{$searchTerm}%")
                      ->orWhere('service', 'like', "%{$searchTerm}%")
                      ->orWhere('username', 'like', "%{$searchTerm}%")
                      ->orWhere('amount', 'like', "%{$searchTerm}%")
                      ->orWhere('status', 'like', "%{$searchTerm}%")
                      ->orWhere('phone_number', 'like', "%{$searchTerm}%");
                });
            })
            ->orderBy($sortColumn, $sortDirection);
    };

    // AJAX response
    if ($request->ajax()) {
        $transactions = $buildQuery($type)->paginate(7)->onEachSide(1);
        
        return response()->json([
            'table' => view('transaction.partials.table', [
                'transactions' => $transactions,
                'type' => $type ?: 'all'
            ])->render(),
            'pagination' => $transactions->links('vendor.pagination.bootstrap-4')->render()
        ]);
    }

    // Regular response - load all transaction types for initial page load
    $transactionsData = [
        'allTransactions' => $buildQuery()->paginate(7),
        'dataTransactions' => $buildQuery('data')->paginate(7),
        'airtimeTransactions' => $buildQuery('airtime')->paginate(7),
        'cableTransactions' => $buildQuery('cable')->paginate(7),
        'electricityTransactions' => $buildQuery('electricity')->paginate(7),
        'examTransactions' => $buildQuery('exam')->paginate(7),
        'searchTerm' => $searchTerm,
        'sortColumn' => $sortColumn,
        'sortDirection' => $sortDirection
    ];

    return view('transaction.index', $transactionsData);
}



    //Refund user failed transaction
    public function refund($requestId)
{
    DB::beginTransaction();
    
    try {
        // Find by API transaction ID instead of primary key
        $reportedtransaction = Transactions::where('transaction_id', $requestId)->firstOrFail();
        $adminUsername = auth()->user()->username;

        // Convert the status to lowercase for case-insensitive comparison
        $status = strtolower($reportedtransaction->status);

        // Check if THIS SPECIFIC TRANSACTION is already refunded
        if ($status === 'refunded') {
            Log::warning('Transaction already refunded:', [
                'transaction_id' => $reportedtransaction->requestId,
            ]);
            return response()->json([
                'message' => 'This transaction has already been refunded',
                'type' => 'error',
            ], 400);
        }

        // Find the user associated with this transaction
        $user = User::where('username', $reportedtransaction->username)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'type' => 'error',
            ], 404);
        }

        // Store previous balance for verification
        $previousBalance = $user->wallet_balance;
        $expectedBalance = $previousBalance + $reportedtransaction->amount;

        // Refund the amount to the user's wallet
        $user->wallet_balance = $expectedBalance;

        if (!$user->save()) {
            throw new \Exception('Failed to update user balance');
        }

        // Refresh user balance from DB and verify the update
        $user->refresh();
        
        // Compare with a small epsilon to account for floating point precision
        if (abs($user->wallet_balance - $expectedBalance) > 0.0001) {
            throw new \Exception('Balance update verification failed');
        }

        // Update THIS SPECIFIC TRANSACTION'S status to "refunded"
        $reportedtransaction->status = 'Refunded';
        $reportedtransaction->updated_by = $adminUsername;
        if (!$reportedtransaction->save()) {
            Log::error('Failed to update transaction status:', [
                'transaction_id' => $reportedtransaction->requestId
            ]);
            throw new \Exception('Failed to update transaction status');
        }

        DB::commit();

        return response()->json([
            'message' => 'Refund successful',
            'type' => 'success',
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'message' => 'An error occurred: ' . $e->getMessage(),
            'type' => 'error',
        ], 500);
    }
}
    

public function show($transactionId, VtpassService $vtpass)
{
    // First get the transaction from your local database
    $localTransaction = Transactions::where('transaction_id', $transactionId)->first();
    $api = strtolower($localTransaction->which_api);

    if ($api === 'vtpass') {
    $headers = $vtpass->getHeaders();

    $baseUrl = config('api.vtpass.base_url');
    $url = $baseUrl."requery";
    $payload = [
        'request_id' => $transactionId,
    ];

    try {
        $response = Http::withHeaders($headers)
            ->withoutVerifying()
            ->post($url, $payload);

        $responseData = $response->json();
        Log::info('Full API Response:', ['response' => $responseData]);

        // Check if the response contains transactions data
        if (!isset($responseData['content']['transactions'])) {
            Log::error('Transactions data missing in API response', ['response' => $responseData]);
            return back()->with('error', 'Transaction details not found in API response');
        }

        // Prepare transaction data
        $transaction = $responseData['content']['transactions'];
        
        // Add additional details from the main response
        $transaction['requestId'] = $responseData['requestId'] ?? 'N/A';
        $transaction['transaction_date'] = $responseData['transaction_date'] ?? 'N/A';
        $transaction['purchased_code'] = $responseData['purchased_code'] ?? 'N/A';
        $transaction['response_description'] = $responseData['response_description'] ?? 'N/A';

        // Add the status from your local database
        $transaction['local_status'] = $localTransaction ? $localTransaction->status : 'N/A';
        $transaction['local_balance_before'] = $localTransaction ? $localTransaction->balance_before : 'N/A';
        $transaction['local_balance_after'] = $localTransaction ? $localTransaction->balance_after : 'N/A';
        $transaction['image'] = $localTransaction ? $localTransaction->image : 'N/A';
        

        // Format the transaction date
        if ($transaction['transaction_date'] !== 'N/A') {
            try {
                $date = new \DateTime($transaction['transaction_date']);
                $transaction['formatted_date'] = $date->format('M d, Y - h:iA');
            } catch (\Exception $e) {
                $transaction['formatted_date'] = $transaction['transaction_date'];
                Log::warning('Failed to format transaction date', ['error' => $e->getMessage()]);
            }
        } else {
            $transaction['formatted_date'] = 'N/A';
        }

        Log::info('Final Transaction Data:', ['transaction' => $transaction]);

        return view('transaction.reports', compact('transaction'));
    } catch (\Exception $e) {
        Log::error('API Error: ' . $e->getMessage());
        return back()->with('error', 'Error fetching transaction details: ' . $e->getMessage());
    }
}
    if ($api === 'artx') {
        $salt = Str::random(40);
        $username = config('api.artx.username');
        $passwordHash = sha1(config('api.artx.password'));
        $fullHash = hash('sha512', $salt . $passwordHash);

        $payload = [
            'auth' => [
                'username' => $username,
                'salt' => $salt,
                'password' => $fullHash
            ],
            'version' => 5,
            'command' => 'getTransaction',
            'id' => $transactionId
        ];

        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->post(config('api.artx.base_url'), $payload);

            $data = $response->json();
            Log::info('ARTX Status Response', ['transaction_id' => $transactionId, 'response' => $data]);

            if (!isset($data['status'])) {
                return back()->with('error', 'Invalid response from ARTX.');
            }

            $transaction = [
                'transaction_id' => $transactionId,
                'status' => $data['status']['name'],
                'status_type' => $data['status']['type'],
                'status_id' => $data['status']['id'],
                'local_status' => $localTransaction->status,
                'local_balance_before' => $localTransaction->balance_before,
                'local_balance_after' => $localTransaction->balance_after,
                'username' => $localTransaction->username,
                'amount' => $localTransaction->amount,
                'service' => $localTransaction->service,
                'image' => $localTransaction->image,
                'created_at' => $localTransaction->created_at->format('M d, Y - h:iA'),
                'updated_at' => $localTransaction->updated_at->format('M d, Y - h:iA')
            ];

            return view('transaction.reports', compact('transaction'));

        } catch (\Exception $e) {
            Log::error('ARTX transaction check failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error checking transaction with ARTX: ' . $e->getMessage());
        }
    }

    return back()->with('error', 'API not supported or not specified for this transaction.');
}

public function resolve($requestId)
{
    DB::beginTransaction();
    
    try {
        $transaction = Transactions::where('transaction_id', $requestId)->firstOrFail();
        $adminUsername = auth()->user()->username;

        // Check if already resolved
        if (strtolower($transaction->status) === 'resolved') {
            return response()->json([
                'message' => 'This transaction is already resolved',
                'type' => 'info',
            ], 400);
        }

        // Update status
        $transaction->status = 'resolved';
        $transaction->updated_by = $adminUsername;
        $transaction->save();

        DB::commit();

        Log::info('Transaction resolved by admin', [
            'admin' => $adminUsername,
            'transaction_id' => $transaction->requestId
        ]);

        return response()->json([
            'message' => 'Transaction marked as resolved (no refund)',
            'type' => 'success',
            'new_status' => 'resolved'
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'An error occurred: ' . $e->getMessage(),
            'type' => 'error',
        ], 500);
    }
}

public function refreshStatus(Request $request, VtpassService $vtpass)
{
    $request->validate(['transaction_id' => 'required|string']);

    $transaction = Transactions::where('transaction_id', $request->transaction_id)->firstOrFail();
    $api = strtolower($transaction->which_api);

    return match ($api) {
        'artx'    => $this->refreshArtx($transaction),
        'vtpass'  => $this->refreshVtpass($transaction, $vtpass),
        'glad'    => $this->refreshGlad($transaction),
        default   => response()->json([
            'status' => false,
            'message' => 'Unsupported or unknown API source.'
        ], 400)
    };
}


private function refreshArtx($transaction)
{
    if (in_array(strtolower($transaction->status), ['refunded', 'resolved', 'successful', 'failed'])) {
        return response()->json([
            'status' => false,
            'message' => 'This transaction has already been ' . strtolower($transaction->status) . '.'
        ], 400);
    }

    $salt = Str::random(40);
    $username = config('api.artx.username');
    $passwordHash = sha1(config('api.artx.password'));
    $fullHash = hash('sha512', $salt . $passwordHash);

    $payload = [
        'auth' => [
            'username' => $username,
            'salt' => $salt,
            'password' => $fullHash
        ],
        'version' => 5,
        'command' => 'getTransaction',
        'id' => $transaction->transaction_id
    ];

    try {
        $response = Http::withoutVerifying()
            ->timeout(30)
            ->post(config('api.artx.base_url'), $payload)
            ->json();

        $type = $response['status']['type'] ?? null;

        if ($type == 0) {
            $transaction->status = 'Successful';
            $transaction->save();

            return response()->json([
                'status' => true,
                'message' => 'Transaction marked as successful'
            ]);
        }

        if ($type == 2) {
            $user = User::where('id', $transaction->user_id)->first();
            if ($user) {
                $user->wallet_balance += $transaction->amount;
                $user->save();

                $transaction->status = 'Refunded';
                $transaction->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Transaction failed. User refunded.'
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'User not found for refund'
            ], 404);
        }

        return response()->json([
            'status' => false,
            'message' => 'Transaction still pending'
        ]);

    } catch (\Exception $e) {
        Log::error('ARTX refresh error', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => false,
            'message' => 'ARTX API error: ' . $e->getMessage()
        ], 500);
    }
}


private function refreshVtpass($transaction, VtpassService $vtpass)
{
    if (in_array(strtolower($transaction->status), ['refunded', 'resolved', 'successful', 'failed'])) {
        return response()->json([
            'status' => false,
            'message' => 'This transaction has already been ' . strtolower($transaction->status) . '.'
        ], 400);
    }

    $payload = ['request_id' => $transaction->transaction_id];

    try {
        $response = Http::withHeaders($vtpass->getHeaders())
            ->withoutVerifying()
            ->post(config('api.vtpass.base_url').'requery', $payload)
            ->json();

        $code = $response['code'] ?? 'unknown';

        if ($code === '000') {
            $transaction->status = 'Successful';
            $transaction->save();

            Log::info('Transaction marked as successful', [
                'transaction_id' => $transaction->transaction_id,
                'status' => $transaction->status
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Transaction marked as successful'
            ]);
        }

        if ($code === '0999') {
            $user = User::where('id', $transaction->user_id)->first();
            if ($user) {
                $user->wallet_balance += $transaction->amount;
                $user->save();

                $transaction->status = 'Refunded';
                $transaction->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Transaction failed. User refunded (VTpass).'
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'User not found for refund (VTpass)'
            ], 404);
        }

        if ($code === '099') {
            return response()->json([
                'status' => false,
                'message' => 'Transaction still pending (VTpass)'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'API returned an Invalid Transaction ID response: ' . $code
        ], 400);

    } catch (\Exception $e) {
        Log::error('VTpass refresh error', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => false,
            'message' => 'VTpass API error: ' . $e->getMessage()
        ], 500);
    }
}

private function refreshGlad($transaction)
{
    $gladAPIKey = config('api.glad.api_key'); // or use the actual token
    $service = strtolower($transaction->service);
    $reference = $transaction->transaction_id;

    $endpoints = [
        'airtime' => "https://www.gladtidingsdata.com/api/airtime/requery/{$reference}",
        'data' => "https://www.gladtidingsdata.com/api/data/requery/{$reference}",
        'cable' => "https://www.gladtidingsdata.com/api/cable/requery/{$reference}",
        'electricity' => "https://www.gladtidingsdata.com/api/electricity/requery/{$reference}",
        // Add more mappings if needed
    ];

    if (!isset($endpoints[$service])) {
        return response()->json([
            'status' => false,
            'message' => "Unsupported GladTidings service type: {$service}"
        ], 400);
    }

    try {
        $response = Http::withoutverifying()->withHeaders([
            'Authorization' => 'Token ' . $gladAPIKey
        ])->get($endpoints[$service]);

        $data = $response->json();
        $status = strtolower($data['Status'] ?? 'unknown');

        if ($status === 'successful') {
            $transaction->status = 'Successful';
            $transaction->save();

            return response()->json([
                'status' => true,
                'message' => "Transaction marked as successful ({$service})"
            ]);
        }

        if ($status === 'failed') {
            $user = User::where('username', $transaction->username)->first();
            if ($user) {
                $user->wallet_balance += $transaction->amount;
                $user->save();

                $transaction->status = 'Refunded';
                $transaction->save();

                return response()->json([
                    'status' => true,
                    'message' => "Transaction failed. User refunded ({$service})"
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'User not found for refund (GladTidings)'
            ], 404);
        }

        return response()->json([
            'status' => false,
            'message' => "Transaction still pending or unknown ({$service})"
        ]);

    } catch (\Exception $e) {
        Log::error("GladTidings requery error for {$service}", ['error' => $e->getMessage()]);
        return response()->json([
            'status' => false,
            'message' => "GladTidings API error for {$service}: " . $e->getMessage()
        ], 500);
    }
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transactions $transactions)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transactions $transactions)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transactions $transactions)
    {
        //
    }
}
