<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'email:test {email}';
    protected $description = 'Test email configuration by sending a test email';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('Testing email configuration...');
        $this->info("Sending test email to: {$email}");
        
        try {
            Mail::raw('This is a test email from OSE E-Docket system. If you receive this, email configuration is working correctly!', function ($message) use ($email) {
                $message->to($email)
                       ->subject('OSE E-Docket - Email Test')
                       ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            $this->info('✅ Email sent successfully!');
            $this->info('Check your Mailtrap inbox: https://mailtrap.io/inboxes');
            
        } catch (\Exception $e) {
            $this->error('❌ Email failed to send: ' . $e->getMessage());
        }
        
        return 0;
    }
}