<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CaseModel;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function caseHistory(CaseModel $case)
    {
        $logs = AuditLog::where('case_id', $case->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('audit.case-history', compact('case', 'logs'));
    }

    public function systemLogs(Request $request)
    {
        $logs = AuditLog::with(['user', 'case'])
            ->when($request->action, fn($q) => $q->where('action', $request->action))
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
        return view('audit.system-logs', compact('logs'));
    }
}