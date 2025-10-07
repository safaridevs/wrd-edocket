<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Approved - OSE E-Docket</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: #1e40af; color: white; padding: 20px; text-align: center; }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .subtitle { font-size: 14px; opacity: 0.9; }
        .content { padding: 30px 20px; }
        .case-info { background: #f8fafc; border-left: 4px solid #1e40af; padding: 15px; margin: 20px 0; }
        .status-badge { background: #10b981; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .button { display: inline-block; background: #1e40af; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 15px 0; }
        .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; }
        .important { background: #fef3c7; border: 1px solid #f59e0b; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">OSE E-Docket System</div>
            <div class="subtitle">New Mexico Office of the State Engineer</div>
        </div>

        <!-- Content -->
        <div class="content">
            <h2 style="color: #1e40af; margin-bottom: 10px;">Water Rights Case Approved</h2>
            
            <p>Dear {{ $recipientName ?? 'Case Participant' }},</p>
            
            <p>We are pleased to inform you that the following water rights case has been <strong>approved</strong> and is now ready for hearing proceedings.</p>

            <!-- Case Information -->
            <div class="case-info">
                <h3 style="margin-top: 0; color: #1e40af;">Case Details</h3>
                <p><strong>Case Number:</strong> {{ $case->case_no }}</p>
                <p><strong>Case Type:</strong> {{ ucfirst($case->case_type) }}</p>
                <p><strong>Status:</strong> <span class="status-badge">APPROVED</span></p>
                <p><strong>Caption:</strong> {{ $case->caption }}</p>
                @if($case->oseFileNumbers->count() > 0)
                <p><strong>OSE File Numbers:</strong> 
                    @foreach($case->oseFileNumbers as $ose)
                        {{ $ose->file_no_from }}{{ $ose->file_no_to ? '-' . $ose->file_no_to : '' }}{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
                @endif
            </div>

            <!-- Important Notice -->
            <div class="important">
                <h4 style="margin-top: 0; color: #92400e;">📋 Important Notice</h4>
                <p style="margin-bottom: 0;">This case is now approved and ready for hearing. You may continue to file additional documents through the E-Docket system as needed for the proceedings.</p>
            </div>

            <!-- Next Steps -->
            <h3 style="color: #1e40af;">Next Steps</h3>
            <ul>
                <li>Review the approved case details in the E-Docket system</li>
                <li>Submit any additional required documents</li>
                <li>Await further hearing scheduling information</li>
                <li>Contact the Hearing Unit with any questions</li>
            </ul>

            <!-- Action Button -->
            <div style="text-align: center; margin: 25px 0;">
                <a href="{{ config('app.url') }}/cases/{{ $case->id }}" class="button">View Case Details</a>
            </div>

            <!-- Contact Information -->
            <h3 style="color: #1e40af;">Contact Information</h3>
            <p>If you have questions about this case or need assistance:</p>
            <ul>
                <li><strong>Email:</strong> hearing.unit@ose.nm.gov</li>
                <li><strong>Phone:</strong> (505) 827-6120</li>
                <li><strong>Office Hours:</strong> Monday - Friday, 8:00 AM - 5:00 PM</li>
            </ul>

            <p style="margin-top: 30px;">Thank you for using the OSE E-Docket system.</p>
            
            <p>Sincerely,<br>
            <strong>New Mexico Office of the State Engineer</strong><br>
            Hearing Unit</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>New Mexico Office of the State Engineer</strong></p>
            <p>1680 Hickman Loop, Las Cruces, NM 88005</p>
            <p>This is an automated message from the OSE E-Docket system. Please do not reply to this email.</p>
            <p>For technical support, contact: <a href="mailto:support@ose.nm.gov">support@ose.nm.gov</a></p>
        </div>
    </div>
</body>
</html>