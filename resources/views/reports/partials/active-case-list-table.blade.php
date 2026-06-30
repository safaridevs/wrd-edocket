<table class="{{ $tableClass ?? 'min-w-full divide-y divide-gray-200' }}">
    <thead class="{{ $headClass ?? 'bg-white' }}">
        <tr>
            <th class="{{ $thClass ?? 'px-6 py-3 text-left text-xs font-medium uppercase text-gray-500' }}">Case No.</th>
            <th class="{{ $thClass ?? 'px-6 py-3 text-left text-xs font-medium uppercase text-gray-500' }}">Caption</th>
            <th class="{{ $thClass ?? 'px-6 py-3 text-left text-xs font-medium uppercase text-gray-500' }}">Case Type</th>
            <th class="{{ $thClass ?? 'px-6 py-3 text-left text-xs font-medium uppercase text-gray-500' }}">Status</th>
            <th class="{{ $thClass ?? 'px-6 py-3 text-left text-xs font-medium uppercase text-gray-500' }}">Accepted</th>
            <th class="{{ $thClass ?? 'px-6 py-3 text-left text-xs font-medium uppercase text-gray-500' }}">Assignments</th>
        </tr>
    </thead>
    <tbody class="{{ $bodyClass ?? 'divide-y divide-gray-200 bg-white' }}">
        @forelse($activeCases as $case)
            <tr>
                <td class="{{ $tdCaseClass ?? 'whitespace-nowrap px-6 py-4 text-sm font-medium text-blue-700' }}">
                    @if($linkCases ?? true)
                        <a href="{{ route('cases.show', $case) }}" class="hover:underline">{{ $case->case_no }}</a>
                    @else
                        {{ $case->case_no }}
                    @endif
                </td>
                <td class="{{ $tdCaptionClass ?? 'min-w-[280px] px-6 py-4 text-sm text-gray-900' }}">{{ $case->caption }}</td>
                <td class="{{ $tdClass ?? 'whitespace-nowrap px-6 py-4 text-sm text-gray-600' }}">{{ ucfirst(str_replace('_', ' ', $case->case_type)) }}</td>
                <td class="{{ $tdClass ?? 'whitespace-nowrap px-6 py-4 text-sm text-gray-600' }}">
                    <div>{{ ucfirst(str_replace('_', ' ', $case->status)) }}</div>
                    @if($case->hu_display_status)
                        <div class="text-xs text-gray-500">
                            HU: {{ \App\Models\CaseModel::HU_DISPLAY_STATUSES[$case->hu_display_status] ?? ucfirst(str_replace('_', ' ', $case->hu_display_status)) }}
                        </div>
                    @endif
                </td>
                <td class="{{ $tdClass ?? 'whitespace-nowrap px-6 py-4 text-sm text-gray-600' }}">{{ $case->accepted_at?->format('m/d/Y') ?? 'Not recorded' }}</td>
                <td class="{{ $tdClass ?? 'whitespace-nowrap px-6 py-4 text-sm text-gray-600' }}">
                    <div><strong>Attorney:</strong> {{ $case->aluAttorneys->pluck('name')->filter()->implode(', ') ?: ($case->assignedAttorney?->name ?? 'Unassigned') }}</div>
                    <div><strong>ALU Clerk:</strong> {{ $case->aluClerks->pluck('name')->filter()->implode(', ') ?: ($case->assignedAluClerk?->name ?? 'Unassigned') }}</div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="{{ $emptyClass ?? 'px-6 py-10 text-center text-sm text-gray-500' }}">
                    No active cases found for this report month.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
