<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monthly Active Case List - {{ $selectedMonth->format('F Y') }}</title>
    <style>
        body {
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 24px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .button {
            background: #1d4ed8;
            border-radius: 4px;
            color: #ffffff;
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 4px;
        }

        .muted {
            color: #4b5563;
        }

        .summary {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, 1fr);
            margin: 18px 0;
        }

        .summary-card {
            border: 1px solid #d1d5db;
            padding: 10px;
        }

        .summary-label {
            color: #4b5563;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 22px;
            font-weight: bold;
            margin-top: 4px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        @media print {
            body {
                margin: 0.35in;
            }

            .toolbar {
                display: none;
            }

            @page {
                size: landscape;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="button" href="{{ route('reports.index', ['month' => $selectedMonth->format('Y-m')]) }}">Back to Report</a>
        <button class="button" type="button" onclick="window.print()">Print</button>
    </div>

    <h1>Monthly Active Case List</h1>
    <div class="muted">Cases active at any point from {{ $monthStart->format('F j, Y') }} through {{ $monthEnd->format('F j, Y') }}.</div>
    <div class="muted">Generated {{ now()->format('m/d/Y g:i A') }}</div>

    <div class="summary">
        <div class="summary-card">
            <div class="summary-label">Total Cases</div>
            <div class="summary-value">{{ $summary['total'] }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Currently Active</div>
            <div class="summary-value">{{ $summary['active'] }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">HU Display Status</div>
            <div class="summary-value">{{ $summary['with_hu_display_status'] }}</div>
        </div>
    </div>

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
</body>
</html>
