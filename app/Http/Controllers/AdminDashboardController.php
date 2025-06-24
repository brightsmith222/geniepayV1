<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transactions;
use App\Models\TransactionReport;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Carbon\Carbon;
use App\Services\VtpassService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    public function myAdmin(Request $request)
    {

        // Default filter (Today)
        $filter = $request->input('filter', 'all_time');

        // Get data based on the filter
        $data = $this->getFilteredData($filter);

        $data['greeting'] = $this->getGreeting() . ', ' . auth()->user()->username;

        // Add filter and transaction to the data array
        $data['filter'] = $filter;

        // Debugging: Log the data being passed to the view
        Log::info('Data passed to view:', $data);

        // Pass data to the view
        return view('dashboard', $data);
    }

    //greeting function
    public function getGreeting()
    {
        $hour = now()->format('H');

        if ($hour < 12) {
            return 'Good morning';
        } elseif ($hour < 16) {
            return 'Good afternoon';
        } else {
            return 'Good evening';
        }
    }

    // Get wallet balance from Vtpass API
    public function getWalletBalance(VtpassService $vtpass)
    {
        if ($vtpass->isVtpassEnabled()) {
            $headers = $vtpass->getHeaders();
        } else {
            return response()->json([
                'status' => false,
                'message' => 'API service is currently disabled.',
            ]);
        }

        $baseUrl = config('api.vtpass.base_url');
        $url = $baseUrl . "balance";
        $response = Http::withHeaders($headers)->withoutVerifying()->get($url);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balance',
                'error' => $response->json()
            ], 500);
        }

        $responseData = $response->json();

        // Check if the response has the expected structure
        if (!isset($responseData['contents']['balance'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid balance response structure',
                'response' => $responseData
            ], 500);
        }

        return response()->json([
            'success' => true,
            'balance' => number_format($responseData['contents']['balance'], 2),
            'raw_response' => $responseData
        ]);
    }


    // Get GladTidings wallet balance
    public function getGladWalletBalance()
    {
        try {
            $url = config('api.glad.base_url') . 'user/';
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . config('api.glad.api_key'),
            ];

            $response = Http::withHeaders($headers)->withoutVerifying()->post($url);
            $responseData = $response->json();

            // Check for the wallet_balance key in the response
            if (isset($responseData['user']['wallet_balance'])) {
                return response()->json([
                    'success' => true,
                    'balance' => number_format($responseData['user']['wallet_balance'], 2),
                    'raw_response' => $responseData
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid GladTidings balance response structure',
                    'response' => $responseData
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("GladTidings Wallet Balance Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GladTidings balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get Airtime balance from Artx API
    public function getArtxWalletBalance()
    {
        try {
            $salt = Str::random(40);
            $password = sha1(config('api.artx.password'));
            $passwordHash = hash('sha512', $salt . $password);

            $payload = [
                'auth' => [
                    'username' => config('api.artx.username'),
                    'salt' => $salt,
                    'password' => $passwordHash,
                ],
                'version' => 5,
                'command' => 'getBalance'
            ];

            $response = Http::withoutVerifying()
                ->post(config('api.artx.base_url'), $payload);

            $data = $response->json();
            Log::info('ARTX Wallet Balance Response:', [
                'status_code' => $response->status(),
                'body' => $data
            ]);

            if (($data['status']['type'] ?? 2) === 0 && isset($data['result']['value'])) {
                return response()->json([
                    'success' => true,
                    'balance' => number_format($data['result']['value'], 2),
                    'currency' => $data['result']['currency'] ?? 'NGN',
                    'raw_response' => $data
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $data['status']['name'] ?? 'Failed to retrieve ARTX balance',
                'response' => $data
            ], 500);
        } catch (\Exception $e) {
            Log::error("ARTX Wallet Balance Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving ARTX wallet balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function filterData(Request $request)
    {
        $filter = $request->input('filter');
        $type = $request->input('type'); // 'sales' or 'users'
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        // Validate dates for custom filter
        if ($filter === 'custom' && (!$startDate || !$endDate)) {
            return response()->json(['error' => 'Please provide both start and end dates.'], 400);
        }

        // Get data based on the filter
        $data = $this->getFilteredData($filter, $startDate, $endDate);

        // Return JSON response
        return response()->json($data);
    }

    private function getFilteredData($filter, $startDate = null, $endDate = null)
    {
        $now = Carbon::now();

        // Initialize variables for date ranges
        if ($filter === 'custom' && $startDate && $endDate) {
            // Use the provided custom date range
            try {
                $startDate = Carbon::createFromFormat('d/m/Y', $startDate)->startOfDay();
                $endDate = Carbon::createFromFormat('d/m/Y', $endDate)->endOfDay();
            } catch (\Exception $e) {
                // Handle invalid date format
                Log::error('Invalid date format provided:', ['startDate' => $startDate, 'endDate' => $endDate]);
                return [
                    'totalSales' => 0,
                    'totalDataSales' => 0,
                    'totalAirtimeSales' => 0,
                    'totalElectricitySales' => 0,
                    'totalCableSales' => 0,
                    'totalExamSales' => 0,
                    'totalUsers' => 0,
                    'activeUsers' => 0,
                    'suspendedUsers' => 0,
                    'blockedUsers' => 0,
                    'reportedTransactionsCount' => 0,
                    'previousTotalSales' => 0,
                    'previousTotalDataSales' => 0,
                    'previousTotalAirtimeSales' => 0,
                    'previousTotalElectricitySales' => 0,
                    'previousTotalCableSales' => 0,
                    'previousTotalExamSales' => 0,
                    'previousTotalUsers' => 0,
                    'previousActiveUsers' => 0,
                    'previousSuspendedUsers' => 0,
                    'previousBlockedUsers' => 0,
                    'previousReportedTransactionsCount' => 0,
                ];
            }
        } else {
            // Set date ranges based on the filter
            switch ($filter) {
                case 'today':
                    $startDate = $now->copy()->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $previousStartDate = $now->copy()->subDay()->startOfDay();
                    $previousEndDate = $now->copy()->subDay()->endOfDay();
                    break;
                case 'yesterday':
                    $startDate = $now->copy()->subDay()->startOfDay();
                    $endDate = $now->copy()->subDay()->endOfDay();
                    $previousStartDate = $now->copy()->subDays(2)->startOfDay();
                    $previousEndDate = $now->copy()->subDays(2)->endOfDay();
                    break;
                case 'this_month':
                    $startDate = $now->copy()->startOfMonth();
                    $endDate = $now->copy()->endOfMonth();
                    $previousStartDate = $now->copy()->subMonth()->startOfMonth();
                    $previousEndDate = $now->copy()->subMonth()->endOfMonth();
                    break;
                case 'last_month':
                    $startDate = $now->copy()->subMonth()->startOfMonth();
                    $endDate = $now->copy()->subMonth()->endOfMonth();
                    $previousStartDate = $now->copy()->subMonths(2)->startOfMonth();
                    $previousEndDate = $now->copy()->subMonths(2)->endOfMonth();
                    break;
                case 'this_year':
                    $startDate = $now->copy()->startOfYear();
                    $endDate = $now->copy()->endOfYear();
                    $previousStartDate = $now->copy()->subYear()->startOfYear();
                    $previousEndDate = $now->copy()->subYear()->endOfYear();
                    break;
                case 'last_year':
                    $startDate = $now->copy()->subYear()->startOfYear();
                    $endDate = $now->copy()->subYear()->endOfYear();
                    $previousStartDate = $now->copy()->subYears(2)->startOfYear();
                    $previousEndDate = $now->copy()->subYears(2)->endOfYear();
                    break;
                case 'all_time':
                    // No date range filter for "All Time"
                    $startDate = null;
                    $endDate = null;
                    $previousStartDate = null;
                    $previousEndDate = null;
                    break;
                default:
                    $startDate = $now->copy()->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $previousStartDate = $now->copy()->subDay()->startOfDay();
                    $previousEndDate = $now->copy()->subDay()->endOfDay();
                    break;
            }
        }

        // Fetch data for the current period
        $transactions = Transactions::when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
        })->where('status', 'successful')->get();

        $reportedTransactions = TransactionReport::when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
        })->get();

        $users = User::when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
        })->get();

        // Fetch data for the previous period
        $previousTransactions = Transactions::when($previousStartDate && $previousEndDate, function ($query) use ($previousStartDate, $previousEndDate) {
            return $query->whereBetween('created_at', [$previousStartDate, $previousEndDate]);
        })->get();

        $previousReportedTransactions = TransactionReport::when($previousStartDate && $previousEndDate, function ($query) use ($previousStartDate, $previousEndDate) {
            return $query->whereBetween('created_at', [$previousStartDate, $previousEndDate]);
        })->get();

        $previousUsers = User::when($previousStartDate && $previousEndDate, function ($query) use ($previousStartDate, $previousEndDate) {
            return $query->whereBetween('created_at', [$previousStartDate, $previousEndDate]);
        })->get();

        // Calculate statistics for the current period

        $totalSales = $transactions->sum('amount');
        $totalDataSales = $transactions->where('service', 'data')->sum('amount');
        $totalAirtimeSales = $transactions->where('service', 'airtime')->sum('amount');
        $totalElectricitySales = $transactions->where('service', 'electricity')->sum('amount');
        $totalCableSales = $transactions->where('service', 'cable')->sum('amount');
        $totalExamSales = $transactions->where('service', 'exam')->sum('amount');

        $totalUsers = $users->count();
        $activeUsers = $users->where('status', 'active')->count();
        $suspendedUsers = $users->where('status', 'suspended')->count();
        $blockedUsers = $users->where('status', 'blocked')->count();
        $reportedTransactionsCount = $reportedTransactions->count();

        // Calculate statistics for the previous period
        $previousTotalSales = $previousTransactions->where('status', 'successful')->sum('amount');
        $previousTotalDataSales = $previousTransactions->where('status', 'successful')->where('service', 'data')->sum('amount');
        $previousTotalAirtimeSales = $previousTransactions->where('status', 'successful')->where('service', 'airtime')->sum('amount');
        $previousTotalElectricitySales = $previousTransactions->where('status', 'successful')->where('service', 'electricity')->sum('amount');
        $previousTotalCableSales = $previousTransactions->where('status', 'successful')->where('service', 'cable')->sum('amount');
        $previousTotalExamSales = $previousTransactions->where('status', 'successful')->where('service', 'exam')->sum('amount');

        $previousTotalUsers = $previousUsers->count();
        $previousActiveUsers = $previousUsers->where('status', 'active')->count();
        $previousSuspendedUsers = $previousUsers->where('status', 'suspended')->count();
        $previousBlockedUsers = $previousUsers->where('status', 'blocked')->count();
        $previousReportedTransactionsCount = $previousReportedTransactions->count();

        // Return the data
        return [
            'totalSales' => $totalSales ?? 0,
            'totalDataSales' => $totalDataSales ?? 0,
            'totalAirtimeSales' => $totalAirtimeSales ?? 0,
            'totalElectricitySales' => $totalElectricitySales ?? 0,
            'totalCableSales' => $totalCableSales ?? 0,
            'totalExamSales' => $totalExamSales ?? 0,
            'totalUsers' => $totalUsers ?? 0,
            'activeUsers' => $activeUsers ?? 0,
            'suspendedUsers' => $suspendedUsers ?? 0,
            'blockedUsers' => $blockedUsers ?? 0,
            'reportedTransactionsCount' => $reportedTransactionsCount ?? 0,
            'previousTotalSales' => $previousTotalSales ?? 0,
            'previousTotalDataSales' => $previousTotalDataSales ?? 0,
            'previousTotalAirtimeSales' => $previousTotalAirtimeSales ?? 0,
            'previousTotalElectricitySales' => $previousTotalElectricitySales ?? 0,
            'previousTotalCableSales' => $previousTotalCableSales ?? 0,
            'previousTotalExamSales' => $previousTotalExamSales ?? 0,
            'previousTotalUsers' => $previousTotalUsers ?? 0,
            'previousActiveUsers' => $previousActiveUsers ?? 0,
            'previousSuspendedUsers' => $previousSuspendedUsers ?? 0,
            'previousBlockedUsers' => $previousBlockedUsers ?? 0,
            'previousReportedTransactionsCount' => $previousReportedTransactionsCount ?? 0,
        ];
    }

    public function getReportedTransactions()
    {
        // Fetch reported transactions with 'reported' status
        $reports = TransactionReport::where('status', 'reported')->get();

        return response()->json([
            'success' => true,
            'count' => $reports->count(),
            'reports' => $reports,
        ]);
    }
}
