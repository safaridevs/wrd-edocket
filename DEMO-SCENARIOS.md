# OSE E-Docket Demo Test Scenarios

## Setup Instructions
1. Run `demo-setup.bat` or `php artisan demo:setup`
2. All demo users have password: `password123`

## Demo Scenarios

### 1. Public Access (No Login Required)
**URL**: `/` (Welcome page)
- Browse approved cases without login
- View case details and documents
- Show transparency features

### 2. ALU Clerk Workflow
**Login**: sarah.johnson@ose.nm.gov
**Scenario**: Create and manage water rights cases
1. Login to dashboard
2. Create new case with parties
3. Upload required documents
4. Submit case to Hearing Unit
5. Edit rejected cases

### 3. HU Admin Workflow  
**Login**: david.thompson@ose.nm.gov
**Scenario**: Review and approve cases
1. View pending cases from ALU
2. Review case details and documents
3. Accept case for hearing (becomes active)
4. Apply e-stamps to pleading documents
5. Approve case (becomes public)
6. Send notifications to parties

### 4. Party Workflow
**Login**: john.smith@email.com
**Scenario**: Participate in water rights case
1. View associated cases
2. Upload documents (exhibits, motions)
3. Track case status
4. Receive notifications

### 5. HU Clerk Workflow
**Login**: lisa.martinez@ose.nm.gov  
**Scenario**: Process documents and filings
1. Review party document submissions
2. Approve/reject documents
3. Apply e-stamps to pleading documents
4. Manage case documentation

## Key Features to Demonstrate

### Role-Based Access Control
- Different dashboards per role
- Permission-based navigation
- Restricted document types per role

### Document Management
- PDF e-stamping with timestamps
- Document naming conventions
- File upload validation
- Preview and download functionality

### Case Status Workflow
- Draft → Submitted → Active → Approved
- Status-based permissions
- Automatic notifications

### Public Transparency
- Approved cases visible to public
- No login required for public access
- Complete case information available

## Sample Data Created

### Users (All password: password123)
- **ALU Clerk**: Sarah Johnson (sarah.johnson@ose.nm.gov)
- **ALU Manager**: Michael Rodriguez (michael.rodriguez@ose.nm.gov)  
- **ALU Attorney**: Jennifer Chen (jennifer.chen@ose.nm.gov)
- **HU Admin**: David Thompson (david.thompson@ose.nm.gov)
- **HU Clerk**: Lisa Martinez (lisa.martinez@ose.nm.gov)
- **Hydrology Expert**: Dr. Robert Wilson (robert.wilson@ose.nm.gov)
- **Party 1**: John Smith (john.smith@email.com)
- **Party 2**: Maria Garcia (maria.garcia@email.com)
- **Party 3**: ABC Ranch LLC (contact@abcranch.com)

### Sample Cases
- **WR-2024-001**: Approved (Public access)
- **WR-2024-002**: Active (Hearing in progress)
- **WR-2024-003**: Submitted to HU (Pending review)
- **WR-2024-004**: Draft (ALU working)

## Demo Flow Suggestions

1. **Start with Public Access** - Show transparency
2. **ALU Clerk** - Show case creation process
3. **HU Admin** - Show review and approval
4. **Party** - Show participation features
5. **Back to Public** - Show approved case is now visible

## Technical Highlights
- Modern responsive design
- Role-based permissions
- PDF document stamping
- Email notifications
- Audit trail logging
- Public transparency
- Secure file management