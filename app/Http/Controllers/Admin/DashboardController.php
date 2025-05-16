<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transactions;
use App\Models\User;
use App\Mail\SendOtp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

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
        $remember = $request->has('remember') ? true : false;
        if (Auth::attempt($credentials, $remember)) {
            if (Auth::user()->role == '1') {
                $user = Auth::user();

                // Generate OTP
                $otp = rand(100000, 999999);
                $user->otp = $otp;
                $user->otp_expires_at = now()->addMinutes(5);
                $user->save();

                // Get the user's IP address
                $ip = request()->ip();

                // Get the user's device information
                $agent = new Agent();
                $device = $agent->device();
                $platform = $agent->platform();
                $browser = $agent->browser();
                $deviceInfo = "{$device} ({$platform}, {$browser})";

                // Get the user's location (optional, requires a geolocation service)
                $location = $this->getLocationFromIp($ip); // Implement this method using a geolocation API
                // Send OTP via email
                Mail::to($user->email)->send(new SendOtp($otp, $deviceInfo, $ip, $location));

                // Logout for now and save user ID to session
                Auth::logout();
                session(['otp_user_id' => $user->id]);

                return view('admin.verify-otp');
            }
        }



        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }


    public function showOtpForm()
    {
        if (!session('otp_user_id')) {
            return redirect()->route('admin.login');
        }

        return view('admin.verify-otp');
    }

    //Verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $userId = session('otp_user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('admin.login')->withErrors(['otp' => 'Session expired, please log in again.']);
        }

        if ($user->otp === $request->otp && now()->lt($user->otp_expires_at)) {
            Auth::login($user);
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->save();
            session()->forget('otp_user_id');
            return redirect()->route('dashboard');
        }

        // OTP is invalid or expired, stay on OTP form
        return redirect()->route('admin.otp.verify.page')
            ->withErrors(['otp' => 'Invalid or expired OTP.'])
            ->withInput();
    }

    public function resendOtp(Request $request)
    {
        $userId = session('otp_user_id');
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session expired, please login again.'
            ], 401);
        }

        // Regenerate OTP
        $otp = rand(100000, 999999);
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        // Send OTP email
        try {
            // Get the user's IP address
            $ip = request()->ip();

            // Get the user's device information
            $agent = new Agent();
            $device = $agent->device();
            $platform = $agent->platform();
            $browser = $agent->browser();
            $deviceInfo = "{$device} ({$platform}, {$browser})";
            $location = $this->getLocationFromIp($ip);

            Mail::to($user->email)->send(new SendOtp($otp, $deviceInfo, $ip, $location));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send OTP. Please try again later.'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'A new OTP has been sent to your email.'
        ]);
    }

    // Get location from IP address (example using ip-api.com)
    private function getLocationFromIp($ip)
{
    // Example using ip-api.com (free geolocation API)
    $response = @file_get_contents("http://ip-api.com/json/{$ip}");
    $data = json_decode($response, true);

    if ($data && $data['status'] === 'success') {
        return "{$data['city']}, {$data['regionName']}, {$data['country']}";
    }

    return 'Unknown Location';
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
