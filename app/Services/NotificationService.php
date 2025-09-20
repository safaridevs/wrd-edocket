<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function notify(User $user, string $type, string $title, string $message, ?CaseModel $case = null): Notification
    {
        return Notification::create([
            'case_id' => $case?->id,
            'notification_type' => $type,
            'payload_json' => [
                'title' => $title,
                'message' => $message,
                'user_id' => $user->id
            ],
            'sent_at' => now()
        ]);
    }

    public function notifyMultiple(array $users, string $type, string $title, string $message, ?CaseModel $case = null): void
    {
        foreach ($users as $user) {
            $this->notify($user, $type, $title, $message, $case);
        }
    }

    public function getUnreadCount(User $user): int
    {
        return 0; // Notifications are case-based, not user-based
    }

    public function markAllAsRead(User $user): void
    {
        // Notifications are case-based, not user-based
    }
}