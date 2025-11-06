<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users()
    {
        $users = User::orderBy('name')->paginate(50);
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

    public function edit(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,alu_manager,alu_atty,alu_clerk,hu_admin,hu_clerk,hydrology_expert,wrd,party'
        ]);

        $user->update($validated);
        
        return response()->json(['success' => true]);
    }

    public function deactivate(User $user)
    {
        $user->update(['is_active' => false]);
        return back()->with('success', 'User deactivated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        
        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }
}