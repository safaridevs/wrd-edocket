<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request)
    {
        if ($request->has('notification_id')) {
            $notification = Auth::user()->notifications()->findOrFail($request->notification_id);
            $notification->markAsRead();
        } else {
            $this->notificationService->markAllAsRead(Auth::user());
        }

        return back()->with('success', 'Notifications marked as read.');
    }

    public function getUnreadCount()
    {
        return response()->json([
            'count' => $this->notificationService->getUnreadCount(Auth::user())
        ]);
    }
}