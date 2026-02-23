<!DOCTYPE html>
<html>
<head>
    <title>Case Submitted for Review - OSE E-Docket</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1e40af; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .case-info { background: #e3f2fd; border: 1px solid #1976d2; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .footer { background: #6b7280; color: white; padding: 15px; text-align: center; font-size: 12px; }
        .review-notice { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Mexico Office of the State Engineer</h1>
            <h2>Case Submitted for Review</h2>
        </div>
        
        <div class="content">
            <p>Dear {{ $case->creator->name }},</p>
            
            <p>Your case has been successfully submitted to the Hearing Unit for review.</p>
            
            <div class="case-info">
                <h3>📋 Case Information</h3>
                <p><strong>Case Number:</strong> {{ $case->case_no }}</p>
                <p><strong>Caption:</strong> {{ $case->caption }}</p>
                <p><strong>Case Type:</strong> {{ ucfirst(str_replace('_', ' ', $case->case_type)) }}</p>
                <p><strong>Submitted:</strong> {{ $case->submitted_at->format('F j, Y \\a\\t g:i A') }}</p>
            </div>
            
            <div class="review-notice">
                <h3>🔍 Review Process</h3>
                <p><strong>The Hearing Unit is in receipt of the Request to Docket OR the Request for Pre-Hearing Scheduling Conference.</strong></p>
                
                <p>The Request and the associated documents will be reviewed and either accepted or rejected. If a case is rejected, we hope to provide a reason for rejection (i.e. improper naming convention, did not include all required documents such as the Application, letters of protests, letter of denial and letter of aggrieval, compliance order, etc.)</p>
            </div>
            
            <h3>📄 Submitted Documents</h3>
            <ul>
                @foreach($case->documents as $document)
                <li>{{ $document->original_filename }} ({{ $document->doc_type_label }})</li>
                @endforeach
            </ul>
            
            <h3>👥 Case Parties</h3>
            <ul>
                @foreach($case->parties as $party)
                <li>{{ $party->person->full_name }} ({{ ucfirst($party->role) }})</li>
                @endforeach
            </ul>
            
            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>The Hearing Unit will review your submission</li>
                <li>You will receive notification when the case is accepted or rejected</li>
                <li>If rejected, you can make corrections and resubmit</li>
                <li>You can track the status in the E-Docket system</li>
            </ul>
            
            <p>You can view your case status at any time by logging into the E-Docket system.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} New Mexico Office of the State Engineer</p>
            <p>1220 South St. Francis Drive, Santa Fe, NM 87505 | (505) 827-6091</p>
        </div>
    </div>
</body>
</html>
