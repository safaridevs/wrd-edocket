<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartyContactController extends Controller
{
    public function edit()
    {
        $person = Person::where('email', Auth::user()->email)->first();
        if (!$person) {
            abort(404, 'Contact information not found.');
        }
        
        return view('party.edit-contact', compact('person'));
    }

    public function update(Request $request)
    {
        $person = Person::where('email', Auth::user()->email)->first();
        if (!$person) {
            abort(404, 'Contact information not found.');
        }

        $validated = $request->validate([
            'first_name' => 'required_if:type,individual|string|max:255',
            'last_name' => 'required_if:type,individual|string|max:255',
            'organization' => 'required_if:type,company|string|max:255',
            'phone_mobile' => 'nullable|string|max:20',
            'phone_office' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10'
        ]);

        $person->update($validated);

        return redirect()->back()->with('success', 'Contact information updated successfully.');
    }
}