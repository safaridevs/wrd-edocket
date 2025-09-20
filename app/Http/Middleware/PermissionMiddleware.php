<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $permissions = $user->getPermissions();
        
        if (!($permissions[$permission] ?? false)) {
            abort(403, 'Insufficient permissions.');
        }

        return $next($request);
    }
}