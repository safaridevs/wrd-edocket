<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use LdapRecord\Laravel\Auth\ListensForLdapBindFailure;
use LdapRecord\Laravel\Auth\AuthenticatesUsers;

class CustomAuthController extends Controller
{
    use AuthenticatesUsers, ListensForLdapBindFailure;

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // First try LDAP authentication for AD users
        if ($this->attemptLdapLogin($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        // Fallback to regular authentication for parties/attorneys
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    protected function attemptLdapLogin(array $credentials)
    {
        try {
            // Try LDAP authentication
            if (Auth::guard('ldap')->attempt($credentials)) {
                $ldapUser = Auth::guard('ldap')->user();
                
                // Find or create local user record
                $user = User::where('email', $ldapUser->getEmail())->first();
                
                if (!$user) {
                    $user = User::create([
                        'name' => $ldapUser->getName(),
                        'email' => $ldapUser->getEmail(),
                        'password' => Hash::make('ldap_user'), // Placeholder password
                        'role' => 'alu_clerk', // Default role - admin can change later
                        'is_active' => true,
                    ]);
                }
                
                // Login the local user
                Auth::login($user);
                return true;
            }
        } catch (\Exception $e) {
            // LDAP failed, continue to regular auth
            \Log::info('LDAP authentication failed: ' . $e->getMessage());
        }
        
        return false;
    }

    protected function handleLdapBindError($message, $code = null)
    {
        \Log::error('LDAP Bind Error: ' . $message);
    }
}