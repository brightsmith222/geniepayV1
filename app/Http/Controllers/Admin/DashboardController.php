<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transactions;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        Log::info('Admin accessed dashboard', ['user_id' => Auth::id()]);
        return view('dashboard');
    }

    // Show Admin Login Page
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->role == '1') {
            return redirect()->route('dashboard');
        }
        return view('admin.login');
    }

    // Handle Admin Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Check user role using your middleware logic
            if (Auth::user()->role == '1') {
                Log::info('Admin login successful', ['user_id' => Auth::id()]);
                return redirect()->intended(route('dashboard'));
            }
            
            // If not admin, log them out
            Auth::logout();
            Log::warning('Non-admin user attempted login', ['email' => $request->email]);
            return back()->withErrors([
                'email' => 'You do not have admin privileges.',
            ]);
        }

        Log::warning('Failed login attempt', ['email' => $request->email]);
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    // Handle Admin Logout
    public function logout(Request $request)
    {
        Log::info('Admin logout', ['user_id' => Auth::id()]);
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login');
    }
}