<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        
        // Update password and clear remember token
        $user->update([
            'password' => Hash::make($validated['password']),
            'remember_token' => null,
        ]);
        
        // Log out all other sessions
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Log the user back in with new session
        Auth::login($user);

        return back()->with('status', 'password-updated');
    }
}
