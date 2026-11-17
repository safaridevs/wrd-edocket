<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\EntryOfAppearance;
use App\Models\User;
use App\Services\EntryOfAppearanceService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EntryOfAppearanceController extends Controller
{
    public function create(Request $request): View
    {
        return view('entry-of-appearances.create', [
            'caseNumber' => trim((string) $request->query('case_number')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'case_number' => ['required', 'string', 'max:255'],
            'party_name' => ['required', 'string', 'max:255'],
            'attorney_prefix' => ['nullable', 'string', 'max:20'],
            'attorney_first_name' => ['required', 'string', 'max:255'],
            'attorney_middle_name' => ['nullable', 'string', 'max:255'],
            'attorney_last_name' => ['required', 'string', 'max:255'],
            'attorney_suffix' => ['nullable', 'string', 'max:20'],
            'law_firm' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:50'],
            'zip' => ['required', 'string', 'max:20'],
            'effective_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'certification' => ['accepted'],
        ]);

        [$case, $party] = $this->matchCaseAndParty($validated['case_number'], $validated['party_name']);
        $email = strtolower(trim($validated['email']));

        $duplicate = EntryOfAppearance::query()
            ->where('case_id', $case->id)
            ->where('represented_case_party_id', $party->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where(function ($query) {
                $query->where('status', EntryOfAppearance::STATUS_PENDING)
                    ->orWhere(function ($unverified) {
                        $unverified->where('status', EntryOfAppearance::STATUS_UNVERIFIED)
                            ->where('verification_expires_at', '>', now());
                    });
            })
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'email' => 'An Entry of Appearance for this attorney, case, and party is already awaiting review.',
            ]);
        }

        $alreadyCounsel = $case->parties()
            ->where('role', 'counsel')
            ->where('client_party_id', $party->id)
            ->whereHas('person', fn ($query) => $query->whereRaw('LOWER(email) = ?', [$email]))
            ->exists();

        if ($alreadyCounsel) {
            throw ValidationException::withMessages(['email' => 'This attorney already represents the named party in this case.']);
        }

        if (CaseParty::wrdRepresentativeAssignmentForEmail($case, $email)) {
            throw ValidationException::withMessages(['email' => 'This attorney cannot appear for a private party in this case.']);
        }

        $appearance = EntryOfAppearance::create([
            'case_id' => $case->id,
            'represented_case_party_id' => $party->id,
            'status' => EntryOfAppearance::STATUS_UNVERIFIED,
            'submitted_case_number' => trim($validated['case_number']),
            'submitted_party_name' => trim($validated['party_name']),
            'attorney_prefix' => $validated['attorney_prefix'] ?? null,
            'attorney_first_name' => trim($validated['attorney_first_name']),
            'attorney_middle_name' => $validated['attorney_middle_name'] ?? null,
            'attorney_last_name' => trim($validated['attorney_last_name']),
            'attorney_suffix' => $validated['attorney_suffix'] ?? null,
            'law_firm' => trim($validated['law_firm']),
            'email' => $email,
            'phone' => trim($validated['phone']),
            'address_line1' => trim($validated['address_line1']),
            'address_line2' => $validated['address_line2'] ?? null,
            'city' => trim($validated['city']),
            'state' => trim($validated['state']),
            'zip' => trim($validated['zip']),
            'effective_date' => $validated['effective_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'verification_expires_at' => now()->addDay(),
            'submission_ip' => $request->ip(),
            'submission_user_agent' => substr((string) $request->userAgent(), 0, 2000),
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'entry-of-appearances.verify',
            $appearance->verification_expires_at,
            ['appearance' => $appearance->id, 'hash' => hash('sha256', $email)]
        );

        Mail::raw(
            "Verify your Entry of Appearance for case {$case->case_no} by visiting this link within 24 hours:\n\n{$verificationUrl}",
            fn ($message) => $message->to($email)->subject('Verify Your Entry of Appearance')
        );

        return redirect()->route('entry-of-appearances.submitted');
    }

    public function submitted(): View
    {
        return view('entry-of-appearances.submitted');
    }

    public function verify(Request $request, EntryOfAppearance $appearance, string $hash): View
    {
        abort_unless(hash_equals(hash('sha256', strtolower(trim($appearance->email))), $hash), 403);

        if ($appearance->status === EntryOfAppearance::STATUS_UNVERIFIED) {
            if (!$appearance->verification_expires_at || $appearance->verification_expires_at->isPast()) {
                $appearance->update(['status' => EntryOfAppearance::STATUS_EXPIRED]);

                return view('entry-of-appearances.verified', ['appearance' => $appearance, 'expired' => true]);
            }

            $appearance->update([
                'status' => EntryOfAppearance::STATUS_PENDING,
                'email_verified_at' => now(),
            ]);

            User::whereAnyCurrentRole(['hu_admin'])->get()->each(function (User $user) use ($appearance) {
                app(NotificationService::class)->notify(
                    $user,
                    'entry_of_appearance_submitted',
                    'Entry of Appearance Awaiting Review',
                    "A verified Entry of Appearance for case {$appearance->case->case_no} is awaiting review.",
                    $appearance->case,
                    false
                );
            });
        }

        return view('entry-of-appearances.verified', ['appearance' => $appearance->fresh('case'), 'expired' => false]);
    }

    public function index(Request $request): View
    {
        $this->authorizeHuAdmin($request);

        $appearances = EntryOfAppearance::query()
            ->with(['case', 'representedParty.person', 'reviewer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.entry-of-appearances.index', compact('appearances'));
    }

    public function show(Request $request, EntryOfAppearance $appearance): View
    {
        $this->authorizeHuAdmin($request);

        return view('admin.entry-of-appearances.show', [
            'appearance' => $appearance->load(['case', 'representedParty.person', 'reviewer', 'person']),
        ]);
    }

    public function approve(Request $request, EntryOfAppearance $appearance, EntryOfAppearanceService $service): RedirectResponse
    {
        $this->authorizeHuAdmin($request);
        $service->approve($appearance, $request->user());

        return redirect()->route('admin.entry-of-appearances.show', $appearance)
            ->with('success', 'Entry of Appearance approved and representation created.');
    }

    public function reject(Request $request, EntryOfAppearance $appearance, EntryOfAppearanceService $service): RedirectResponse
    {
        $this->authorizeHuAdmin($request);
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        $service->reject($appearance, $request->user(), $validated['rejection_reason']);

        return redirect()->route('admin.entry-of-appearances.show', $appearance)
            ->with('success', 'Entry of Appearance rejected.');
    }

    private function matchCaseAndParty(string $caseNumber, string $partyName): array
    {
        $case = CaseModel::query()
            ->whereRaw('LOWER(case_no) = ?', [strtolower(trim($caseNumber))])
            ->where('status', 'active')
            ->first();

        if (!$case || $case->isConsolidatedSource()) {
            throw $this->matchFailure();
        }

        $normalizedParty = $this->normalizeName($partyName);
        $matches = $case->parties()
            ->whereNotIn('role', CaseParty::REPRESENTATIVE_ROLES)
            ->with('person')
            ->get()
            ->filter(fn (CaseParty $party) => $this->normalizeName($party->person?->full_name) === $normalizedParty);

        if ($matches->count() !== 1) {
            throw $this->matchFailure();
        }

        return [$case, $matches->first()];
    }

    private function normalizeName(?string $value): string
    {
        return preg_replace('/[^a-z0-9]+/i', '', strtolower(trim((string) $value))) ?? '';
    }

    private function matchFailure(): ValidationException
    {
        return ValidationException::withMessages([
            'party_name' => 'The case number and party name could not be matched.',
        ]);
    }

    private function authorizeHuAdmin(Request $request): void
    {
        abort_unless($request->user()?->isHUAdmin(), 403);
    }
}
