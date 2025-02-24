<?php

namespace App\Http\Controllers;

use App\Models\WalletTransactions;
use Illuminate\Http\Request;

class WalletTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('wallet_transac.index');
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
