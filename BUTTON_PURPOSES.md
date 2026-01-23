# Button Purposes - E-Docket WRD

## Case Show Page - Button Locations & Purposes

### 1. HU Validation Checklist Section
**Location**: Shows when case status = `submitted_to_hu`

**Purpose**: Informational only - shows validation status
- ✓ Application PDF Present
- ✓ Pleading Document Present  
- ✓ Filename Convention Compliant

**Actions**: None - just displays checklist. Directs HU to Documents section for actions.

---

### 2. Documents Section - Case Actions
**Location**: Top right of Documents section

#### When Status = `submitted_to_hu`:
- **"Accept Case" button** (Green)
  - Opens modal to confirm acceptance
  - Changes case status to `approved`
  - Notifies all parties
  - Does NOT automatically accept or stamp documents
  
- **"Reject Case" button** (Red)
  - Opens modal to enter rejection reason
  - Changes case status to `rejected`
  - Notifies ALU staff to fix issues
  - Case returns to ALU for corrections

#### When Status = `approved`:
- **"✓ Case Accepted" badge** (Green) - Read-only status indicator

---

### 3. Manage Documents Page - Document Actions
**Location**: Next to each document

#### For Pending Documents:
- **"Accept" button** (Green)
  - Requires checking "I viewed document" checkbox
  - Marks individual document as accepted
  - Does NOT stamp document automatically
  
- **"Reject" button** (Red)
  - Requires checking "I viewed document" checkbox
  - Enter rejection reason
  - Marks document as rejected

#### For Accepted Pleading Documents:
- **"Stamp" button** (Blue)
  - Only appears for accepted pleading documents
  - Applies electronic stamp to PDF
  - Marks document as "Electronically Filed"
  - Uses document upload date on stamp

---

## Workflow Summary

```
CASE ACCEPTANCE WORKFLOW:
1. HU reviews HU Validation Checklist (informational)
2. HU clicks "Accept Case" in Documents section
3. Case status → approved
4. Documents remain pending (not auto-accepted)

DOCUMENT ACCEPTANCE WORKFLOW:
1. HU goes to "Manage Documents"
2. HU reviews each document individually
3. HU accepts or rejects each document
4. Documents status → accepted or rejected

DOCUMENT STAMPING WORKFLOW:
1. Document must be accepted first
2. Document must be pleading type
3. HU clicks "Stamp" button
4. PDF gets electronic stamp applied
5. Document status → E-Stamped
```

---

## Key Points

### ✅ DO:
- Use "Accept Case" button in **Documents section**
- Accept documents individually in **Manage Documents**
- Stamp documents AFTER accepting them
- Check "I viewed document" before accept/reject

### ❌ DON'T:
- Don't use buttons in HU Validation Checklist (removed)
- Don't expect documents to auto-accept when case is accepted
- Don't expect documents to auto-stamp when accepted
- Don't try to stamp before accepting

---

## Button Hierarchy

**Case Level** (Documents Section):
- Accept Case → Changes case status
- Reject Case → Returns to ALU

**Document Level** (Manage Documents):
- Accept Document → Approves individual document
- Reject Document → Rejects individual document  
- Stamp Document → Applies electronic stamp (after acceptance)

---

## Status Flow

```
Case Status:
draft → submitted_to_hu → approved → closed → archived
                    ↓
                rejected (back to draft)

Document Status:
pending → accepted → e-stamped
    ↓
rejected
```

---

**Last Updated**: January 2024
