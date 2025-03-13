<?php

namespace App\Http\Controllers;

use App\Models\Transactions;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\notify;
class TransactionsController extends Controller
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

    // Base query for all transactions
    $transactionsQuery = Transactions::orderBy($sortColumn, $sortDirection);

    // Apply search filter if a search term is provided
    if ($searchTerm) {
        $transactionsQuery->where(function ($query) use ($searchTerm) {
            $query->where('transaction_id', 'like', "%{$searchTerm}%")
                  ->orWhere('service', 'like', "%{$searchTerm}%")
                  ->orWhere('username', 'like', "%{$searchTerm}%")
                  ->orWhere('amount', 'like', "%{$searchTerm}%")
                  ->orWhere('status', 'like', "%{$searchTerm}%")
                  ->orWhere('phone_number', 'like', "%{$searchTerm}%");
        });
    }

    // Paginate the results
    $transactions = $transactionsQuery->paginate(7);

    // Fetch the required transactions based on service type with pagination
    $dataTransactions = Transactions::where('service', 'Data')->orderBy($sortColumn, $sortDirection)->paginate(7);
    $airtimeTransactions = Transactions::where('service', 'Airtime')->orderBy($sortColumn, $sortDirection)->paginate(7);
    $cableTransactions = Transactions::where('service', 'Cable')->orderBy($sortColumn, $sortDirection)->paginate(7);
    $electricityTransactions = Transactions::where('service', 'Electricity')->orderBy($sortColumn, $sortDirection)->paginate(7);
    $examTransactions = Transactions::where('service', 'Exam')->orderBy($sortColumn, $sortDirection)->paginate(7);

    // Render the view and pass the necessary data
    return view('transaction.index', compact(
        'transactions',
        'dataTransactions',
        'airtimeTransactions',
        'cableTransactions',
        'electricityTransactions',
        'examTransactions',
        'searchTerm'
    ));
}



    //Refund user failed transaction
    public function refund($id)
    {
        // Find the specific transaction by its ID
        $transaction = Transactions::findOrFail($id);
    
        // Convert the status to lowercase for case-insensitive comparison
        $status = strtolower($transaction->status);
    
        // Check if THIS SPECIFIC TRANSACTION is already refunded
        if ($status === 'refunded') {
            return response()->json([
                'message' => 'This transaction has already been refunded',
                'type' => 'error', // Error type for already refunded
            ], 400);
        }
    
        // Find the user associated with this transaction
        $user = User::where('username', $transaction->user)->first();
    
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'type' => 'error', // Error type for user not found
            ], 404);
        }
    
        // Refund the amount to the user's wallet
        $user->wallet_balance += $transaction->amount;
        $user->save();
    
        // Update THIS SPECIFIC TRANSACTION'S status to "refunded"
        $transaction->status = 'Refunded';
        $transaction->save();
    
        return response()->json([
            'message' => 'Refund successful',
            'type' => 'success', // Success type for successful refund
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
    public function show(Transactions $transactions)
    {
        //
    }

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
