<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function notify($recipient, string $type, string $title, string $message, ?CaseModel $case = null): Notification
    {
        $userId = null;
        $email = null;
        
        if ($recipient instanceof User) {
            $userId = $recipient->id;
            $email = $recipient->email;
        } elseif ($recipient instanceof \App\Models\Person) {
            $email = $recipient->email;
        } elseif ($recipient instanceof \App\Models\Attorney) {
            $email = $recipient->email;
        }
        
        // Send actual email
        if ($email) {
            try {
                Mail::raw($message, function ($mail) use ($email, $title) {
                    $mail->to($email)
                         ->subject($title);
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send email notification', [
                    'email' => $email,
                    'title' => $title,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return Notification::create([
            'case_id' => $case?->id,
            'notification_type' => $type,
            'payload_json' => [
                'title' => $title,
                'message' => $message,
                'email' => $email,
                'user_id' => $userId
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