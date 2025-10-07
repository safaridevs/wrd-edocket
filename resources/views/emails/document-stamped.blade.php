<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Stamped - OSE E-Docket</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: #1e40af; color: white; padding: 20px; text-align: center; }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .subtitle { font-size: 14px; opacity: 0.9; }
        .content { padding: 30px 20px; }
        .case-info { background: #f8fafc; border-left: 4px solid #1e40af; padding: 15px; margin: 20px 0; }
        .document-info { background: #ecfdf5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; }
        .stamp-badge { background: #3b82f6; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .button { display: inline-block; background: #1e40af; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 15px 0; }
        .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; }
        .stamp-notice { background: #dbeafe; border: 1px solid #3b82f6; padding: 15px; border-radius: 5px; margin: 15px 0; }
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
            <h2 style="color: #1e40af; margin-bottom: 10px;">Document Officially Stamped</h2>
            
            <p>Dear {{ $recipientName ?? 'Case Participant' }},</p>
            
            <p>This is to notify you that a document in your water rights case has been <strong>officially stamped</strong> by the Hearing Unit.</p>

            <!-- Case Information -->
            <div class="case-info">
                <h3 style="margin-top: 0; color: #1e40af;">Case Details</h3>
                <p><strong>Case Number:</strong> {{ $case->case_no }}</p>
                <p><strong>Case Type:</strong> {{ ucfirst($case->case_type) }}</p>
                <p><strong>Caption:</strong> {{ $case->caption }}</p>
            </div>

            <!-- Document Information -->
            <div class="document-info">
                <h3 style="margin-top: 0; color: #059669;">📋 Stamped Document</h3>
                <p><strong>Document:</strong> {{ $document->original_filename }}</p>
                <p><strong>Document Type:</strong> {{ ucfirst(str_replace('_', ' ', $document->doc_type)) }}</p>
                @if($document->pleading_type && $document->pleading_type !== 'none')
                <p><strong>Pleading Type:</strong> {{ ucfirst(str_replace('_', ' ', $document->pleading_type)) }}</p>
                @endif
                <p><strong>Stamped:</strong> <span class="stamp-badge">📋 E-STAMPED</span></p>
                <p><strong>Stamp Date:</strong> {{ $document->stamped_at->format('F j, Y \a\t g:i A') }}</p>
                <p><strong>File Size:</strong> {{ number_format($document->size_bytes / 1024, 1) }} KB</p>
            </div>

            <!-- Stamp Notice -->
            <div class="stamp-notice">
                <h4 style="margin-top: 0; color: #1e40af;">🏛️ Official Filing Notice</h4>
                <p>This document has been officially received and stamped by the New Mexico Office of the State Engineer Hearing Unit. The electronic stamp serves as proof of filing and includes the date and time of receipt.</p>
                <p style="margin-bottom: 0;"><strong>Legal Effect:</strong> This stamped document is now part of the official case record and has the same legal standing as a traditionally filed document.</p>
            </div>

            <!-- What this means -->
            <h3 style="color: #1e40af;">What this means:</h3>
            <ul>
                <li>Your document has been officially received and processed</li>
                <li>The document is now part of the official case record</li>
                <li>The electronic stamp provides legal proof of filing</li>
                <li>You can download the stamped version from the E-Docket system</li>
            </ul>

            <!-- Action Button -->
            <div style="text-align: center; margin: 25px 0;">
                <a href="{{ config('app.url') }}/cases/{{ $case->id }}" class="button">View Case & Documents</a>
            </div>

            <!-- Important Information -->
            <h3 style="color: #1e40af;">Important Information</h3>
            <ul>
                <li><strong>Keep Records:</strong> Download and save the stamped document for your records</li>
                <li><strong>Filing Deadlines:</strong> Ensure all required documents are filed by applicable deadlines</li>
                <li><strong>Case Progress:</strong> Monitor your case status through the E-Docket system</li>
                <li><strong>Questions:</strong> Contact the Hearing Unit if you need clarification</li>
            </ul>

            <!-- Contact Information -->
            <h3 style="color: #1e40af;">Contact Information</h3>
            <p>If you have questions about this stamped document or your case:</p>
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