<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Case Index List - {{ $case->case_no }}</title>
    <style>
        body { color: #111827; font-family: Arial, sans-serif; font-size: 12px; margin: 24px; }
        .toolbar { display: flex; justify-content: space-between; margin-bottom: 18px; }
        .button { background: #1d4ed8; border: 0; border-radius: 4px; color: #fff; cursor: pointer; display: inline-block; padding: 8px 12px; text-decoration: none; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #4b5563; margin-bottom: 2px; }
        table { border-collapse: collapse; margin-top: 18px; width: 100%; }
        th, td { border: 1px solid #6b7280; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #d9ead3; font-size: 11px; text-transform: uppercase; }
        @media print {
            body { margin: 0.45in; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="button" href="{{ route('reports.case-documents', ['case_id' => $case->id]) }}">Back to Report</a>
        <button class="button" type="button" onclick="window.print()">Print</button>
    </div>

    <h1>Case Index List</h1>
    <div class="muted"><strong>Hearing Number:</strong> {{ $case->case_no }}</div>
    <div class="muted">Generated {{ now()->format('m/d/Y g:i A') }}</div>

    @include('reports.partials.case-document-list-table', [
        'tableClass' => '',
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
</body>
</html>
