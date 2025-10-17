<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Status Update - OSE E-Docket</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: #1e40af; color: white; padding: 20px; text-align: center; }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .subtitle { font-size: 14px; opacity: 0.9; }
        .content { padding: 30px 20px; }
        .case-info { background: #f8fafc; border-left: 4px solid #1e40af; padding: 15px; margin: 20px 0; }
        .status-active { background: #10b981; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-submitted { background: #f59e0b; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-rejected { background: #ef4444; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .button { display: inline-block; background: #1e40af; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 15px 0; }
        .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; }
        .alert { background: #dbeafe; border: 1px solid #3b82f6; padding: 15px; border-radius: 5px; margin: 15px 0; }
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
            <h2 style="color: #1e40af; margin-bottom: 10px;">Case Status Update</h2>
            
            <p>Dear {{ $recipientName ?? 'Case Participant' }},</p>
            
            <p>This is to notify you that there has been a status update for your water rights case.</p>

            <!-- Case Information -->
            <div class="case-info">
                <h3 style="margin-top: 0; color: #1e40af;">Case Details</h3>
                <p><strong>Case Number:</strong> {{ $case->case_no }}</p>
                <p><strong>Case Type:</strong> {{ ucfirst($case->case_type) }}</p>
                <p><strong>New Status:</strong> 
                    @if($case->status === 'active')
                        <span class="status-active">ACTIVE</span>
                    @elseif($case->status === 'submitted_to_hu')
                        <span class="status-submitted">SUBMITTED TO HU</span>
                    @elseif($case->status === 'rejected')
                        <span class="status-rejected">REJECTED</span>
                    @else
                        <span class="status-active">{{ strtoupper(str_replace('_', ' ', $case->status)) }}</span>
                    @endif
                </p>
                <p><strong>Caption:</strong> {{ $case->caption }}</p>
                <p><strong>Updated:</strong> {{ $case->updated_at->format('F j, Y \a\t g:i A') }}</p>
            </div>

            <!-- Status-specific message -->
            @if($case->status === 'active')
            <div class="alert">
                <h4 style="margin-top: 0; color: #1e40af;">🎯 Case Now Active</h4>
                <p style="margin-bottom: 0;">Your case has been accepted by the Hearing Unit and is now active. You may continue to file documents and participate in the hearing process.</p>
            </div>
            @elseif($case->status === 'submitted_to_hu')
            <div class="alert">
                <h4 style="margin-top: 0; color: #1e40af;">📋 Under Review</h4>
                <p style="margin-bottom: 0;">Your case has been submitted to the Hearing Unit for review. You will be notified once the review is complete.</p>
            </div>
            @elseif($case->status === 'rejected')
            <div style="background: #fef2f2; border: 1px solid #ef4444; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <h4 style="margin-top: 0; color: #dc2626;">❌ Case Requires Attention</h4>
                <p style="margin-bottom: 0;">Your case has been rejected and requires corrections. Please review the feedback and resubmit with the necessary changes.</p>
            </div>
            @endif

            <!-- Action Button -->
            <div style="text-align: center; margin: 25px 0;">
                <a href="{{ config('app.url') }}/cases/{{ $case->id }}" class="button">View Case Details</a>
            </div>

            <!-- What to do next -->
            <h3 style="color: #1e40af;">What to do next:</h3>
            <ul>
                @if($case->status === 'active')
                <li>Continue filing any additional required documents</li>
                <li>Monitor case progress through the E-Docket system</li>
                <li>Await hearing scheduling information</li>
                @elseif($case->status === 'submitted_to_hu')
                <li>Wait for Hearing Unit review completion</li>
                <li>Monitor your email for further updates</li>
                <li>Prepare any additional documentation that may be needed</li>
                @elseif($case->status === 'rejected')
                <li>Review the rejection reasons in the case details</li>
                <li>Make necessary corrections to your case</li>
                <li>Resubmit the case for review</li>
                @endif
                <li>Contact the Hearing Unit if you have questions</li>
            </ul>

            <!-- Contact Information -->
            <h3 style="color: #1e40af;">Need Help?</h3>
            <p>Contact the Hearing Unit:</p>
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