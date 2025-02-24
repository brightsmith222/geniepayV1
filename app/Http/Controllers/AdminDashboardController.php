<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transactions;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function myAdmin(){
        return view('dashboard',
        // data: [
        //     'totalRevenue' => Transactions::where('status', 'successful')->sum('amount'),
        //     'totalTransactions' => Transactions::count(),
        //     'activeUsers' => User::where('status', 'active')->count(),
        //     'failedTransactions' => Transactions::where('status', 'failed')->count(),
        //     'recentTransactions' => Transactions::latest()->take(5)->get()
        // ]
    );
     }
}
