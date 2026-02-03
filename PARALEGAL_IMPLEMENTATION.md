# Paralegal Assignment Feature - Implementation Summary

## Overview
Attorneys can now assign paralegals to cases they are representing. Paralegals are linked to the attorney's client through the `case_parties` table using the `client_party_id` field.

## Database Structure
- **Table**: `case_parties`
- **Key Fields**:
  - `role`: enum including 'paralegal'
  - `client_party_id`: links paralegal to attorney's client

## Relationship Model
```
Client (applicant/protestant) → Attorney (counsel) → Paralegal (paralegal)
                                      ↓
                            client_party_id links paralegal to same client
```

## Implementation Details

### 1. Routes (web.php)
```php
Route::post('cases/{case}/paralegals', [CaseController::class, 'addParalegal'])
    ->name('cases.paralegals.add');
Route::delete('cases/{case}/paralegals/{party}', [CaseController::class, 'removeParalegal'])
    ->name('cases.paralegals.remove');
```

### 2. Controller Methods (CaseController.php)

#### addParalegal()
- **Permission**: Only attorneys (role='party' with counsel role on case)
- **Validation**: first_name, last_name, email, phone_office, phone_mobile
- **Process**:
  1. Verify user is counsel on the case
  2. Create or find Person record
  3. Get attorney's counsel party record
  4. Create paralegal party linked to attorney's client via `client_party_id`
  5. Add to service list

#### removeParalegal()
- **Permission**: Only the attorney who added the paralegal
- **Process**:
  1. Verify paralegal belongs to attorney's client
  2. Remove from service list
  3. Delete paralegal party record

### 3. User Model Permissions (User.php)
```php
public function canAssignParalegal(): bool
{
    return $this->isAttorney();
}
```

### 4. View Implementation (cases/show.blade.php)

#### Paralegal Section
- Displays under "Case Parties" section
- Only visible to attorneys (counsel role)
- Shows list of attorney's paralegals
- "Add Paralegal" button
- "Remove" button for each paralegal

#### Modal Form
- Fields: First Name, Last Name, Email, Office Phone, Mobile Phone
- Submits to `cases.paralegals.add` route

#### JavaScript Functions
```javascript
showAddParalegalModal()
hideAddParalegalModal()
removeParalegal(partyId)
```

## Features

### For Attorneys
- ✅ Add multiple paralegals to cases they represent
- ✅ View all their paralegals on a case
- ✅ Remove paralegals they added
- ✅ Paralegals automatically added to service list
- ✅ Paralegals get case access through case_parties relationship

### For Paralegals
- ✅ Upload documents to cases they're assigned to
- ✅ View case details and documents
- ✅ Receive case notifications via service list
- ✅ Same document upload permissions as attorneys
- ✅ Can only access cases where they're assigned as paralegal

### Security
- ✅ Only attorneys can add paralegals
- ✅ Attorneys can only add paralegals to cases they represent
- ✅ Attorneys can only remove their own paralegals
- ✅ Paralegals linked to specific attorney's client

### Data Integrity
- ✅ Person records reused if email exists
- ✅ Duplicate prevention (same person can't be added twice)
- ✅ Service list automatically updated
- ✅ Cascade delete when paralegal removed

## Usage Example

1. Attorney logs in and views a case they're representing
2. In "Case Parties" section, they see "My Paralegals" subsection
3. Click "Add Paralegal" button
4. Fill in paralegal information (name, email, phones)
5. Submit form
6. Paralegal appears in the list with "Remove" button
7. Paralegal receives case notifications via service list
8. Attorney can remove paralegal anytime

## Testing Checklist
- [ ] Attorney can add paralegal to their case
- [ ] Attorney cannot add paralegal to case they don't represent
- [ ] Non-attorney cannot access paralegal functions
- [ ] Paralegal appears in service list
- [ ] Paralegal can be removed by attorney
- [ ] Duplicate email prevention works
- [ ] Multiple paralegals can be added
- [ ] Paralegal linked to correct client via client_party_id
- [ ] Paralegal can upload documents to assigned cases
- [ ] Paralegal can view case details
- [ ] Paralegal cannot upload to cases they're not assigned to
- [ ] Paralegal receives case notifications

## Files Modified
1. `routes/web.php` - Added paralegal routes
2. `app/Http/Controllers/CaseController.php` - Added addParalegal() and removeParalegal()
3. `app/Models/User.php` - Added canAssignParalegal() permission, updated canAccessCase() to include paralegals
4. `app/Models/CaseModel.php` - Updated canUserUploadDocuments() to allow paralegals to upload documents
5. `resources/views/cases/show.blade.php` - Fixed paralegal filtering logic

## Permissions Summary

### Paralegals Can:
- ✅ View cases they're assigned to
- ✅ Upload documents to active/approved cases
- ✅ Download case documents
- ✅ Receive case notifications
- ✅ Access case details and parties

### Paralegals Cannot:
- ❌ Create new cases
- ❌ Submit cases to HU
- ❌ Approve/reject documents (HU only)
- ❌ Assign staff to cases
- ❌ Close or archive cases
- ❌ Add other paralegals (attorney only)

## Database Schema
No migration needed - uses existing `case_parties` table structure:
- `role` enum already includes 'paralegal'
- `client_party_id` field already exists for linking
