<?php

namespace App\Http\Controllers;

use App\Models\TransactionReport;
use Illuminate\Http\Request;
use App\Models\User;

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
    $reportedQuery = TransactionReport::orderBy($sortColumn, $sortDirection);

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

public function reportedrefund($id)
{
    // Find the specific transaction by its ID
    $reportedtransaction = TransactionReport::findOrFail($id);

    // Log the current status for debugging
    \Log::info('Checking transaction status:', [
        'transaction_id' => $reportedtransaction->id,
        'status' => $reportedtransaction->status,
    ]);

    // Convert the status to lowercase for case-insensitive comparison
    $status = strtolower($reportedtransaction->status);

    // Check if THIS SPECIFIC TRANSACTION is already refunded
    if ($status === 'refunded') {
        \Log::warning('Transaction already refunded:', [
            'transaction_id' => $reportedtransaction->id,
        ]);
        return response()->json([
            'message' => 'This transaction has already been refunded',
            'type' => 'error', // Add a type to differentiate between success and error
        ], 400);
    }

    // Find the user associated with this transaction
    $user = User::where('username', $reportedtransaction->user)->first();

    if (!$user) {
        \Log::error('User not found for transaction:', [
            'transaction_id' => $reportedtransaction->id,
            'username' => $reportedtransaction->user,
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
        'transaction_id' => $reportedtransaction->id,
        'new_status' => $reportedtransaction->status,
    ]);

    return response()->json([
        'message' => 'Refund successful',
        'type' => 'success', // Add a type to differentiate between success and error
    ], 200);
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
    public function show(Reported $reported)
    {
        //
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
