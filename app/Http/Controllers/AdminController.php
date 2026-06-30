<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DocumentType;
use App\Models\Person;
use App\Models\Role;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
            'account_type' => 'nullable|in:ldap,local',
            'per_page' => 'nullable|integer|in:25,50,100,200',
        ]);

        $roles = Role::where('is_active', true)->orderBy('display_name')->get();
        $perPage = $filters['per_page'] ?? 50;

        $users = User::with('roleRelation')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('sam_account_name', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['role'] ?? null), function ($query) use ($filters) {
                $role = $filters['role'];

                $query->where(function ($query) use ($role) {
                    $query->where('role', $role)
                        ->orWhereHas('roleRelation', fn ($roleQuery) => $roleQuery->where('name', $role));
                });
            })
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when(($filters['account_type'] ?? null) === 'ldap', fn ($query) => $query->where('is_ldap_user', true))
            ->when(($filters['account_type'] ?? null) === 'local', fn ($query) => $query->where('is_ldap_user', false))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users', compact('users', 'roles', 'filters'));
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

    public function notificationDelivery()
    {
        if (!auth()->user()->canManageUsers()) {
            abort(403);
        }

        $notifications = Notification::with('case')
            ->whereIn('email_status', ['failed', 'bounced'])
            ->orderBy('sent_at', 'desc')
            ->paginate(50);

        return view('admin.notifications', compact('notifications'));
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
            'role' => 'required|exists:roles,name'
        ]);

        $user->update(['role' => $validated['role']]);
        
        return back()->with('success', 'User role updated successfully.');
    }

    public function storeUser(Request $request)
    {
        $requiresPersonProfile = $request->input('account_type') === 'local'
            || $request->input('role') === 'external_attorney';

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => ['required', Rule::exists('roles', 'name')->where('is_active', true)],
            'account_type' => 'required|in:ldap,local',
            'password' => ['nullable', 'required_if:account_type,local', 'confirmed', Rules\Password::defaults()],
            'is_active' => 'sometimes|boolean',
            'first_name' => [Rule::requiredIf($requiresPersonProfile), 'nullable', 'string', 'max:255'],
            'middle_name' => 'nullable|string|max:255',
            'last_name' => [Rule::requiredIf($requiresPersonProfile), 'nullable', 'string', 'max:255'],
            'organization' => 'nullable|string|max:255',
            'phone_mobile' => [$requiresPersonProfile ? 'required_without:phone_office' : 'nullable', 'nullable', 'string', 'max:20'],
            'phone_office' => [$requiresPersonProfile ? 'required_without:phone_mobile' : 'nullable', 'nullable', 'string', 'max:20'],
            'address_line1' => [Rule::requiredIf($requiresPersonProfile), 'nullable', 'string', 'max:255'],
            'address_line2' => 'nullable|string|max:255',
            'city' => [Rule::requiredIf($requiresPersonProfile), 'nullable', 'string', 'max:255'],
            'state' => [Rule::requiredIf($requiresPersonProfile), 'nullable', 'string', 'max:50'],
            'zip' => [Rule::requiredIf($requiresPersonProfile), 'nullable', 'string', 'max:20'],
            'notes' => 'nullable|string|max:1000',
        ]);

        $email = strtolower(trim($validated['email']));

        DB::transaction(function () use ($validated, $request, $requiresPersonProfile, $email) {
            if ($requiresPersonProfile) {
                Person::updateOrCreate(
                    ['email' => $email],
                    [
                        'type' => 'individual',
                        'first_name' => $validated['first_name'],
                        'middle_name' => $validated['middle_name'] ?? null,
                        'last_name' => $validated['last_name'],
                        'organization' => $validated['organization'] ?? null,
                        'title' => $validated['title'] ?? null,
                        'phone_mobile' => $validated['phone_mobile'] ?? null,
                        'phone_office' => $validated['phone_office'] ?? null,
                        'address_line1' => $validated['address_line1'],
                        'address_line2' => $validated['address_line2'] ?? null,
                        'city' => $validated['city'],
                        'state' => $validated['state'],
                        'zip' => $validated['zip'],
                        'notes' => $validated['notes'] ?? null,
                    ]
                );
            }

            User::create([
                'name' => $validated['name'],
                'title' => $validated['title'] ?? null,
                'email' => $email,
                'role' => $validated['role'],
                'password' => Hash::make($validated['account_type'] === 'local' ? $validated['password'] : Str::random(48)),
                'is_ldap_user' => $validated['account_type'] === 'ldap',
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return back()->with('success', 'User account created successfully.');
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
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'title' => $user->title,
            'email' => $user->email,
            'role' => $user->getCurrentRole(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|exists:roles,name'
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

        DB::transaction(function () use ($user) {
            $user->update(['is_active' => false]);
            $user->delete();
        });

        return back()->with('success', 'User deactivated and deleted successfully.');
    }
}
