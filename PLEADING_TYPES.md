# Pleading Document Types - E-Docket WRD

## Two Pleading Document Types

The system recognizes **TWO** pleading document types that require electronic stamping:

### 1. Request to Docket
- **Code**: `request_to_docket`
- **Display Name**: "Request to Docket"
- **Purpose**: Request to docket a case for hearing
- **Requires Stamping**: Yes (after acceptance)

### 2. Request for Pre-Hearing
- **Code**: `request_pre_hearing`
- **Display Name**: "Request for Pre-Hearing"
- **Purpose**: Request for pre-hearing scheduling conference
- **Requires Stamping**: Yes (after acceptance)

---

## How Pleading Types Work

### In HU Validation Checklist:
```php
$hasRequest = $case->documents->whereIn('pleading_type', [
    'request_to_docket', 
    'request_pre_hearing'
])->count() > 0;
```

The checklist checks if **at least ONE** of these pleading documents is present.

### In Document Display:
Documents with these pleading types show a **"Pleading Document"** badge.

### In Stamping Logic:
Only documents with these pleading types can be stamped after acceptance:
```php
if (in_array($document->pleading_type, ['request_to_docket', 'request_pre_hearing'])) {
    // Show "Stamp" button
}
```

---

## Common Issue Fixed

### Problem:
The code was inconsistent - some places used:
- ✅ `request_pre_hearing` (CORRECT - matches database)
- ❌ `request_for_pre_hearing` (WRONG - doesn't exist)

### Result:
Documents with "Request for Pre-Hearing" were not being recognized as pleading documents, so:
- HU Validation Checklist showed ❌ instead of ✓
- "Stamp" button didn't appear after acceptance
- Document wasn't flagged as pleading type

### Solution:
All code now uses the correct value: `request_pre_hearing`

---

## Where Pleading Types Are Used

1. **Case Creation Form** - User selects pleading type when uploading
2. **HU Validation Checklist** - Checks if pleading document present
3. **Document Display** - Shows "Pleading Document" badge
4. **Manage Documents** - Shows "Stamp" button for accepted pleading docs
5. **PDF Stamping Service** - Applies electronic stamp to pleading docs

---

## Validation Rules

### Case Submission Requirements:
- **Aggrieved/Protested Cases**: Must have Application + ONE pleading document
- **Compliance Cases**: Must have ONE pleading document (no Application required)

### Stamping Requirements:
- Document must be **accepted** first
- Document must have pleading_type = `request_to_docket` OR `request_pre_hearing`
- Only HU users can stamp documents

---

## Database Schema

### documents table:
```sql
pleading_type VARCHAR(50) NULL
-- Values: 'request_to_docket', 'request_pre_hearing', or NULL
```

### document_types table:
```sql
code: 'request_to_docket' | 'request_pre_hearing'
name: 'Request to Docket' | 'Request for Pre-Hearing'
is_pleading: true
```

---

**Last Updated**: January 2024
