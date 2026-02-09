<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DocumentType;
use App\Models\Role;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users()
    {
        $users = User::orderBy('name')->paginate(50);
        return view('admin.users', compact('users'));
    }

    public function documentTypes()
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $documentTypes = DocumentType::with('roles')->orderBy('sort_order')->get();
        $roles = Role::where('is_active', true)->orderBy('name')->get();

        return view('admin.document-types', compact('documentTypes', 'roles'));
    }

    public function storeDocumentType(Request $request)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:document_types,code',
            'category' => 'required|in:case_creation,party_upload,system',
            'sort_order' => 'nullable|integer|min:0',
            'is_required' => 'sometimes|boolean',
            'is_pleading' => 'sometimes|boolean',
            'allows_multiple' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        $documentType = DocumentType::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'category' => $validated['category'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_required' => $request->boolean('is_required'),
            'is_pleading' => $request->boolean('is_pleading'),
            'allows_multiple' => $request->boolean('allows_multiple'),
            'is_active' => $request->boolean('is_active'),
        ]);

        if (!empty($validated['role_ids'])) {
            $documentType->roles()->sync($validated['role_ids']);
        }

        return back()->with('success', 'Document type created successfully.');
    }

    public function updateDocumentTypeRoles(Request $request, DocumentType $documentType)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $validated = $request->validate([
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        $documentType->roles()->sync($validated['role_ids'] ?? []);

        return response()->json(['success' => true]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:wrd_expert,wrap_director,alu_managing_atty,alu_law_clerk,alu_attorney,hydrology_expert,hu_admin,hu_law_clerk,unaffiliated,system_admin'
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
            'title' => 'nullable|string|max:255',
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
