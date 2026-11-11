<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Person;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'person' => Person::where('email', $request->user()->email)->first(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateLegalServiceProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $person = Person::where('email', $user->email)->firstOrFail();
        $request->merge(['type' => $person->type]);

        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            'first_name' => 'required_if:type,individual|nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required_if:type,individual|nullable|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'organization' => 'required_if:type,company|nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'phone_mobile' => 'nullable|string|max:20',
            'phone_office' => 'nullable|string|max:20',
            'initials' => 'nullable|string|max:10',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|size:2',
            'zip' => 'nullable|string|max:10',
        ]);

        $fields = [
            'first_name', 'middle_name', 'last_name', 'suffix', 'organization', 'title',
            'phone_mobile', 'phone_office', 'address_line1', 'address_line2', 'city',
            'state', 'zip',
        ];

        $updates = collect($fields)
            ->mapWithKeys(fn (string $field) => [$field => $validated[$field] ?? null])
            ->all();

        if ($person->type === 'individual') {
            $updates['organization'] = null;
        } else {
            $updates['first_name'] = null;
            $updates['middle_name'] = null;
            $updates['last_name'] = null;
            $updates['suffix'] = null;
        }

        $initials = array_key_exists('initials', $validated)
            ? $validated['initials']
            : $user->initials;

        DB::transaction(function () use ($person, $updates, $user, $initials) {
            $before = $person->only(array_keys($updates));
            $person->fill($updates);
            $after = $person->only(array_keys($updates));

            $changes = collect($after)
                ->filter(fn ($value, string $field) => (string) ($before[$field] ?? '') !== (string) ($value ?? ''))
                ->map(fn ($value, string $field) => [
                    'before' => $before[$field] ?? null,
                    'after' => $value,
                ])
                ->all();

            $userUpdates = [
                'name' => $person->full_name,
                'phone' => $updates['phone_office'] ?: $updates['phone_mobile'],
                'initials' => $initials,
            ];
            $changedUserFields = collect($userUpdates)
                ->filter(fn ($value, string $field) => (string) ($user->{$field} ?? '') !== (string) ($value ?? ''))
                ->all();

            if ($changes === [] && $changedUserFields === []) {
                return;
            }

            if ($changes !== []) {
                $person->save();
            }

            $userNameChange = null;
            if (array_key_exists('name', $changedUserFields)) {
                $userNameChange = [
                    'before' => $user->name,
                    'after' => $userUpdates['name'],
                ];
            }

            if ($changedUserFields !== []) {
                $user->update($changedUserFields);
            }

            AuditService::logLegalServiceProfileUpdated($user, $person->id, $changes, $userNameChange);
        });

        return Redirect::route('profile.edit')->with('status', 'legal-service-profile-updated');
    }

}
