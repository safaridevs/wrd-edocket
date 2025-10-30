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
            'bar_number' => 'nullable|string|max:50'
        ]);

        $attorney->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}