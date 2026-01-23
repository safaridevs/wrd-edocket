# E-Docket WRD - Complete Case Workflow Guide

## Overview
This guide walks through the complete lifecycle of a case from creation to closing, including all user roles and their responsibilities.

---

## User Roles

### ALU (Administrative Law Unit)
- **ALU Clerk** - Creates and manages cases
- **ALU Attorney** - Reviews and manages cases
- **ALU Managing Attorney** - Oversees case assignments

### HU (Hearing Unit)
- **HU Clerk** - Reviews and accepts/rejects cases
- **HU Admin** - Full administrative access

### Other Roles
- **Hydrology Expert** - Provides technical expertise
- **WRD Expert** - Water Rights Division expert
- **Party** - External case participants

---

## Complete Case Workflow

### PHASE 1: CASE CREATION (ALU Clerk/Attorney)

#### Step 1.1: Create New Case
1. Navigate to **Cases** → **Create New Case**
2. Fill in case details:
   - **Case Type**: Choose from:
     - Aggrieved (requires Application document)
     - Protested (requires Application document)
     - Compliance (no Application required)
   - **Caption**: Enter case description
   - **OSE File Numbers**: Add relevant file numbers (optional)

#### Step 1.2: Add Parties
3. Add case parties with details:
   - **Role**: Applicant, Protestant, Respondent, Violator, Alleged Violator
   - **Type**: Individual or Entity (Non-Person)
   - **Contact Info**: Name, email, phone, address
   - **Attorney Representation** (optional):
     - Select existing attorney from system
     - OR add new attorney with bar number and address

**Note**: Entities MUST have attorney representation

#### Step 1.3: Upload Required Documents
4. Upload mandatory documents:
   - **Application PDF** (for Aggrieved/Protested cases only)
   - **Pleading Document** (Request to Docket OR Request for Pre-Hearing)
   - **Other supporting documents** (optional)

**File Naming Convention**: `YYYY-MM-DD - [Document Type] - [OSE Numbers].pdf`
- Example: `2024-01-15 - Application - RG-12345.pdf`

#### Step 1.4: Review and Save as Draft
5. Click **"Save as Draft"**
6. Case status: **DRAFT**
7. Case is saved but not yet submitted to HU

---

### PHASE 2: CASE SUBMISSION (ALU Clerk/Attorney)

#### Step 2.1: Review Draft Case
1. Navigate to **Cases** → Select your draft case
2. Review all information:
   - Parties are complete with contact info
   - All required documents uploaded
   - File naming conventions followed
   - Attorney assignments (if applicable)

#### Step 2.2: Submit to Hearing Unit
3. Click **"Submit to HU"** button
4. Select recipients to notify:
   - Case parties (checked by default)
   - Attorneys (checked by default)
   - Assigned staff (checked by default)
5. Add optional custom message
6. Click **"Submit to HU & Notify Selected"**

**Result**: 
- Case status changes to: **SUBMITTED_TO_HU**
- Email notifications sent to selected recipients
- Case appears in HU review queue

---

### PHASE 3: HU REVIEW (HU Clerk/Admin)

#### Step 3.1: Access Submitted Cases
1. Navigate to **Cases** → Filter by status: **Submitted to HU**
2. Click on case to review

#### Step 3.2: Review Case Details
3. Review **HU Validation Checklist**:
   - ✓ Application PDF Present (for Aggrieved/Protested)
   - ✓ Pleading Document Present
   - ✓ Filename Convention Compliant

4. Review case information:
   - Parties and contact information
   - Documents and content
   - OSE file numbers
   - Caption accuracy

#### Step 3.3: Accept or Reject Case

**IMPORTANT**: Use the Accept/Reject buttons in the **Documents section**, NOT in the HU Validation Checklist.

**Option A: ACCEPT CASE**
1. Scroll to the **Documents** section
2. Click **"Accept Case"** button
3. Confirm notification recipients
4. Click **"Accept & Notify All"**

**Result**:
- Case status changes to: **APPROVED**
- Email notifications sent to all parties
- Case becomes active for document filing
- Audit log entry created
- **IMPORTANT**: Documents are NOT automatically stamped. Stamping is a separate manual action performed later.

**Option B: REJECT CASE**
1. Click **"Reject Case"** button
2. Enter detailed rejection reason
3. Click **"Reject & Notify ALU"**

**Result**:
- Case status changes to: **REJECTED**
- Email sent to ALU staff with rejection reason
- Case returns to ALU for corrections
- ALU can edit and resubmit

---

### PHASE 4: ACTIVE CASE MANAGEMENT (After Acceptance)

#### Step 4.1: Document Management (HU)

**IMPORTANT**: Accepting a case does NOT automatically accept or stamp documents. Each document must be individually reviewed and accepted.

**Accept Documents**:
1. Navigate to **Cases** → Select case → **Manage Documents**
2. For each pending document:
   - Click **"View"** to review document
   - Check **"I confirm I have viewed this document"**
   - Click **"Accept"** to approve
   - OR Click **"Reject"** and provide reason

**Stamp Documents** (Separate Action After Acceptance):
3. For accepted pleading documents:
   - Document must be accepted first
   - Click **"Stamp"** button (appears only for accepted pleading docs)
   - System applies electronic stamp with:
     - Case number
     - Upload date
     - "Electronically Filed" marking
   - Document marked as **E-Stamped**

#### Step 4.2: Party Management (HU/ALU)
1. Navigate to **Cases** → Select case → **Manage Parties**
2. Available actions:
   - **Add Party**: Add new party to case
   - **Edit Party**: Update party information
   - **Edit Attorney**: Manage attorney representation
   - **Remove Party**: Remove party from case
   - **Notify Parties**: Send notifications to selected parties

#### Step 4.3: Staff Assignments (HU Admin)
1. Assign case to staff members:
   - **ALU Attorneys**: Legal review
   - **ALU Clerks**: Administrative support
   - **Hydrology Experts**: Technical analysis
   - **WRD Experts**: Water rights expertise

2. Navigate to case → Click **"Assign"** next to each role
3. Select staff member from dropdown
4. Click **"Assign"**

#### Step 4.4: Ongoing Document Filing
- **Parties** can file documents to active cases
- **ALU/HU** can upload documents anytime
- All documents require HU acceptance
- Pleading documents can be stamped after acceptance

---

### PHASE 5: CASE CLOSING (HU Clerk/Admin)

#### Step 5.1: Prepare for Closure
1. Ensure all documents are processed:
   - All pending documents accepted or rejected
   - Required pleading documents stamped
   - No outstanding issues

#### Step 5.2: Close Case
2. Navigate to case → Click **"Close Case"** button
3. Enter **Reason for Closure**:
   - Settlement reached
   - Hearing completed
   - Withdrawn by applicant
   - Administrative closure
   - Other (specify)

4. Click **"Close Case"**

**Result**:
- Case status changes to: **CLOSED**
- Email notifications sent to all parties
- No further document filing allowed
- Case remains accessible for viewing
- Audit log entry created

#### Step 5.3: Archive Case (Optional - HU Admin only)
5. For closed cases, click **"Archive Case"**
6. Confirm archival

**Result**:
- Case status changes to: **ARCHIVED**
- Case moved to archive storage
- Reduced visibility in main case lists
- Still accessible via search/direct link

---

## Case Status Flow Diagram

```
DRAFT
  ↓ (ALU submits)
SUBMITTED_TO_HU
  ↓ (HU reviews)
  ├→ APPROVED (accepted) → ACTIVE
  │                          ↓ (HU closes)
  │                        CLOSED
  │                          ↓ (HU archives)
  │                        ARCHIVED
  │
  └→ REJECTED (rejected) → back to DRAFT (ALU fixes & resubmits)
```

---

## Document Workflow

### Document States:
1. **Pending** - Uploaded, awaiting HU review
2. **Accepted** - Approved by HU
3. **Rejected** - Rejected by HU with reason
4. **E-Stamped** - Accepted + electronically stamped (pleading docs only)

### Document Actions by Role:

**ALU Clerk/Attorney (Draft/Rejected cases)**:
- Upload documents
- Delete documents
- Edit case details

**HU Clerk/Admin (All cases)**:
- Accept documents (with mandatory view confirmation)
- Reject documents (with reason)
- Stamp accepted pleading documents
- Upload additional documents

**Parties (Active/Approved cases)**:
- File documents to case
- View case documents
- Receive notifications

---

## Key Features

### 1. Attorney Management
- Assign attorneys to parties
- Edit attorney information
- Remove attorney representation (individuals only)
- Entities must have attorney representation

### 2. Service List
- Automatically generated from case parties
- Includes all parties and their attorneys
- Used for case notifications

### 3. Notifications
- Case submission to HU
- Case acceptance/rejection
- Document acceptance/rejection
- Case closure
- Custom messages supported

### 4. Audit Trail
- All actions logged with:
  - User who performed action
  - Timestamp
  - Action type
  - Metadata (if applicable)

### 5. Document Validation
- File naming convention checks
- Required document checks
- File type validation (PDF preferred)
- File size limits (10MB max)

---

## Common Scenarios

### Scenario 1: Simple Aggrieved Case
1. ALU creates case with applicant party
2. Uploads Application + Request to Docket
3. Submits to HU
4. HU reviews and accepts
5. HU accepts and stamps documents
6. Case proceeds to hearing
7. HU closes case after resolution

### Scenario 2: Case with Attorney Representation
1. ALU creates case with entity party
2. Adds attorney for entity (mandatory)
3. Uploads required documents
4. Submits to HU
5. HU reviews and accepts
6. Attorney files additional documents
7. HU accepts/stamps documents
8. HU closes case

### Scenario 3: Case Rejection and Resubmission
1. ALU creates and submits case
2. HU reviews and finds issues
3. HU rejects with detailed reason
4. ALU receives notification
5. ALU edits case to fix issues
6. ALU resubmits to HU
7. HU reviews and accepts
8. Case proceeds normally

### Scenario 4: Multi-Party Case
1. ALU creates case
2. Adds multiple parties (applicant, protestant)
3. Assigns attorneys to parties
4. Uploads documents
5. Submits to HU
6. HU accepts case
7. All parties notified
8. Parties can file documents
9. HU manages document acceptance
10. HU closes case when complete

---

## Best Practices

### For ALU Staff:
1. ✓ Follow file naming conventions strictly
2. ✓ Verify all party contact information
3. ✓ Ensure entities have attorney representation
4. ✓ Review case before submission
5. ✓ Respond promptly to rejections

### For HU Staff:
1. ✓ Review all documents thoroughly
2. ✓ Provide detailed rejection reasons
3. ✓ Accept documents promptly
4. ✓ Stamp pleading documents after acceptance
5. ✓ Keep parties informed via notifications

### For All Users:
1. ✓ Use descriptive file names
2. ✓ Upload PDF format when possible
3. ✓ Keep contact information current
4. ✓ Check audit trail for case history
5. ✓ Use custom messages in notifications

---

## Troubleshooting

### Issue: Cannot submit case
**Solution**: Ensure all required documents uploaded and parties added

### Issue: Document not accepting
**Solution**: Check that "I have viewed" checkbox is checked

### Issue: Cannot remove attorney
**Solution**: Entities must have attorney representation; only individuals can be self-represented

### Issue: Case not appearing in list
**Solution**: Check status filter; case may be in different status than expected

### Issue: Notification not received
**Solution**: Verify email addresses in party/user profiles; check spam folder

---

## Support

For technical issues or questions:
- Contact System Administrator
- Review audit trail for case history
- Check user permissions for role-based access

---

**Document Version**: 1.0  
**Last Updated**: January 2024  
**System**: E-Docket WRD - Water Rights Division Case Management
