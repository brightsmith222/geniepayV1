<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;


class AdminMiddleWare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) { // Check if the user is authenticated
            if ($request->user()->role == '1') { // Check if the user role is '1' (admin)
                Log::info('In the side of AdminMiddleWare as role 1.');
                return $next($request);
            } else {
                Log::info('In the side of AdminMiddleWare Login to dashboard.');
                return redirect('/login')->withErrors('You are not an admin');
                // return new RedirectResponse('/login', 302, ['errors' => 'You are not logged in']);
            }
        } else {
            Log::info('In the side of AdminMiddleWare logout.');
            return redirect('/login')->withErrors('You are not logged in');
            // return new RedirectResponse('/login', 302, ['errors' => 'You are not logged in']);
          
        }
       
        
    }
}
