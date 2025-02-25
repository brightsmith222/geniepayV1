<?php

namespace App\Http\Controllers;

use App\Models\Transactions;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $transactions = Transactions::all();
    $dataTransactions = Transactions::where('service', 'Data')->get();
    $airtimeTransactions = Transactions::where('service', 'Airtime')->get();
    $cableTransactions = Transactions::where('service', 'Cable')->get();
    $electricityTransactions = Transactions::where('service', 'Electricity')->get();
    $examTransactions = Transactions::where('service', 'Exam')->get();

    return view('transaction.index', compact(
        'transactions',
        'dataTransactions',
        'airtimeTransactions',
        'cableTransactions',
        'electricityTransactions',
        'examTransactions'
    ));
}


    public function allTransactions(Request $request){

       

    }

    public function refund($id)
{
    $transaction = Transaction::find($id);

    if (!$transaction) {
        return redirect()->back()->with('error', 'Transaction not found.');
    }

    if ($transaction->status !== 'failed' && $transaction->status !== 'pending') {
        return redirect()->back()->with('error', 'Only failed or pending transactions can be refunded.');
    }

    // Perform the refund logic here (e.g., reverse payment, update balance)
    $transaction->status = 'refunded';
    $transaction->save();

    return redirect()->back()->with('success', 'Transaction refunded successfully.');
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
