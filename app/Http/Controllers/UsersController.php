<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transactions;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        // Default sorting
        $sortColumn = $request->get('sort_column', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $searchTerm = $request->get('search');
        
        // Validate sort parameters
        $validColumns = ['id', 'username', 'email', 'full_name', 'wallet_balance', 'created_at', 'status'];
        $validDirections = ['asc', 'desc'];
        
        if (!in_array($sortColumn, $validColumns)) {
            $sortColumn = 'created_at';
        }
        
        if (!in_array($sortDirection, $validDirections)) {
            $sortDirection = 'desc';
        }

        
        // Query
        $users = User::query()
        ->when($searchTerm, function($query) use ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('username', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('full_name', 'like', "%{$searchTerm}%");
            });
        })
        ->orderBy($sortColumn, $sortDirection)
        ->paginate(7)->onEachSide(1)
        ->appends([
            'search' => $searchTerm,
            'sort_column' => $sortColumn,
            'sort_direction' => $sortDirection
        ]);

    if ($request->ajax()) {
        return response()->json([
            'table' => view('users.partials.table', compact('users'))->render(),
            'pagination' => $users->links('vendor.pagination.bootstrap-4')->render()
        ]);
    }

        // Regular response
        return view('users.index', compact('users', 'searchTerm', 'sortColumn', 'sortDirection'));
    }


    //Function to edit user
    public function edit($id)
{
    $user = User::findOrFail($id);

    $transactions = Transactions::where('username', $user->username)
                                ->latest()
                                ->take(5)
                                ->get();

    return view('users.edit', compact('user', 'transactions'));
    
}




public function fetchTransactions($id)
{
    // Find the user by ID
    $user = User::findOrFail($id);

    // Fetch the transactions associated with the user
    $transactions = $user->transactions()->latest()->take(5)->get();

    // Return the view with transactions data
    return view('users.partials.last-transactions', compact('transactions'));
}



    //Function to update user
    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);
    $user->update($request->all());

    flash()->success("User updated successful");

    if ($request->ajax()) {
        return response()->json(['success' => true]);
    }

    return redirect()->route('users.index');
}

    

public function suspend($id)
{
    try {
        $user = User::findOrFail($id);
        $user->update(['status' => 'suspended']);
        
        return response()->json([
            'success' => true,
            'message' => 'User suspended successfully',
            'status' => 'suspended',
            'user_id' => $id
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to suspend user: ' . $e->getMessage()
        ], 500);
    }
}

public function unsuspend($id)
{
    try {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);
        
        return response()->json([
            'success' => true,
            'message' => 'User unsuspended successfully',
            'status' => 'active',
            'user_id' => $id
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to unsuspend user: ' . $e->getMessage()
        ], 500);
    }
}

public function block($id)
{
    try {
        $user = User::findOrFail($id);
        $user->update(['status' => 'blocked']);
        
        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully',
            'status' => 'blocked',
            'user_id' => $id
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to block user: ' . $e->getMessage()
        ], 500);
    }
}

public function unblock($id)
{
    try {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);
        
        return response()->json([
            'success' => true,
            'message' => 'User unblocked successfully',
            'status' => 'active',
            'user_id' => $id
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to unblock user: ' . $e->getMessage()
        ], 500);
    }
}

public function destroy($id)
{
    try {
        $user = User::findOrFail($id);
        
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin users cannot be deleted'
            ], 403);
        }

        $user->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete user: ' . $e->getMessage()
        ], 500);
    }
}


}