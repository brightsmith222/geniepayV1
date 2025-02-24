<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transactions;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', data: [
            'totalRevenue' => Transactions::where('status', 'completed')->sum('amount'),
            'totalTransactions' => Transactions::count(),
            'activeUsers' => User::where('status', 'active')->count(),
            'failedTransactions' => Transactions::where('status', 'failed')->count(),
            'recentTransactions' => Transactions::latest()->take(5)->get()
        ]);
    }

     // Show Admin Login Page
     public function showLoginForm()
     {
         return view('admin.login');
     }
 
     // Handle Admin Login
     public function login(Request $request)
     {
         $credentials = $request->only('email', 'password');
 
         if (Auth::guard('auth')->attempt($credentials)) {
             return redirect()->route('admin.dashboard');
         }
 
         return back()->withErrors(['error' => 'Invalid email or password.']);
     }
 
     // Handle Admin Logout
     public function logout()
     {
         Auth::guard('auth')->logout();
        //  return redirect()->route('admin.login');
        return view('admin.login');
     }

     public function myAdmin(){
        return view('dashboard');
     }

}
