<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonController extends Controller
{
    public function edit(CaseModel $case, Person $person)
    {
        $user = Auth::user();
        
        // Allow if user can modify persons OR if editing own contact info
        $canEdit = $user->canModifyPersons() || 
                  ($user->canUpdateOwnContact() && $person->email === $user->email);
        
        if (!$canEdit) {
            abort(403);
        }
        
        return view('persons.edit', compact('case', 'person'));
    }

    public function update(Request $request, CaseModel $case, Person $person)
    {
        $user = Auth::user();
        
        // Allow if user can modify persons OR if updating own contact info
        $canUpdate = $user->canModifyPersons() || 
                    ($user->canUpdateOwnContact() && $person->email === $user->email);
        
        if (!$canUpdate) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            'first_name' => 'required_if:type,individual|string|max:255',
            'last_name' => 'required_if:type,individual|string|max:255',
            'organization' => 'required_if:type,company|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone_mobile' => 'nullable|string|max:20',
            'phone_office' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10'
        ]);

        $person->update($validated);

        return redirect()->route('cases.show', $case)->with('success', 'Person updated successfully.');
    }
}