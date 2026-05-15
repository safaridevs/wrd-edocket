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
            $connection = new \LdapRecord\Connection($this->ldapConnectionConfig());
            
            $connection->connect();
            
            $identifier = trim((string) $credentials['email']);
            $ldapUser = $this->findLdapUser($connection, $identifier);
                
            if (!$ldapUser) {
                \Log::info('LDAP user not found: ' . $identifier);
                return false;
            }
            
            if (!$this->bindLdapUser($ldapUser, $credentials['password'], $identifier)) {
                return false;
            }
            
            // Create/find local user by email or sAMAccountName
            $email = $this->ldapFirst($ldapUser, 'mail') ?: $this->ldapFirst($ldapUser, 'userPrincipalName');
            $samAccountName = $this->ldapFirst($ldapUser, 'sAMAccountName');
            
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
                    'name' => $this->ldapFirst($ldapUser, 'displayName') ?: $this->ldapFirst($ldapUser, 'cn') ?: $samAccountName ?: 'LDAP User',
                    'email' => $email,
                    'sam_account_name' => $samAccountName,
                    'password' => Hash::make('ldap_user'),
                    'role' => 'interested_party',
                    'title' => $this->ldapFirst($ldapUser, 'title'),
                    'is_ldap_user' => true,
                ]);
            } elseif ($user && !$user->sam_account_name && $samAccountName) {
                // Update existing user with sAMAccountName if missing
                $user->update(['sam_account_name' => $samAccountName]);
            }

            if (!$user) {
                \Log::error('LDAP authentication succeeded but no local user could be resolved.', [
                    'identifier' => $identifier,
                    'dn' => $this->ldapDn($ldapUser),
                ]);

                return false;
            }
            
            Auth::login($user);
            return true;
            
        } catch (\Exception $e) {
            \Log::error('LDAP authentication failed: ' . $e->getMessage());
        }
        
        return false;
    }

    protected function ldapConnectionConfig(array $overrides = []): array
    {
        return array_merge([
            'hosts' => config('ldap.connections.default.hosts'),
            'username' => config('ldap.connections.default.username'),
            'password' => config('ldap.connections.default.password'),
            'port' => config('ldap.connections.default.port'),
            'base_dn' => config('ldap.connections.default.base_dn'),
            'timeout' => config('ldap.connections.default.timeout'),
            'use_ssl' => config('ldap.connections.default.use_ssl'),
            'use_tls' => config('ldap.connections.default.use_tls'),
            'options' => config('ldap.connections.default.options', []),
        ], $overrides);
    }

    protected function findLdapUser(\LdapRecord\Connection $connection, string $identifier): mixed
    {
        $attributes = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? ['mail', 'userPrincipalName']
            : ['sAMAccountName', 'userPrincipalName', 'mail'];

        foreach ($attributes as $attribute) {
            $user = $connection->query()
                ->where('objectClass', '=', 'user')
                ->where($attribute, '=', $identifier)
                ->first();

            if ($user) {
                return $user;
            }
        }

        return null;
    }

    protected function bindLdapUser(mixed $ldapUser, string $password, string $identifier): bool
    {
        $bindNames = array_filter(array_unique([
            $this->ldapDn($ldapUser),
            $this->ldapFirst($ldapUser, 'userPrincipalName'),
            $identifier,
        ]));

        foreach ($bindNames as $bindName) {
            try {
                (new \LdapRecord\Connection($this->ldapConnectionConfig([
                    'username' => $bindName,
                    'password' => $password,
                ])))->connect();

                \Log::info('LDAP authentication successful');

                return true;
            } catch (\Exception $e) {
                \Log::warning('LDAP bind attempt failed: ' . $e->getMessage(), [
                    'bind_name' => $bindName,
                ]);
            }
        }

        return false;
    }

    protected function ldapFirst(mixed $ldapUser, string $attribute): ?string
    {
        if ($ldapUser instanceof \LdapRecord\Models\Model) {
            return $ldapUser->getFirstAttribute($attribute);
        }

        if (!is_array($ldapUser)) {
            return null;
        }

        $value = $ldapUser[$attribute] ?? $ldapUser[strtolower($attribute)] ?? null;

        if (is_array($value)) {
            return $value[0] ?? null;
        }

        return $value;
    }

    protected function ldapDn(mixed $ldapUser): ?string
    {
        if ($ldapUser instanceof \LdapRecord\Models\Model) {
            return $ldapUser->getDn();
        }

        return is_array($ldapUser)
            ? ($ldapUser['dn'] ?? $this->ldapFirst($ldapUser, 'distinguishedName'))
            : null;
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
