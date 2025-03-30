<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\WalletTransactions;
use App\Models\User;
use Illuminate\Http\Request;

class WalletTransactionsController extends Controller
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
    $walletQuery = WalletTransactions::orderBy($sortColumn, $sortDirection);

    // Apply search filter if a search term is provided
    if ($searchTerm) {
        $walletQuery->where(function ($query) use ($searchTerm) {
            $query->where('transaction_id', 'like', "%{$searchTerm}%")
                  ->orWhere('service', 'like', "%{$searchTerm}%")
                  ->orWhere('user', 'like', "%{$searchTerm}%")
                  ->orWhere('amount', 'like', "%{$searchTerm}%")
                  ->orWhere('status', 'like', "%{$searchTerm}%");
        });
    }

    // Paginate the results
    $walletTransactions = $walletQuery->paginate(7);

    // Log the query results for debugging
    \Log::info('Query Results:', ['results' => $walletTransactions]);

    // Render the view and pass the necessary data
    return view('wallet_transac.index', compact('walletTransactions'));
}

public function walletrefund($id)
{
    DB::beginTransaction();
    
    try {
        // Find the specific transaction by its ID
        $transaction = WalletTransactions::findOrFail($id);

        \Log::info('Checking transaction status:', [
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
        ]);

        // Convert the status to lowercase for case-insensitive comparison
        $status = strtolower($transaction->status);

        // Check if THIS SPECIFIC TRANSACTION is already refunded
        if ($status === 'refunded') {
            \Log::warning('Transaction already refunded:', [
                'transaction_id' => $transaction->id,
            ]);
            return response()->json([
                'message' => 'This transaction has already been refunded',
                'type' => 'error',
            ], 400);
        }

        // Find the user associated with this transaction
        $user = User::where('username', $transaction->user)->first();

        if (!$user) {
            \Log::error('User not found for transaction:', [
                'transaction_id' => $transaction->id,
                'username' => $transaction->user,
            ]);
            return response()->json([
                'message' => 'User not found',
                'type' => 'error',
            ], 404);
        }

        // Store previous balance for verification
        $previousBalance = $user->wallet_balance;
        $expectedBalance = $previousBalance + $transaction->amount;

        // Refund the amount to the user's wallet
        $user->wallet_balance = $expectedBalance;

        if (!$user->save()) {
            \Log::error('Failed to update user balance:', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id
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
        $transaction->status = 'Refunded';
        if (!$transaction->save()) {
            \Log::error('Failed to update transaction status:', [
                'transaction_id' => $transaction->id
            ]);
            throw new \Exception('Failed to update transaction status');
        }

        DB::commit();

        \Log::info('Transaction refunded successfully:', [
            'transaction_id' => $transaction->id,
            'new_status' => $transaction->status,
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
            'transaction_id' => $id ?? null
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
    public function show(WalletTransactions $walletTransactions)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WalletTransactions $walletTransactions)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WalletTransactions $walletTransactions)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WalletTransactions $walletTransactions)
    {
        //
    }
}
