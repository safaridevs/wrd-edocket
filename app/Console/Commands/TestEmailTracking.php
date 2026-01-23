<?php

namespace App\Console\Commands;

use App\Models\CaseModel;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class TestEmailTracking extends Command
{
    protected $signature = 'email:test-tracking {email}';
    protected $description = 'Test email tracking with a specific email address';

    public function handle(NotificationService $notificationService)
    {
        $email = $this->argument('email');
        
        $this->info("Sending test email to: {$email}");
        
        // Create a Person object with the email
        $recipient = new \App\Models\Person();
        $recipient->email = $email;
        $recipient->first_name = 'Test';
        $recipient->last_name = 'User';
        
        // Send test notification
        $notification = $notificationService->notify(
            $recipient,
            'test_notification',
            'Email Tracking Test',
            'This is a test email to verify email tracking functionality.',
            null,
            false // Don't log to audit
        );
        
        $this->info("Email sent! Notification ID: {$notification->id}");
        $this->info("Email Status: {$notification->email_status}");
        
        if ($notification->bounce_reason) {
            $this->error("Bounce Reason: {$notification->bounce_reason}");
        }
        
        // Show the notification record
        $this->table(
            ['ID', 'Type', 'Email', 'Status', 'Bounce Reason', 'Sent At'],
            [[
                $notification->id,
                $notification->notification_type,
                $notification->payload_json['email'] ?? 'N/A',
                $notification->email_status,
                $notification->bounce_reason ?? 'None',
                $notification->sent_at
            ]]
        );
        
        $this->info("\nCheck the notifications table to verify the status was recorded.");
        
        return 0;
    }
}
