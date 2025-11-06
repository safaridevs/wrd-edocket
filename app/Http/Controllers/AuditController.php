<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CaseModel;
use App\Models\Notification;
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

    public function notificationHistory(Request $request)
    {
        $notifications = Notification::with('case')
            ->when($request->case_id, fn($q) => $q->where('case_id', $request->case_id))
            ->when($request->email, fn($q) => $q->whereJsonContains('payload_json->email', $request->email))
            ->orderBy('sent_at', 'desc')
            ->paginate(50);
            
        $emailLogs = AuditLog::where('action', 'send_notification')
            ->with(['user', 'case'])
            ->when($request->case_id, fn($q) => $q->where('case_id', $request->case_id))
            ->when($request->email, fn($q) => $q->whereJsonContains('meta_json->email', $request->email))
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
        return view('audit.notification-history', compact('notifications', 'emailLogs'));
    }
}