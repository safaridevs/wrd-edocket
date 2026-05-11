<?php

namespace App\Http\Controllers;

use App\Models\CaseParty;
use App\Models\CaseModel;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttorneyController extends Controller
{
    public function show(CaseModel $case)
    {
        return view('cases.attorney-representation', compact('case'));
    }

    public function addClient(Request $request, CaseModel $case)
    {
        $validated = $request->validate([
            'client_person_id' => 'required|exists:persons,id',
            'effective_date' => 'required|date',
            'notes' => 'nullable|string|max:500'
        ]);

        $attorneyPerson = Person::where('email', auth()->user()->email)->first();
        if (!$attorneyPerson) {
            return redirect()->back()->with('error', 'Your account is not linked to a person record.');
        }

        $clientParty = $case->parties()
            ->where('person_id', $validated['client_person_id'])
            ->whereNotIn('role', ['counsel', 'paralegal', 'agent'])
            ->first();

        if (!$clientParty) {
            return redirect()->back()->with('error', 'Client is not a party on this case.');
        }

        CaseParty::firstOrCreate([
            'case_id' => $case->id,
            'person_id' => $attorneyPerson->id,
            'role' => 'counsel',
            'client_party_id' => $clientParty->id,
        ], [
            'service_enabled' => true,
        ]);

        return redirect()->back()->with('success', 'Client representation added successfully.');
    }

    public function terminateRepresentation($relationship)
    {
        $relationship = CaseParty::where('role', 'counsel')->findOrFail($relationship);
        $relationship->delete();

        return redirect()->back()->with('success', 'Client representation terminated.');
    }

    public function myClients()
    {
        $attorney = Person::where('email', auth()->user()->email)->first();
        if (!$attorney) {
            return redirect()->back()->with('error', 'Person record not found.');
        }

        $relationships = CaseParty::where('person_id', $attorney->id)
            ->where('role', 'counsel')
            ->with(['client.person', 'case'])
            ->get();
            
        return view('attorney.clients', compact('relationships'));
    }

    public function editProfile()
    {
        $attorney = Person::where('email', Auth::user()->email)->first();
        if (!$attorney) {
            abort(404, 'Person record not found.');
        }
        
        return view('attorney.edit-profile', compact('attorney'));
    }

    public function updateProfile(Request $request)
    {
        $attorney = Person::where('email', Auth::user()->email)->first();
        if (!$attorney) {
            abort(404, 'Person record not found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10',
        ]);

        $name = Person::splitDisplayName($validated['name']);
        $attorney->update([
            'first_name' => $name['first_name'],
            'last_name' => $name['last_name'],
            'phone_office' => $validated['phone'] ?? null,
            'address_line1' => $validated['address_line1'] ?? null,
            'address_line2' => $validated['address_line2'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip' => $validated['zip'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function index()
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $attorneys = Person::counselDirectory()->paginate(50);
        return view('admin.attorneys', compact('attorneys'));
    }

    public function edit(Person $attorney)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        return response()->json([
            'id' => $attorney->id,
            'name' => $attorney->full_name,
            'email' => $attorney->email,
            'phone' => $attorney->phone_office,
            'address_line1' => $attorney->address_line1,
            'address_line2' => $attorney->address_line2,
            'city' => $attorney->city,
            'state' => $attorney->state,
            'zip' => $attorney->zip,
        ]);
    }

    public function update(Request $request, Person $attorney)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:persons,email,' . $attorney->id,
            'phone' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10'
        ]);

        $name = Person::splitDisplayName($validated['name']);
        $attorney->update([
            'type' => 'individual',
            'first_name' => $name['first_name'],
            'last_name' => $name['last_name'],
            'email' => $validated['email'],
            'phone_office' => $validated['phone'] ?? null,
            'address_line1' => $validated['address_line1'] ?? null,
            'address_line2' => $validated['address_line2'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip' => $validated['zip'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Person $attorney)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        if ($attorney->caseParties()->exists()) {
            return back()->with('error', 'Counsel records that are attached to cases cannot be deleted.');
        }

        $attorney->delete();
        return back()->with('success', 'Attorney deleted successfully.');
    }
}
