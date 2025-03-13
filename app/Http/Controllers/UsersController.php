<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        // Default sorting column and direction
    $sortColumn = request('sort', 'created_at'); // Default to 'created_at'
    $sortDirection = request('direction', 'desc'); // Default to 'desc'

    // Validate the sort column to prevent SQL injection
    $validColumns = ['id', 'amount', 'created_at', 'status'];
    if (!in_array($sortColumn, $validColumns)) {
        $sortColumn = 'created_at'; // Fallback to a valid column
    }

    $searchTerm = $request->input('search'); // Get the search term from the request

    $users = User::query()
        ->when($searchTerm, function ($query, $searchTerm) {
            $columns = ['username', 'email', 'id', 'status', 'wallet_balance', 'full_name', 'phone_number']; // Add all columns you want to search
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$searchTerm}%");
            }
            return $query;
        })->paginate(7); 

        return view('users.index', compact('users'));
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