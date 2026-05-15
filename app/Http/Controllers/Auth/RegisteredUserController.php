<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($this->emailExistsInLdap($request->email)) {
            return back()->withErrors([
                'email' => 'This email belongs to an OSE account. Please sign in with your network username and password instead of creating a new account.'
            ])->withInput();
        }

        // Check if email exists in the case contact directory.
        $person = \App\Models\Person::where('email', $request->email)->first();
        
        if (!$person) {
            return back()->withErrors([
                'email' => 'This email is not associated with any case. Only parties and attorneys involved in cases can register.'
            ])->withInput();
        }
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'party',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    protected function emailExistsInLdap(string $email): bool
    {
        try {
            $connection = new \LdapRecord\Connection([
                'hosts' => config('ldap.connections.default.hosts'),
                'username' => config('ldap.connections.default.username'),
                'password' => config('ldap.connections.default.password'),
                'port' => config('ldap.connections.default.port'),
                'base_dn' => config('ldap.connections.default.base_dn'),
                'timeout' => config('ldap.connections.default.timeout'),
                'use_ssl' => config('ldap.connections.default.use_ssl'),
                'use_tls' => config('ldap.connections.default.use_tls'),
                'options' => config('ldap.connections.default.options', []),
            ]);

            $connection->connect();

            foreach (['mail', 'userPrincipalName'] as $attribute) {
                $ldapUser = $connection->query()
                    ->where('objectClass', '=', 'user')
                    ->where($attribute, '=', $email)
                    ->first();

                if ($ldapUser) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            \Log::error('LDAP registration check failed: ' . $e->getMessage());

            return true;
        }

        return false;
    }
}
