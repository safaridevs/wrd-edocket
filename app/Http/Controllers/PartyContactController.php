<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'type' => 'required|in:individual,company',
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

        DB::transaction(function () use ($person, $validated) {
            $before = $person->only(array_keys($validated));
            $person->fill($validated);
            $after = $person->only(array_keys($validated));

            $changes = collect($after)
                ->filter(fn ($value, string $field) => (string) ($before[$field] ?? '') !== (string) ($value ?? ''))
                ->map(fn ($value, string $field) => [
                    'before' => $before[$field] ?? null,
                    'after' => $value,
                ])
                ->all();

            if ($changes === []) {
                return;
            }

            $person->save();

            $user = Auth::user();
            $userNameChange = null;
            if ($person->email === $user->email) {
                $userNameChange = $user->name !== $person->full_name
                    ? ['before' => $user->name, 'after' => $person->full_name]
                    : null;

                if ($userNameChange) {
                    $user->update(['name' => $person->full_name]);
                }
            }

            AuditService::logLegalServiceProfileUpdated($user, $person->id, $changes, $userNameChange);
        });

        return redirect()->back()->with('success', 'Contact information updated successfully.');
    }
}
