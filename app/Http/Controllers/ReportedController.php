<?php

namespace App\Http\Controllers;

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
    // Find by API transaction ID instead of primary key
    $reportedtransaction = TransactionReport::where('transaction_id', $requestId)->first();
   

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
            'type' => 'error', // Add a type to differentiate between success and error
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
            'type' => 'error', // Add a type to differentiate between success and error
        ], 404);
    }

    // Refund the amount to the user's wallet
    $user->wallet_balance += $reportedtransaction->amount;
    $user->save();

    // Update THIS SPECIFIC TRANSACTION'S status to "refunded"
    $reportedtransaction->status = 'Refunded';
    $reportedtransaction->save();

    // Log the updated status for debugging
    \Log::info('Transaction refunded successfully:', [
        'transaction_id' => $reportedtransaction->requestId,
        'new_status' => $reportedtransaction->status,
    ]);

    return response()->json([
        'message' => 'Refund successful',
        'type' => 'success', // Add a type to differentiate between success and error
    ], 200);
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

        // Check if response is successful
        if ($response->failed() || !isset($responseData['code'])) {
            Log::error('Transaction fetch failed.', ['response' => $responseData]);
            return back()->with('error', 'Failed to fetch transaction details.');
        }

        // Check if transaction was successful
        if ($responseData['code'] !== '000') {
            Log::error('Transaction not successful.', ['response' => $responseData]);
            return back()->with('error', 'Transaction not found or not successful.');
        }

        // Check if 'content' exists
        if (!isset($responseData['content']) || !isset($responseData['content']['transactions'])) {
            Log::error('API response is missing content or transactions.', ['response' => $responseData]);
            return back()->with('error', 'Transaction data is incomplete or not available.');
        }

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
                $transaction['formatted_date'] = $date->format('jS M, Y h:i A');
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
