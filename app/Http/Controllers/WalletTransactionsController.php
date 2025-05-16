<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\WalletTransactions;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WalletTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    // Get and validate sort parameters
    $sortColumn = $request->get('sort_column', 'created_at');
    $sortDirection = $request->get('sort_direction', 'desc');
    
    // Validate columns and directions
    $validColumns = ['id', 'amount', 'created_at', 'status'];
    $validDirections = ['asc', 'desc'];
    
    if (!in_array($sortColumn, $validColumns)) {
        $sortColumn = 'created_at';
    }
    
    if (!in_array($sortDirection, $validDirections)) {
        $sortDirection = 'desc';
    }

    // Get search term
    $searchTerm = $request->get('search');

    // Base query
    $query = WalletTransactions::query()
        ->when($searchTerm, function($query) use ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('transaction_id', 'like', "%{$searchTerm}%")
                  ->orWhere('service', 'like', "%{$searchTerm}%")
                  ->orWhere('user', 'like', "%{$searchTerm}%")
                  ->orWhere('amount', 'like', "%{$searchTerm}%")
                  ->orWhere('status', 'like', "%{$searchTerm}%");
            });
        })
        ->orderBy($sortColumn, $sortDirection);

    // Paginate results
    $walletTransactions = $query->paginate(7)->onEachSide(1)
        ->appends([
            'search' => $searchTerm,
            'sort_column' => $sortColumn,
            'sort_direction' => $sortDirection
        ]);

    // AJAX response
    if ($request->ajax()) {
        return response()->json([
            'table' => view('wallet_transac.partials.table', compact('walletTransactions'))->render(),
            'pagination' => $walletTransactions->links('vendor.pagination.bootstrap-4')->render()
        ]);
    }

    // Regular response
    return view('wallet_transac.index', compact('walletTransactions', 'searchTerm', 'sortColumn', 'sortDirection'));
}

public function walletrefund($id)
{
    DB::beginTransaction();
    
    try {
        // Find the specific transaction by its ID
        $transaction = WalletTransactions::findOrFail($id);

        Log::info('Checking transaction status:', [
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
        ]);

        // Convert the status to lowercase for case-insensitive comparison
        $status = strtolower($transaction->status);

        // Check if THIS SPECIFIC TRANSACTION is already refunded
        if ($status === 'refunded') {
            Log::warning('Transaction already refunded:', [
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
            Log::error('User not found for transaction:', [
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
            Log::error('Failed to update user balance:', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id
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
        $transaction->status = 'Refunded';
        if (!$transaction->save()) {
            Log::error('Failed to update transaction status:', [
                'transaction_id' => $transaction->id
            ]);
            throw new \Exception('Failed to update transaction status');
        }

        DB::commit();

        Log::info('Transaction refunded successfully:', [
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
        Log::error('Refund failed:', [
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
