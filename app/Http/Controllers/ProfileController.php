<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Attorney;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
            
            // Update related Person and Attorney records
            Person::where('email', $user->getOriginal('email'))
                  ->update(['email' => $user->email]);
            
            Attorney::where('email', $user->getOriginal('email'))
                    ->update(['email' => $user->email]);
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }


}
