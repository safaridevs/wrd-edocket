<style>
    h1 {
        color: #111827;
        font-size: 18px;
        margin: 0 0 4px;
    }

    .muted {
        color: #4b5563;
        font-size: 9px;
    }

    .summary-table {
        border-collapse: collapse;
        margin: 10px 0 12px;
        width: 100%;
    }

    .summary-table td {
        border: 1px solid #d1d5db;
        padding: 6px;
    }

    .summary-label {
        color: #4b5563;
        font-size: 8px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .summary-value {
        color: #111827;
        font-size: 16px;
        font-weight: bold;
    }

    table.report-table {
        border-collapse: collapse;
        width: 100%;
    }

    table.report-table th,
    table.report-table td {
        border: 1px solid #d1d5db;
        font-size: 7px;
        padding: 4px;
        vertical-align: top;
    }

    table.report-table th {
        background-color: #f3f4f6;
        color: #374151;
        font-weight: bold;
        text-transform: uppercase;
    }
</style>

<h1>Monthly Active Case List</h1>
<div class="muted">Cases active at any point from {{ $monthStart->format('F j, Y') }} through {{ $monthEnd->format('F j, Y') }}.</div>
<div class="muted">Generated {{ now()->format('m/d/Y g:i A') }}</div>

<table class="summary-table">
    <tr>
        <td>
            <div class="summary-label">Total Cases</div>
            <div class="summary-value">{{ $summary['total'] }}</div>
        </td>
        <td>
            <div class="summary-label">Currently Active</div>
            <div class="summary-value">{{ $summary['active'] }}</div>
        </td>
        <td>
            <div class="summary-label">HU Display Status</div>
            <div class="summary-value">{{ $summary['with_hu_display_status'] }}</div>
        </td>
    </tr>
</table>

@include('reports.partials.active-case-list-table', [
    'linkCases' => false,
    'tableClass' => 'report-table',
    'headClass' => '',
    'bodyClass' => '',
    'thClass' => '',
    'tdCaseClass' => '',
    'tdCaptionClass' => '',
    'tdClass' => '',
    'emptyClass' => '',
])
