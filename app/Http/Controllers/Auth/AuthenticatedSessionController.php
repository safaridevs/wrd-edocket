<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        // First try LDAP authentication for AD users
        if ($this->attemptLdapLogin($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Fallback to regular authentication
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    protected function attemptLdapLogin(array $credentials)
    {
        try {
            $appUtils = app('ApplicationUtils');
            $connection = new \LdapRecord\Connection([
                'hosts' => [config('ldap.connections.default.hosts.0')],
                'username' => $appUtils->handleProperty(env('LDAP_USERNAME')),
                'password' => $appUtils->handleProperty(env('LDAP_PASSWORD')),
                'port' => config('ldap.connections.default.port'),
                'base_dn' => config('ldap.connections.default.base_dn'),
                'timeout' => config('ldap.connections.default.timeout'),
                'use_ssl' => config('ldap.connections.default.use_ssl'),
                'use_tls' => config('ldap.connections.default.use_tls'),
            ]);
            
            $connection->connect();
            
            // Search for user by email or sAMAccountName
            $query = $connection->query()->where('objectClass', '=', 'user');
            
            if (filter_var($credentials['email'], FILTER_VALIDATE_EMAIL)) {
                // Input is email format
                $query->where('mail', '=', $credentials['email']);
            } else {
                // Input is likely sAMAccountName
                $query->where('sAMAccountName', '=', $credentials['email']);
            }
            
            $ldapUser = $query->first();
                
            if (!$ldapUser) {
                \Log::info('LDAP user not found: ' . $credentials['email']);
                return false;
            }
            
            // Test authentication with user's DN
            $authConnection = new \LdapRecord\Connection([
                'hosts' => [config('ldap.connections.default.hosts.0')],
                'username' => $ldapUser['dn'],
                'password' => $credentials['password'],
                'port' => config('ldap.connections.default.port'),
                'base_dn' => config('ldap.connections.default.base_dn'),
                'timeout' => config('ldap.connections.default.timeout'),
                'use_ssl' => config('ldap.connections.default.use_ssl'),
                'use_tls' => config('ldap.connections.default.use_tls'),
            ]);
            
            $authConnection->connect();
            \Log::info('LDAP authentication successful');
            
            // Create/find local user by email or sAMAccountName
            $email = $ldapUser['mail'][0] ?? null;
            $samAccountName = $ldapUser['sAMAccountName'][0] ?? null;
            
            // Find user by email first, then by sAMAccountName if not found
            $user = null;
            if ($email) {
                $user = User::where('email', $email)->first();
            }
            if (!$user && $samAccountName) {
                $user = User::where('sam_account_name', $samAccountName)->first();
            }
            
            if (!$user && $email) {
                $user = User::create([
                    'name' => $ldapUser['cn'][0] ?? 'LDAP User',
                    'email' => $email,
                    'sam_account_name' => $samAccountName,
                    'password' => Hash::make('ldap_user'),
                    'role' => 'unaffiliated',
                    'title' => $ldapUser['title'][0] ?? null,
                    'is_ldap_user' => true,
                ]);
            } elseif ($user && !$user->sam_account_name && $samAccountName) {
                // Update existing user with sAMAccountName if missing
                $user->update(['sam_account_name' => $samAccountName]);
            }
            
            Auth::login($user);
            return true;
            
        } catch (\Exception $e) {
            \Log::error('LDAP authentication failed: ' . $e->getMessage());
        }
        
        return false;
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
