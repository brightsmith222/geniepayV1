<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Transactions;
use App\Models\User;

class ProcessedTransactions extends Component
{


    
    //Refund user failed transaction

   /* public function refund($id)
{
    // Find the specific transaction by its ID
    $transaction = Transactions::findOrFail($id);

    // Log the current status for debugging
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
        return response()->json(['message' => 'This transaction has already been refunded'], 400);
    }

    // Find the user associated with this transaction
    $user = User::where('username', $transaction->username)->first();

    if (!$user) {
        \Log::error('User not found for transaction:', [
            'transaction_id' => $transaction->id,
            'username' => $transaction->username,
        ]);
        return response()->json(['message' => 'User not found'], 404);
    }

    // Refund the amount to the user's wallet
    $user->wallet_balance += $transaction->amount;
    $user->save();

    // Update THIS SPECIFIC TRANSACTION'S status to "refunded"
    $transaction->status = 'Refunded';
    $transaction->save();

    // Log the updated status for debugging
    \Log::info('Transaction refunded successfully:', [
        'transaction_id' => $transaction->id,
        'new_status' => $transaction->status,
    ]);

    return response()->json(['message' => 'Refund successful'], 200);
}*/
/*
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


public function render()
{
    // Default sorting column and direction
    $sortColumn = request('sort', 'created_at'); // Default to 'created_at'
    $sortDirection = request('direction', 'desc'); // Default to 'desc'

    // Validate the sort column to prevent SQL injection
    $validColumns = ['id', 'amount', 'created_at', 'status'];
    if (!in_array($sortColumn, $validColumns)) {
        $sortColumn = 'created_at'; // Fallback to a valid column
    }
    
    // Fetch the required transactions based on service type with pagination
    $transactions = Transactions::orderBy($sortColumn, $sortDirection)->paginate(7);
    $dataTransactions = Transactions::where('service', 'Data')->orderBy($sortColumn, $sortDirection)->paginate(7);
    $airtimeTransactions = Transactions::where('service', 'Airtime')->orderBy($sortColumn, $sortDirection)->paginate(7);
    $cableTransactions = Transactions::where('service', 'Cable')->orderBy($sortColumn, $sortDirection)->paginate(7);
    $electricityTransactions = Transactions::where('service', 'Electricity')->orderBy($sortColumn, $sortDirection)->paginate(7);
    $examTransactions = Transactions::where('service', 'Exam')->orderBy($sortColumn, $sortDirection)->paginate(7);

    // Render the view and pass the necessary data
    return view('livewire.processed-transactions', compact(
        'transactions',
        'dataTransactions',
        'airtimeTransactions',
        'cableTransactions',
        'electricityTransactions',
        'examTransactions'
    ));
}
    */

}