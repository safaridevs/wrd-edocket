<style>
    h1 { color: #111827; font-size: 18px; margin: 0 0 4px; }
    .meta { color: #4b5563; font-size: 9px; line-height: 1.5; }
    table.report-table { border-collapse: collapse; margin-top: 12px; width: 100%; }
    table.report-table th, table.report-table td { border: 1px solid #6b7280; font-size: 8px; padding: 5px; vertical-align: top; }
    table.report-table th { background-color: #d9ead3; color: #111827; font-weight: bold; text-transform: uppercase; }
</style>

<h1>Case Index List</h1>
<div class="meta"><strong>Hearing Number:</strong> {{ $case->case_no }}</div>
<div class="meta">Generated {{ now()->format('m/d/Y g:i A') }}</div>

@include('reports.partials.case-document-list-table', [
    'tableClass' => 'report-table',
    'headClass' => '',
    'bodyClass' => '',
    'numberHeaderClass' => '',
    'headerClass' => '',
    'partyHeaderClass' => '',
    'numberCellClass' => '',
    'cellClass' => '',
    'titleCellClass' => '',
    'emptyClass' => '',
])
