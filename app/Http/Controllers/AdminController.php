<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users()
    {
        $users = User::paginate(20);
        return view('admin.users', compact('users'));
    }

    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:wrd_expert,wrap_director,alu_managing_atty,alu_law_clerk,alu_attorney,hydrology_expert,hu_admin,hu_law_clerk,interested_party,system_admin'
        ]);

        $user->update(['role' => $validated['role']]);
        
        return back()->with('success', 'User role updated successfully.');
    }

    public function approveUser(User $user)
    {
        $user->update(['is_active' => true]);
        return back()->with('success', 'User approved successfully.');
    }

    public function getPendingUsers()
    {
        return User::where('is_active', false)->get();
    }
}