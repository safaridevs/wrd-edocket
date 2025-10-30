# E-Docket Water Rights System - User Guide

## System Overview
The E-Docket system manages water rights cases for the New Mexico Office of the State Engineer (OSE). It handles case creation, document management, party notifications, and workflow approvals.

---

## User Roles & Permissions

### 1. ALU Clerk (Administrative Law Unit Clerk)
**Primary Functions:**
- **Create new cases** - Initiate water rights cases
- **Manage case parties** - Add/edit interested parties and their contact information
- **Upload documents** - Add case-related documents and pleadings
- **Assign experts** - Assign hydrology experts and attorneys to cases
- **Submit to HU** - Forward cases to Hearing Unit for review

**Key Permissions:**
- Create, read, write cases
- Upload documents
- Manage users
- Assign attorneys and experts
- Transmit materials

### 2. HU Admin (Hearing Unit Administrator)
**Primary Functions:**
- **Review cases** - Accept or reject cases submitted by ALU
- **Approve cases** - Final case approval with party notifications
- **Document stamping** - Apply official e-stamps to pleading documents
- **Manage all users** - Full user management capabilities
- **Assign staff** - Assign ALU Clerks and WRD experts to cases

**Key Permissions:**
- All case operations
- Accept/reject filings
- Apply stamps
- Manage users
- Full system access

### 3. HU Clerk (Hearing Unit Clerk)
**Primary Functions:**
- **Case approval** - Approve cases and notify parties
- **Document stamping** - Apply official e-stamps to documents
- **Case management** - Update case status and information
- **User management** - Manage system users

**Key Permissions:**
- Read, write cases
- Accept filings
- Apply stamps
- Manage users

### 4. Party (Interested Party)
**Primary Functions:**
- **View assigned cases** - Access cases where they are listed as a party
- **Upload documents** - Submit pleadings and supporting documents
- **Update contact info** - Maintain current contact information
- **Receive notifications** - Get case updates and status changes
- **Self-representation** - Act as their own legal representative

**Key Permissions:**
- Read accessible cases
- Upload documents
- Update own contact information

**Self-Represented Parties:**
- Have same document upload rights as represented parties
- Receive all case notifications directly
- Can update their own contact information
- No attorney assignment required
- Full access to their cases without legal representation

### 5. Attorney
**Primary Functions:**
- **Represent clients** - Manage attorney-client relationships
- **Access client cases** - View cases for represented clients
- **Upload documents** - Submit legal documents on behalf of clients
- **Update profile** - Maintain attorney contact and bar information

**Key Permissions:**
- Access cases for represented clients
- Upload documents
- Update own profile

### 6. Hydrology Expert
**Primary Functions:**
- **Review technical aspects** - Provide expert analysis on water rights cases
- **Access assigned cases** - View cases assigned for expert review

### 7. WRD Expert (Water Rights Division Expert)
**Primary Functions:**
- **Technical review** - Analyze water rights technical documentation
- **Case consultation** - Provide expertise on water rights matters

---

## Case Creation Workflow

### Step 1: Case Initiation (ALU Clerk)
1. **Login** as ALU Clerk
2. **Navigate** to "New Case" or "Cases" → "Create"
3. **Enter Case Details:**
   - Case number (auto-generated or manual)
   - Case title/description
   - Case type (water rights application, etc.)
   - Priority level
   - Initial status

### Step 2: Add Parties (ALU Clerk)
1. **Access** case details page
2. **Click** "Manage Parties"
3. **Add each party:**
   - Search by email (auto-populates if exists)
   - Enter contact information:
     - Name (individual) or Organization (company)
     - Email address
     - Phone numbers (mobile/office)
     - Mailing address
   - Select party type (applicant, protestor, etc.)
4. **Save** party information

### Step 3: Upload Initial Documents (ALU Clerk)
1. **Click** "Upload Documents" on case page
2. **Select document type:**
   - Application documents
   - Supporting materials
   - Correspondence
   - Technical reports
3. **Choose files** (PDF preferred)
4. **Add descriptions** and metadata
5. **Submit** documents

### Step 4: Assign Experts (ALU Clerk)
1. **Navigate** to case assignments section
2. **Assign Hydrology Expert:**
   - Select from available experts
   - Set assignment date
   - Add notes if needed
3. **Assign Attorney** (if needed):
   - Select ALU attorney
   - Define scope of assignment
   - **Note:** Self-represented parties do not require attorney assignment

### Step 5: Submit to Hearing Unit (ALU Clerk)
1. **Review** case completeness
2. **Click** "Submit to HU" button
3. **Add submission notes**
4. **Confirm** submission
5. **System** automatically notifies HU staff

### Step 6: HU Review (HU Admin/Clerk)
1. **Login** as HU Admin or HU Clerk
2. **Access** submitted case
3. **Review** all documents and party information
4. **Decision options:**
   - **Accept:** Case proceeds to active status
   - **Reject:** Return to ALU with comments
   - **Request changes:** Ask for specific modifications

### Step 7: Case Approval (HU Admin/Clerk)
1. **Click** "Approve Case" button
2. **System automatically:**
   - Updates case status to "Approved"
   - Sends notifications to all parties
   - Notifies assigned attorneys and experts
   - Creates audit log entry

---

## Document Management

### Document Types
- **Pleadings:** Legal documents requiring e-stamps
- **Applications:** Initial water rights applications
- **Supporting Documents:** Technical reports, maps, studies
- **Correspondence:** Letters, emails, official communications
- **Orders:** Official decisions and rulings

### Document Upload Process
1. **Select** appropriate document type
2. **Choose** file (PDF recommended)
3. **Enter** description and metadata
4. **Specify** pleading type if applicable:
   - Request to Docket
   - Request for Pre-Hearing
5. **Submit** for processing

### E-Stamping Process (HU Admin/Clerk Only)
1. **Access** document in case
2. **Click** "Stamp Document" button
3. **System applies:**
   - Official OSE stamp
   - Date and time
   - Case number
   - Clerk initials
4. **Notification** sent to document uploader

---

## Contact Information Management

### For Parties
- **Access:** User menu → "Contact Information"
- **Update:** Name, phones, address (email readonly)
- **Automatic:** Updates reflect across all cases

### For Attorneys
- **Access:** User menu → "Attorney Profile"
- **Update:** Name, phone, bar number (email readonly)
- **Maintains:** Professional credentials

### For System Users
- **Access:** User menu → "Profile"
- **Update:** Account information and preferences
- **Syncs:** Email changes across Person/Attorney records

---

## Notifications System

### Automatic Notifications Sent For:
- Case status changes
- Document approvals/rejections
- E-stamp applications
- Assignment notifications
- Case approvals (to all parties)

### Notification Recipients:
- **Case parties:** All interested parties
- **Assigned attorneys:** Legal representatives
- **Assigned experts:** Technical reviewers
- **System users:** Relevant staff members

---

## Key System Features

### Role-Based Access Control
- Users see only permitted functions
- Case access controlled by party involvement
- Document permissions by role and case relationship

### Audit Trail
- All actions logged with user, timestamp, and details
- Document upload/download tracking
- Case status change history

### Multi-Repository Sync
- Documents sync to SharePoint, OneDrive, Revver
- Website publication for public cases
- Backup and redundancy built-in

### Attorney-Client Management
- Formal representation relationships
- Client access through attorney accounts
- Termination tracking and history
- **Self-represented parties** access cases directly without attorney relationship

---

## Quick Reference - Common Tasks

### Create a Case (ALU Clerk)
1. Cases → New Case
2. Fill case details → Save
3. Add parties → Manage Parties
4. Upload documents → Upload Documents
5. Assign experts → Assignments
6. Submit to HU → Submit button

### Approve a Case (HU Admin/Clerk)
1. Access submitted case
2. Review documents and parties
3. Click "Approve Case"
4. System notifies all parties automatically

### Upload Documents (Any authorized user)
1. Access case page
2. Click "Upload Documents"
3. Select files and document types
4. Add descriptions → Submit

### Self-Represented Party Actions
1. Login with party credentials
2. Access "My Cases" from dashboard
3. Upload documents directly to case
4. Update contact information as needed
5. Receive all notifications without attorney intermediary

### Update Contact Info
- **Parties:** User menu → Contact Information
- **Attorneys:** User menu → Attorney Profile
- **Users:** User menu → Profile

### Apply E-Stamp (HU Admin/Clerk)
1. Access document in case
2. Click "Stamp Document"
3. Confirm stamping action
4. System applies official stamp

---

*This guide covers the primary workflows and functions of the E-Docket system. For technical support or additional questions, contact the system administrator.*