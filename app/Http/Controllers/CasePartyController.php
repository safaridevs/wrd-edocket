<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CasePartyController extends Controller
{
    public function manage(CaseModel $case)
    {
        if (!Auth::user()->canModifyPersons()) {
            abort(403);
        }
        
        $case->load('parties.person');
        $availablePersons = Person::all();
        
        return view('cases.parties.manage', compact('case', 'availablePersons'));
    }

    public function store(Request $request, CaseModel $case)
    {
        if (!Auth::user()->canModifyPersons()) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => 'required|in:applicant,protestant,intervenor,counsel',
            'type' => 'required|in:individual,company',
            'prefix' => 'nullable|string|max:10',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'organization' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone_mobile' => 'nullable|string|max:20',
            'phone_office' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10'
        ]);

        // Create or find person
        $person = Person::where('email', $validated['email'])->first();
        
        if (!$person) {
            $person = Person::create([
                'type' => $validated['type'],
                'prefix' => $validated['prefix'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'],
                'organization' => $validated['organization'],
                'title' => $validated['title'],
                'email' => $validated['email'],
                'phone_mobile' => $validated['phone_mobile'],
                'phone_office' => $validated['phone_office'],
                'address_line1' => $validated['address_line1'],
                'address_line2' => $validated['address_line2'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'zip' => $validated['zip']
            ]);
        }

        // Check if party already exists
        $existingParty = CaseParty::where('case_id', $case->id)
            ->where('person_id', $person->id)
            ->where('role', $validated['role'])
            ->first();
            
        if ($existingParty) {
            return back()->with('error', 'This person is already assigned to this case with the same role.');
        }

        CaseParty::create([
            'case_id' => $case->id,
            'person_id' => $person->id,
            'role' => $validated['role'],
            'service_enabled' => true
        ]);

        return back()->with('success', 'Party added successfully.');
    }

    public function destroy(CaseModel $case, CaseParty $party)
    {
        if (!Auth::user()->canModifyPersons()) {
            abort(403);
        }

        $party->delete();

        return back()->with('success', 'Party removed successfully.');
    }
}