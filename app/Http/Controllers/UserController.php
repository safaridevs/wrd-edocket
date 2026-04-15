<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUsersWithRoles()
    {
        return User::with('roleRelation')
            ->select('id', 'name', 'email', 'role', 'role_id', 'initials')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $user->role = $user->getCurrentRole();
                return $user;
            })
            ->groupBy('role');
    }

    public function getUsersByRole(string $role)
    {
        return User::whereCurrentRole($role)
            ->select('id', 'name', 'email', 'role', 'role_id', 'initials')
            ->with('roleRelation')
            ->get();
    }

    public function getLoginCapableUsers()
    {
        return User::with('roleRelation')->select('id', 'name', 'email', 'role', 'role_id', 'initials')
            ->whereNotNull('email')
            ->whereNotNull('password')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getCurrentRole(),
                    'initials' => $user->initials,
                    'permissions' => $user->getPermissions()
                ];
            });
    }
}
