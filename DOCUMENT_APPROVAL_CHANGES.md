# Document Approval Changes - Removal of Unapprove/Unaccept Functionality

## Overview
Removed the ability for HU team members to unapprove or unaccept documents once they have been approved or stamped. This ensures that due diligence is performed before clicking approve, as there is no way to reverse the action.

## Changes Made

### 1. CaseController.php
- **Removed**: `unapproveDocument()` method
- **Impact**: HU users can no longer unapprove documents that have been previously approved

### 2. routes/web.php  
- **Removed**: Route for `cases/{case}/documents/{document}/unapprove`
- **Impact**: No endpoint exists for unapproving documents

### 3. resources/views/cases/documents/manage.blade.php
- **Removed**: "Unapprove" button for approved documents
- **Removed**: `unrejectDocument()` JavaScript function
- **Updated**: Conditional logic to only show Approve/Reject buttons for pending documents
- **Impact**: UI no longer provides option to unapprove approved documents

## Current Document State Flow

### Before Changes
```
Pending → Approve → Approved → Unapprove → Pending
Pending → Reject → Rejected → Approve → Approved
```

### After Changes  
```
Pending → Approve → Approved (FINAL)
Pending → Reject → Rejected → Approve → Approved (FINAL)
```

## Document States

| State | Available Actions | Notes |
|-------|------------------|-------|
| **Pending** | Approve, Reject | Initial state for new documents |
| **Approved** | None | Final state - cannot be changed |
| **Rejected** | Approve only | Can be approved but not re-rejected |
| **Stamped** | None | Final state - cannot be changed |

## Business Rules Enforced

1. **One-Way Approval**: Once a document is approved, it cannot be unapproved
2. **One-Way Stamping**: Once a document is stamped, it cannot be unstamped  
3. **Due Diligence Required**: HU team must carefully review before approving
4. **Rejected Documents**: Can still be approved after rejection, but approval is final

## Benefits

- **Prevents Accidental Reversals**: No risk of accidentally unapproving important documents
- **Audit Trail Integrity**: Maintains clear approval history without reversals
- **Forces Careful Review**: HU team must be certain before approving
- **Compliance**: Ensures approved/stamped documents maintain their legal status

## Migration Notes

- No database changes required
- Existing approved/stamped documents remain unchanged
- No impact on document upload or initial approval workflow
- Only affects the ability to reverse approvals