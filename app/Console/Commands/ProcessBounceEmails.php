<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Webklex\PHPIMAP\ClientManager;

class ProcessBounceEmails extends Command
{
    protected $signature = 'email:process-bounces';
    protected $description = 'Process bounce emails and update notification status';

    public function handle()
    {
        try {
            $cm = new ClientManager();
            $client = $cm->make([
                'host' => env('BOUNCE_MAIL_HOST'),
                'port' => env('BOUNCE_MAIL_PORT', 993),
                'encryption' => env('BOUNCE_MAIL_ENCRYPTION', 'ssl'),
                'validate_cert' => true,
                'username' => env('BOUNCE_MAIL_USERNAME'),
                'password' => env('BOUNCE_MAIL_PASSWORD'),
                'protocol' => 'imap'
            ]);

            $client->connect();
            $folder = $client->getFolder('INBOX');
            $messages = $folder->query()->unseen()->get();

            $processedCount = 0;
            foreach ($messages as $message) {
                $subject = $message->getSubject();
                $body = $message->getTextBody();

                // Check if it's a bounce message
                if ($this->isBounceMessage($subject, $body)) {
                    $bouncedEmail = $this->extractBouncedEmail($body);
                    $bounceReason = $this->extractBounceReason($body);

                    if ($bouncedEmail) {
                        // Update notifications for this email
                        $updated = Notification::where('payload_json->email', $bouncedEmail)
                            ->where('email_status', 'sent')
                            ->update([
                                'email_status' => 'bounced',
                                'bounce_reason' => $bounceReason,
                                'bounced_at' => now()
                            ]);

                        if ($updated > 0) {
                            $this->info("Marked {$updated} notification(s) as bounced for {$bouncedEmail}");
                            $processedCount++;
                        }
                    }

                    $message->setFlag('Seen');
                }
            }

            $this->info("Processed {$processedCount} bounce messages");
            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to process bounces: ' . $e->getMessage());
            return 1;
        }
    }

    private function isBounceMessage(string $subject, string $body): bool
    {
        $bounceKeywords = [
            'delivery status notification',
            'undelivered mail',
            'mail delivery failed',
            'returned mail',
            'delivery failure',
            'mailer-daemon',
            'postmaster'
        ];

        $text = strtolower($subject . ' ' . $body);
        foreach ($bounceKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function extractBouncedEmail(string $body): ?string
    {
        // Try to extract email from common bounce message patterns
        if (preg_match('/(?:to|for|recipient):\s*<?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>?/i', $body, $matches)) {
            return $matches[1];
        }

        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $body, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractBounceReason(string $body): string
    {
        // Extract common bounce reasons
        if (preg_match('/550.*?(user|mailbox|address).*?(not found|unknown|does not exist)/i', $body, $matches)) {
            return 'Mailbox does not exist';
        }

        if (preg_match('/552.*?mailbox.*?full/i', $body)) {
            return 'Mailbox full';
        }

        if (preg_match('/554.*?rejected/i', $body)) {
            return 'Message rejected by recipient server';
        }

        return 'Email delivery failed';
    }
}
