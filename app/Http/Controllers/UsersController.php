<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UsersController extends Controller
{
    public function index(Request $request)
{
    // Default sorting column and direction
    $sortColumn = 'created_at'; // Default to 'created_at'
    $sortDirection = 'desc'; // Default to 'desc'

    // Handle sorting
    if ($request->has('sort')) {
        $sortParams = explode('_', $request->input('sort'));
        if (count($sortParams) === 2) {
            $sortColumn = $sortParams[0]; // Extract the column
            $sortDirection = $sortParams[1]; // Extract the direction
        }
    }

    // Validate the sort column and direction
    $validColumns = ['id', 'wallet_balance', 'created_at', 'status'];
    $validDirections = ['asc', 'desc'];

    if (!in_array($sortColumn, $validColumns)) {
        $sortColumn = 'created_at'; // Fallback to a valid column
    }

    if (!in_array($sortDirection, $validDirections)) {
        $sortDirection = 'desc'; // Fallback to a valid direction
    }

    // Get the search term from the request
    $searchTerm = $request->input('search');

    // Base query for all users
    $usersQuery = User::orderBy($sortColumn, $sortDirection);

    // Apply search filter if a search term is provided
    if ($searchTerm) {
        $usersQuery->where(function ($query) use ($searchTerm) {
            $columns = ['username', 'email', 'id', 'status', 'wallet_balance', 'full_name', 'phone_number'];
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$searchTerm}%");
            }
        });
    }

    // Paginate the results
    $users = $usersQuery->paginate(7);

    // Render the view and pass the necessary data
    return view('users.index', compact('users', 'searchTerm', 'sortColumn', 'sortDirection'));
}

    //Function to edit user
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('users.edit', compact('user'));
    }

    //Function to update user
    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);
    $user->update([
        'username' => $request->input('username'),
        'email' => $request->input('email'),
        'full_name' => $request->input('full_name'),
        'phone_number' => $request->input('phone_number'),
        'gender' => $request->input('gender'),
        'pin' => $request->input('pin'),
        'role' => $request->input('role'),
    ]);
    return redirect()->route('users.index')->with('success', 'User updated successfully');
}

   //Function to suspend user
    public function suspend($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'suspended']);
        return redirect()->route('users.index')->with('success', 'User suspended successfully');
    }

    //Function to block user
    public function block($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'blocked']);
        return redirect()->route('users.index')->with('success', 'User blocked successfully');
    }

    //Function to delete user
    public function destroy($id)
{
    $user = User::findOrFail($id);
    $user->delete();

    return redirect()->route('users.index')->with('success', 'User deleted successfully');
}
}