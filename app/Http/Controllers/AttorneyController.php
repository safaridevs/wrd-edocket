<?php

namespace App\Http\Controllers;

use App\Models\Attorney;
use App\Models\AttorneyClientRelationship;
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
        $request->validate([
            'client_person_id' => 'required|exists:persons,id',
            'effective_date' => 'required|date',
            'notes' => 'nullable|string|max:500'
        ]);

        $attorney = Attorney::where('email', auth()->user()->email)->first();
        if (!$attorney) {
            return redirect()->back()->with('error', 'You must be registered as an attorney to represent clients.');
        }

        AttorneyClientRelationship::create([
            'attorney_id' => $attorney->id,
            'client_person_id' => $request->client_person_id,
            'case_id' => $case->id,
            'status' => 'active',
            'effective_date' => $request->effective_date,
            'notes' => $request->notes
        ]);

        return redirect()->back()->with('success', 'Client representation added successfully.');
    }

    public function terminateRepresentation($relationship)
    {
        $relationship = AttorneyClientRelationship::findOrFail($relationship);
        
        $relationship->update([
            'status' => 'terminated',
            'termination_date' => now()
        ]);

        return redirect()->back()->with('success', 'Client representation terminated.');
    }

    public function myClients()
    {
        $attorney = Attorney::where('email', auth()->user()->email)->first();
        if (!$attorney) {
            return redirect()->back()->with('error', 'Attorney record not found.');
        }

        $relationships = AttorneyClientRelationship::where('attorney_id', $attorney->id)
            ->where('status', 'active')
            ->with(['client', 'case'])
            ->get();
            
        return view('attorney.clients', compact('relationships'));
    }

    public function editProfile()
    {
        $attorney = Attorney::where('email', Auth::user()->email)->first();
        if (!$attorney) {
            abort(404, 'Attorney record not found.');
        }
        
        return view('attorney.edit-profile', compact('attorney'));
    }

    public function updateProfile(Request $request)
    {
        $attorney = Attorney::where('email', Auth::user()->email)->first();
        if (!$attorney) {
            abort(404, 'Attorney record not found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bar_number' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10',
        ]);

        $attorney->update($validated);

        $person = Person::where('email', $attorney->email)->first();
        if ($person) {
            $nameParts = preg_split('/\s+/', trim((string) $validated['name'])) ?: [];
            $firstName = $nameParts[0] ?? $person->first_name;
            $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : ($person->last_name ?? '');

            $person->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone_office' => $validated['phone'] ?? null,
                'address_line1' => $validated['address_line1'] ?? null,
                'address_line2' => $validated['address_line2'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'zip' => $validated['zip'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function index()
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $attorneys = Attorney::orderBy('name')->paginate(50);
        return view('admin.attorneys', compact('attorneys'));
    }

    public function edit(Attorney $attorney)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        return response()->json($attorney);
    }

    public function update(Request $request, Attorney $attorney)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:attorneys,email,' . $attorney->id,
            'phone' => 'nullable|string|max:20',
            'bar_number' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:500',
            'address_line2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:10'
        ]);

        $attorney->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Attorney $attorney)
    {
        if (!auth()->user()->isHearingUnit()) {
            abort(403);
        }

        $attorney->delete();
        return back()->with('success', 'Attorney deleted successfully.');
    }
}
