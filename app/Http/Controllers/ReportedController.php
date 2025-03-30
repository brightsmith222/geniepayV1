<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\TransactionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Models\User;
use Illuminate\Support\Facades\Log;


class ReportedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    if ($request->ajax()) {
        $reports = TransactionReport::where('status', 'Reported')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($report) {
                return [
                    'username' => $report->username,
                    'service' => $report->service,
                    'amount' => number_format($report->amount, 2),
                    'service_plan' => $report->service_plan,
                    'created_at' => $report->created_at->diffForHumans()
                ];
            });

        return response()->json([
            'success' => true,
            'count' => TransactionReport::where('status', 'Reported')->count(),
            'reports' => $reports
        ]);
    }

    // Default sorting column and direction
    $sortColumn = request('sort', 'created_at'); // Default to 'created_at'
    $sortDirection = request('direction', 'desc'); // Default to 'desc'

    // Validate the sort column to prevent SQL injection
    $validColumns = ['id', 'amount', 'created_at', 'status'];
    if (!in_array($sortColumn, $validColumns)) {
        $sortColumn = 'created_at'; // Fallback to a valid column
    }

    // Get the search term from the request
    $searchTerm = $request->input('search');

    // Log the search term for debugging
    \Log::info('Search Term:', ['searchTerm' => $searchTerm]);

    // Base query for all transactions
    $reportedQuery = TransactionReport::where('status', 'Reported')->orderBy($sortColumn, $sortDirection);

    // Apply search filter if a search term is provided
    if ($searchTerm) {
        $reportedQuery->where(function ($query) use ($searchTerm) {
            $query->where('transaction_id', 'like', "%{$searchTerm}%")
                  ->orWhere('service', 'like', "%{$searchTerm}%")
                  ->orWhere('user', 'like', "%{$searchTerm}%")
                  ->orWhere('amount', 'like', "%{$searchTerm}%")
                  ->orWhere('status', 'like', "%{$searchTerm}%");
        });
    }

    // Paginate the results
    $reportedTransactions = $reportedQuery->paginate(7);

    // Log the query results for debugging
    \Log::info('Query Results:', ['results' => $reportedTransactions]);

    // Render the view and pass the necessary data
    return view('reported.index', compact('reportedTransactions'));
}

public function reportedrefund($requestId) 
{
    DB::beginTransaction();
    
    try {
        // Find by API transaction ID instead of primary key
        $reportedtransaction = TransactionReport::where('transaction_id', $requestId)->firstOrFail();

        // Log the current status for debugging
        \Log::info('Checking transaction status:', [
            'transaction_id' => $reportedtransaction->requestId,
            'status' => $reportedtransaction->status,
        ]);

        // Convert the status to lowercase for case-insensitive comparison
        $status = strtolower($reportedtransaction->status);

        // Check if THIS SPECIFIC TRANSACTION is already refunded
        if ($status === 'refunded') {
            \Log::warning('Transaction already refunded:', [
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
            \Log::error('User not found for transaction:', [
                'transaction_id' => $reportedtransaction->requestId,
                'username' => $reportedtransaction->username,
            ]);
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
            \Log::error('Failed to update user balance:', [
                'user_id' => $user->id,
                'transaction_id' => $reportedtransaction->requestId
            ]);
            throw new \Exception('Failed to update user balance');
        }

        // Refresh user balance from DB and verify the update
        $user->refresh();
        
        // Compare with a small epsilon to account for floating point precision
        if (abs($user->wallet_balance - $expectedBalance) > 0.0001) {
            \Log::error('Balance update verification failed:', [
                'expected' => $expectedBalance,
                'actual' => $user->wallet_balance,
                'difference' => abs($user->wallet_balance - $expectedBalance)
            ]);
            throw new \Exception('Balance update verification failed');
        }

        // Update THIS SPECIFIC TRANSACTION'S status to "refunded"
        $reportedtransaction->status = 'Refunded';
        if (!$reportedtransaction->save()) {
            \Log::error('Failed to update transaction status:', [
                'transaction_id' => $reportedtransaction->requestId
            ]);
            throw new \Exception('Failed to update transaction status');
        }

        DB::commit();

        \Log::info('Transaction refunded successfully:', [
            'transaction_id' => $reportedtransaction->requestId,
            'new_status' => $reportedtransaction->status,
            'user_new_balance' => $user->wallet_balance
        ]);

        return response()->json([
            'message' => 'Refund successful',
            'type' => 'success',
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Refund failed:', [
            'error' => $e->getMessage(),
            'request_id' => $requestId
        ]);
        
        return response()->json([
            'message' => 'An error occurred: ' . $e->getMessage(),
            'type' => 'error',
        ], 500);
    }
}

public function queryApiStatus($requestId)
{
    $headers = [
        'api-key' => '6f8493837a1d4b0e5715fd72849cb087',
        'secret-key' => 'SK_5139159efe5bb9bd7bec71f13cece42899e4d29611a',
        'public-key' => 'PK_5438116e83f6e4454bb7055ddd5960b363a9661143b',
        'Content-Type' => 'application/json',
    ];

    $url = "https://sandbox.vtpass.com/api/requery";
    $payload = [
        'request_id' => $requestId,
    ];

    try {
        $response = Http::withHeaders($headers)->post($url, $payload);
        $data = $response->json();

        Log::info('API Response: ', $data);

        // Ensure 'requestId' exists before accessing
        if (isset($data['requestId'])) {
            return response()->json($data, 200);
        }

        return response()->json(['status' => 'Failed to fetch status', 'error' => 'Missing requestId'], 500);
    } catch (\Exception $e) {
        Log::error('API Error: ' . $e->getMessage());
        return response()->json(['status' => 'Error fetching transaction details'], 500);
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

     public function show($transactionId)
{
    //Live header
    $headers = [
        'api-key' => '7e34e6b7628b552ab6572a989ad28bf6',
        'secret-key' => 'SK_69981ef530cd8a7eaf8f379e14baa5042e02957bed0',
        'public-key' => 'PK_3667469c9f7e5b8229d8a9cb0586ade01e4d8659655',
        'Content-Type' => 'application/json',
    ];

    $url = "https://vtpass.com/api/requery";
    $payload = [
        'request_id' => $transactionId,
    ];

    try {
        $response = Http::withHeaders($headers)
            ->withoutVerifying()
            ->post($url, $payload);

        $responseData = $response->json();
        Log::info('Full API Response:', ['response' => $responseData]);

        $transaction = $responseData['content']['transactions'];

        // Add additional details from the main response
        $transaction['requestId'] = $responseData['requestId'] ?? 'N/A';
        $transaction['transaction_date'] = $responseData['transaction_date'] ?? 'N/A';
        $transaction['purchased_code'] = $responseData['purchased_code'] ?? 'N/A';
        $transaction['response_description'] = $responseData['response_description'] ?? 'N/A';

        // Format the transaction date
        if ($transaction['transaction_date'] !== 'N/A') {
            try {
                $date = new \DateTime($transaction['transaction_date']);
                $transaction['formatted_date'] = $date->format('M d, Y - h:iA');
            } catch (\Exception $e) {
                $transaction['formatted_date'] = $transaction['transaction_date'];
            }
        } else {
            $transaction['formatted_date'] = 'N/A';
        }

        Log::info('Final Transaction Data:', ['transaction' => $transaction]);

        return view('reported.reports', compact('transaction'));
    } catch (\Exception $e) {
        Log::error('API Error: ' . $e->getMessage());
        return back()->with('error', 'Error fetching transaction details: ' . $e->getMessage());
    }
}
      

    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reported $reported)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reported $reported)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reported $reported)
    {
        //
    }
}
