<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\TransactionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Models\User;
use App\Services\VtpassService;
use Illuminate\Support\Facades\Log;


class ReportedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    // Reported Transactions
    $reportedData = $this->getReportedTransactions($request);
    
    // Resolved/Refunded Transactions
    $resolvedData = $this->getResolvedTransactions($request);

    // AJAX response
    if ($request->ajax()) {
        return response()->json([
            'reported' => [
                'table' => view('reported.partials.table', [
                    'transactions' => $reportedData['transactions'],
                ])->render(),
                'pagination' => $reportedData['transactions']->links('vendor.pagination.bootstrap-4')->render()
            ],
            'resolved' => [
                'table' => view('reported.partials.resolved_table', [
                    'transactions' => $resolvedData['transactions'],
                ])->render(),
                'pagination' => $resolvedData['transactions']->links('vendor.pagination.bootstrap-4')->render()
            ]
        ]);
    }

    // Regular response remains the same
    return view('reported.index', [
        'reportedTransactions' => $reportedData['transactions'],
        'resolvedTransactions' => $resolvedData['transactions'],
        'searchTerm' => $reportedData['searchTerm'],
        'sortColumn' => $reportedData['sortColumn'],
        'sortDirection' => $reportedData['sortDirection'],
        'resolvedSearchTerm' => $resolvedData['resolvedSearchTerm'],
        'resolvedSortColumn' => $resolvedData['resolvedSortColumn'],
        'resolvedSortDirection' => $resolvedData['resolvedSortDirection']
    ]);
}

protected function getReportedTransactions(Request $request)
{
    $validated = $request->validate([
        'sort_column' => 'sometimes|string|in:created_at,amount,updated_at',
        'sort_direction' => 'sometimes|string|in:asc,desc',
        'search' => 'nullable|string',
        'page' => 'sometimes|integer'
    ]);

    $sortColumn = $validated['sort_column'] ?? 'created_at';
    $sortDirection = $validated['sort_direction'] ?? 'desc';
    $searchTerm = $validated['search'] ?? null; 

    $query = TransactionReport::where('status', 'Reported')
        ->when($searchTerm, function($query) use ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('transaction_id', 'like', "%{$searchTerm}%")
                  ->orWhere('service', 'like', "%{$searchTerm}%")
                  ->orWhere('username', 'like', "%{$searchTerm}%")
                  ->orWhere('amount', 'like', "%{$searchTerm}%");
            });
        })
        ->orderBy($sortColumn, $sortDirection);

    $transactions = $query->paginate(7)
        ->appends([
            'search' => $searchTerm,
            'sort_column' => $sortColumn,
            'sort_direction' => $sortDirection
        ]);

    return [
        'transactions' => $transactions,
        'searchTerm' => $searchTerm,
        'sortColumn' => $sortColumn,
        'sortDirection' => $sortDirection
    ];
}

protected function getResolvedTransactions(Request $request)
{
    $validated = $request->validate([
        'resolved_sort_column' => 'sometimes|string|in:updated_at,amount,created_at',
        'resolved_sort_direction' => 'sometimes|string|in:asc,desc',
        'resolved_search' => 'nullable|string',
        'page' => 'sometimes|integer'
    ]);

    $sortColumn = $validated['resolved_sort_column'] ?? 'updated_at';
    $sortDirection = $validated['resolved_sort_direction'] ?? 'desc';
    $searchTerm = $validated['resolved_search'] ?? null;

    $query = TransactionReport::whereIn('status', ['Refunded', 'resolved'])
        ->when($searchTerm, function($query) use ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('transaction_id', 'like', "%{$searchTerm}%")
                  ->orWhere('service', 'like', "%{$searchTerm}%")
                  ->orWhere('username', 'like', "%{$searchTerm}%")
                  ->orWhere('amount', 'like', "%{$searchTerm}%")
                  ->orWhere('updated_by', 'like', "%{$searchTerm}%");
            });
        })
        ->orderBy($sortColumn, $sortDirection);

    $transactions = $query->paginate(7)->onEachSide(1)
        ->appends([
            'resolved_search' => $searchTerm,
            'resolved_sort_column' => $sortColumn,
            'resolved_sort_direction' => $sortDirection
        ]);

    return [
        'transactions' => $transactions, // Changed from 'resolvedTransactions' for consistency
        'resolvedSearchTerm' => $searchTerm,
        'resolvedSortColumn' => $sortColumn,
        'resolvedSortDirection' => $sortDirection
    ];
}

public function resolved(Request $request)
{
    $resolvedData = $this->getResolvedTransactions($request);
    
    return response()->json([
        'table' => view('reported.partials.resolved_tab', ['transactions' => $resolvedData['resolvedTransactions']])->render(),
        'pagination' => $resolvedData['resolvedTransactions']->links('vendor.pagination.bootstrap-4')->render()
    ]);
}


public function reportedrefund($requestId) 
{
    DB::beginTransaction();
    
    try {
        // Find by API transaction ID instead of primary key
        $reportedtransaction = TransactionReport::where('transaction_id', $requestId)->firstOrFail();
        $adminUsername = auth()->user()->username;

        // Log the current status for debugging
        Log::info('Checking transaction status:', [
            'transaction_id' => $reportedtransaction->requestId,
            'status' => $reportedtransaction->status,
        ]);

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
            Log::error('User not found for transaction:', [
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
            Log::error('Failed to update user balance:', [
                'user_id' => $user->id,
                'transaction_id' => $reportedtransaction->requestId
            ]);
            throw new \Exception('Failed to update user balance');
        }

        // Refresh user balance from DB and verify the update
        $user->refresh();
        
        // Compare with a small epsilon to account for floating point precision
        if (abs($user->wallet_balance - $expectedBalance) > 0.0001) {
            Log::error('Balance update verification failed:', [
                'expected' => $expectedBalance,
                'actual' => $user->wallet_balance,
                'difference' => abs($user->wallet_balance - $expectedBalance)
            ]);
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

        Log::info('Transaction refunded successfully:', [
            'transaction_id' => $reportedtransaction->requestId,
            'new_status' => $reportedtransaction->status,
            'admin' => $adminUsername,
            'user_new_balance' => $user->wallet_balance
        ]);

        return response()->json([
            'message' => 'Refund successful',
            'type' => 'success',
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Refund failed:', [
            'error' => $e->getMessage(),
            'request_id' => $requestId
        ]);
        
        return response()->json([
            'message' => 'An error occurred: ' . $e->getMessage(),
            'type' => 'error',
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

     public function show($transactionId, VtpassService $vtpass)
{
    // First get the transaction from your local database
    $localTransaction = TransactionReport::where('transaction_id', $transactionId)->first();
    
    $headers = $vtpass->getHeaders();
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
        $transaction['id'] = $localTransaction ? $localTransaction->id : 'N/A';
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

        return view('reported.reports', compact('transaction'));
    } catch (\Exception $e) {
        Log::error('API Error: ' . $e->getMessage());
        return back()->with('error', 'Error fetching transaction details: ' . $e->getMessage());
    }
}
      

    

}