<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use TCPDF;

class ReportController extends Controller
{
    private const REPORT_ROLES = [
        'admin',
        'hu_admin',
        'hu_clerk',
        'alu_mgr',
        'alu_clerk',
        'alu_paralegal',
        'alu_atty',
    ];

    public function index(Request $request): View
    {
        $this->authorizeReports();

        $data = $this->monthlyActiveCaseData($request);

        return view('reports.index', $data);
    }

    public function activeCasesPrint(Request $request): View
    {
        $this->authorizeReports();

        return view('reports.active-cases-print', $this->monthlyActiveCaseData($request));
    }

    public function activeCasesPdf(Request $request): Response
    {
        $this->authorizeReports();

        $data = $this->monthlyActiveCaseData($request);
        $html = view('reports.active-cases-pdf', $data)->render();

        $pdf = new TCPDF('L', 'mm', 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator(config('app.name', 'E-Docket'));
        $pdf->SetAuthor(config('app.name', 'E-Docket'));
        $pdf->SetTitle('Monthly Active Case List - ' . $data['selectedMonth']->format('F Y'));
        $pdf->SetMargins(10, 12, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 8);
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = 'monthly-active-case-list-' . $data['selectedMonth']->format('Y-m') . '.pdf';

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function authorizeReports(): void
    {
        if (!auth()->user()?->hasAnyRole(self::REPORT_ROLES)) {
            abort(403);
        }
    }

    private function monthlyActiveCaseData(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $selectedMonth = Carbon::createFromFormat('Y-m', $validated['month'] ?? now()->format('Y-m'))->startOfMonth();
        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();

        $activeCases = CaseModel::with([
                'aluAttorneys',
                'aluClerks',
                'assignedAttorney',
                'assignedAluClerk',
                'huDisplayStatusUpdatedBy',
            ])
            ->whereNotIn('status', ['draft', 'submitted_to_hu', 'rejected'])
            ->where(function ($query) use ($monthEnd) {
                $query->whereNull('accepted_at')
                    ->orWhere('accepted_at', '<=', $monthEnd);
            })
            ->where(function ($query) use ($monthStart) {
                $query->whereNull('closed_at')
                    ->orWhere('closed_at', '>=', $monthStart);
            })
            ->where(function ($query) use ($monthStart) {
                $query->whereNull('archived_at')
                    ->orWhere('archived_at', '>=', $monthStart);
            })
            ->orderBy('case_no')
            ->get();

        return [
            'activeCases' => $activeCases,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'selectedMonth' => $selectedMonth,
            'summary' => [
                'total' => $activeCases->count(),
                'active' => $activeCases->where('status', 'active')->count(),
                'with_hu_display_status' => $activeCases->whereNotNull('hu_display_status')->count(),
            ],
        ];
    }
}
