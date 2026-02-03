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
        $groupBy = $request->get('group_by', 'case'); // default to case grouping
        
        $notifications = Notification::with('case')
            ->when($request->case_id, fn($q) => $q->where('case_id', $request->case_id))
            ->when($request->email, fn($q) => $q->whereJsonContains('payload_json->email', $request->email))
            ->orderBy('sent_at', 'desc')
            ->get();
            
        $emailLogs = AuditLog::where('action', 'send_notification')
            ->with(['user', 'case'])
            ->when($request->case_id, fn($q) => $q->where('case_id', $request->case_id))
            ->when($request->email, fn($q) => $q->whereJsonContains('meta_json->email', $request->email))
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Group data based on selected tab
        $grouped = match($groupBy) {
            'type' => $notifications->groupBy('notification_type'),
            'date' => $notifications->groupBy(fn($n) => $n->sent_at->format('Y-m-d')),
            'recipient' => $notifications->groupBy(fn($n) => $n->payload_json['email'] ?? 'Unknown'),
            default => $notifications->groupBy('case_id'), // case
        };
            
        return view('audit.notification-history', compact('notifications', 'emailLogs', 'grouped', 'groupBy'));
    }
}