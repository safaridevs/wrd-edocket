# Email Bounce Processing Setup

## 1. Install IMAP Package
```bash
composer require webklex/php-imap
```

## 2. Configure Bounce Email Account
Update `.env` with your bounce email credentials:
```
BOUNCE_MAIL_HOST=webmail.state.nm.us
BOUNCE_MAIL_PORT=993
BOUNCE_MAIL_ENCRYPTION=ssl
BOUNCE_MAIL_USERNAME=bounces@ose.nm.gov
BOUNCE_MAIL_PASSWORD=your-password-here
```

## 3. Run Migration
```bash
php artisan migrate
```

## 4. Schedule the Command
Add to `app/Console/Kernel.php` in the `schedule()` method:
```php
$schedule->command('email:process-bounces')->everyFiveMinutes();
```

## 5. Manual Testing
```bash
php artisan email:process-bounces
```

## 6. Configure SMTP Return-Path
In your SMTP configuration, set the Return-Path header to your bounce email address so bounce messages are sent there.

## How It Works
1. System sends emails with Return-Path set to bounces@ose.nm.gov
2. If email bounces, mail server sends bounce notification to that address
3. Command checks bounce mailbox every 5 minutes
4. Parses bounce messages to extract failed email addresses
5. Updates notification status to 'bounced' with reason
6. Marks bounce email as read

## Notification Statuses
- `pending` - Not yet sent
- `sent` - Successfully sent to SMTP server
- `delivered` - Confirmed delivered (requires webhook integration)
- `bounced` - Email bounced back
- `failed` - Failed to send to SMTP server
