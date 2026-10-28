<table class="{{ $tableClass ?? 'min-w-full divide-y divide-gray-200' }}">
    <thead class="{{ $headClass ?? 'bg-gray-50' }}">
        <tr>
            <th class="{{ $numberHeaderClass ?? 'w-20 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600' }}">No.</th>
            <th class="{{ $headerClass ?? 'w-32 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600' }}">Date</th>
            <th class="{{ $headerClass ?? 'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600' }}">Title</th>
            <th class="{{ $partyHeaderClass ?? 'w-40 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600' }}">Party</th>
        </tr>
    </thead>
    <tbody class="{{ $bodyClass ?? 'divide-y divide-gray-200 bg-white' }}">
        @forelse($rows as $row)
            <tr>
                <td class="{{ $numberCellClass ?? 'whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-900' }}">
                    <span class="{{ $row['is_child'] ? 'inline-block text-gray-600' : '' }}" @if($row['is_child']) style="padding-left: 16px;" @endif>{{ $row['order'] }}</span>
                </td>
                <td class="{{ $cellClass ?? 'whitespace-nowrap px-4 py-3 text-sm text-gray-700' }}">
                    {{ $row['date']?->format('m/d/y') ?? 'Not recorded' }}
                </td>
                <td class="{{ $titleCellClass ?? 'px-4 py-3 text-sm text-gray-900' }}">
                    <span class="{{ $row['is_child'] ? 'inline-block' : 'font-medium' }}" @if($row['is_child']) style="padding-left: 16px;" @endif>{{ $row['title'] }}</span>
                </td>
                <td class="{{ $cellClass ?? 'px-4 py-3 text-sm text-gray-700' }}">{{ $row['filer'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="{{ $emptyClass ?? 'px-6 py-10 text-center text-sm text-gray-500' }}">
                    No filed documents were found for this case.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
