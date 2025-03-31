<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if (Auth::user()->role == '1') {
                Log::info('Admin access granted', [
                    'user_id' => Auth::id(),
                    'route' => $request->path()
                ]);
                return $next($request);
            }
            
            Log::warning('Non-admin access attempt', [
                'user_id' => Auth::id(),
                'route' => $request->path()
            ]);
            Auth::logout();
            return redirect()->route('admin.login')->with('error', 'You do not have admin privileges.');
        }

        Log::info('Unauthenticated access attempt', ['route' => $request->path()]);
        return redirect()->route('admin.login')->with('error', 'Please login to access this page.');
    }
}