<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUsersWithRoles()
    {
        return User::select('id', 'name', 'email', 'role', 'initials')
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->groupBy('role');
    }

    public function getUsersByRole(string $role)
    {
        return User::where('role', $role)
            ->select('id', 'name', 'email', 'role', 'initials')
            ->get();
    }

    public function getLoginCapableUsers()
    {
        return User::select('id', 'name', 'email', 'role', 'initials')
            ->whereNotNull('email')
            ->whereNotNull('password')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'initials' => $user->initials,
                    'permissions' => $user->getPermissions()
                ];
            });
    }
}