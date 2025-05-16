<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserSearchController extends Controller
{
    public function search(Request $request)
    {
        // Check if query exists
        if (!$request->has('query')) {
            return response()->json(['error' => 'Query parameter is missing'], 400);
        }

        $searchTerm = $request->query('query');

        // Fetch users matching the search term
        $users = User::where('username', 'like', '%' . $searchTerm . '%')
            ->select('id', 'username') // Select only required fields
            ->limit(5)
            ->get();

        return response()->json($users); // Ensure JSON response
    }

    
}
