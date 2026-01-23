# E-Docket WRD - Quick Reference Guide

## 🚀 Quick Start: Case Lifecycle

### 1️⃣ CREATE (ALU)
```
ALU Clerk/Attorney
├─ Create Case (Type, Caption)
├─ Add Parties (with attorneys if needed)
├─ Upload Documents (Application + Pleading)
└─ Save as DRAFT
```

### 2️⃣ SUBMIT (ALU)
```
ALU Clerk/Attorney
├─ Review draft case
├─ Click "Submit to HU"
├─ Select notification recipients
└─ Status: SUBMITTED_TO_HU
```

### 3️⃣ REVIEW (HU)
```
HU Clerk/Admin
├─ Review validation checklist
├─ Check documents & parties
└─ Decision:
    ├─ ACCEPT → Status: APPROVED (documents NOT auto-stamped)
    └─ REJECT → Status: REJECTED (back to ALU)
```

### 4️⃣ MANAGE (HU)
```
HU Clerk/Admin (Approved Cases)
├─ Accept/Reject Documents (SEPARATE from case acceptance)
│  └─ Must check "I viewed document"
├─ Stamp Pleading Documents (AFTER accepting document)
│  └─ Applies electronic stamp
├─ Manage Parties
└─ Assign Staff

IMPORTANT: Case acceptance ≠ Document acceptance
           Document acceptance ≠ Document stamping
```

### 5️⃣ CLOSE (HU)
```
HU Clerk/Admin
├─ Click "Close Case"
├─ Enter closure reason
├─ Status: CLOSED
└─ Optional: Archive (HU Admin only)
```

---

## 📋 Required Documents by Case Type

| Case Type | Application | Pleading Document | Notice of Publication |
|-----------|-------------|-------------------|----------------------|
| **Aggrieved** | ✅ Required | ✅ Required | ⚪ Optional |
| **Protested** | ✅ Required | ✅ Required | ⚪ Optional |
| **Compliance** | ❌ Not Required | ✅ Required | ⚪ Optional |

**Pleading Document** = Request to Docket OR Request for Pre-Hearing

---

## 👥 Party Management

### Adding a Party
```
1. Click "Add Party"
2. Select Role (Applicant, Protestant, etc.)
3. Choose Type:
   ├─ Individual → Can be self-represented
   └─ Entity → MUST have attorney
4. Enter contact details
5. Optional: Add attorney
   ├─ Select existing attorney
   └─ OR add new attorney
```

### Editing Attorney
```
1. Navigate to "Manage Parties"
2. Find party with attorney
3. Click "Edit Attorney"
4. Choose:
   ├─ Select different existing attorney
   └─ OR add new attorney
```

---

## 📄 Document Management

### Document States
| State | Icon | Meaning | Next Action |
|-------|------|---------|-------------|
| **Pending** | ⏳ | Awaiting HU review | HU: Accept/Reject |
| **Accepted** | ✓ | Approved by HU | HU: Stamp (if pleading) |
| **Rejected** | ✗ | Rejected by HU | ALU: Fix & reupload |
| **E-Stamped** | 📋 | Electronically filed | None (final state) |

### Document Actions

**Accept Document (HU)**:
```
1. Click "View" to review
2. Check "I confirm I have viewed this document"
3. Click "Accept"
```

**Stamp Document (HU)**:
```
1. Document must be accepted first
2. Document must be pleading type
3. Click "Stamp" button
4. System applies electronic stamp
```

**Reject Document (HU)**:
```
1. Click "View" to review
2. Check "I confirm I have viewed this document"
3. Click "Reject"
4. Enter detailed rejection reason
```

---

## 🔔 Notifications

### Who Gets Notified?

**Case Submission**:
- ✉️ All case parties
- ✉️ All attorneys
- ✉️ Assigned staff
- ✉️ HU team

**Case Acceptance**:
- ✉️ All case parties
- ✉️ All attorneys
- ✉️ Assigned staff

**Case Rejection**:
- ✉️ Case creator (ALU)
- ✉️ Assigned ALU attorney

**Case Closure**:
- ✉️ All case parties
- ✉️ All attorneys
- ✉️ Assigned staff

**Document Rejection**:
- ✉️ Document uploader
- ✉️ Case creator

---

## 🎯 Case Status Reference

| Status | Who Can Edit | Available Actions |
|--------|--------------|-------------------|
| **DRAFT** | ALU | Edit, Delete, Submit |
| **SUBMITTED_TO_HU** | HU | Accept, Reject |
| **REJECTED** | ALU | Edit, Resubmit |
| **APPROVED** | HU | Manage docs, Close |
| **CLOSED** | HU Admin | Archive |
| **ARCHIVED** | HU Admin | View only |

---

## 📝 File Naming Convention

### Format
```
YYYY-MM-DD - [Document Type] - [OSE Numbers].pdf
```

### Examples
```
✅ 2024-01-15 - Application - RG-12345.pdf
✅ 2024-01-15 - Request to Docket.pdf
✅ 2024-02-20 - Notice of Publication - RG-12345, RG-12346.pdf
❌ application.pdf (missing date)
❌ 01-15-2024 - Application.pdf (wrong date format)
❌ 2024-01-15-Application.pdf (missing spaces)
```

---

## 🔐 Role Permissions

| Action | ALU Clerk | ALU Attorney | HU Clerk | HU Admin |
|--------|-----------|--------------|----------|----------|
| Create Case | ✅ | ✅ | ❌ | ❌ |
| Submit Case | ✅ | ✅ | ❌ | ❌ |
| Accept/Reject Case | ❌ | ❌ | ✅ | ✅ |
| Accept/Reject Docs | ❌ | ❌ | ✅ | ✅ |
| Stamp Documents | ❌ | ❌ | ✅ | ✅ |
| Close Case | ❌ | ❌ | ✅ | ✅ |
| Archive Case | ❌ | ❌ | ❌ | ✅ |
| Assign Staff | ❌ | ❌ | ❌ | ✅ |

---

## ⚡ Keyboard Shortcuts & Tips

### Navigation
- Use browser back button to return to previous page
- Click case number to view case details
- Use filters to find cases quickly

### Document Upload
- Drag & drop supported
- Multiple files can be uploaded at once
- Max file size: 10MB per file
- Preferred format: PDF

### Search & Filter
- Filter by case status
- Filter by document type
- Search by case number
- Search by party name

---

## ❓ Common Questions

**Q: Can I edit a case after submission?**  
A: No. Once submitted, only HU can accept/reject. If rejected, ALU can edit and resubmit.

**Q: Can I remove an attorney from an entity?**  
A: No. Entities must have attorney representation. Only individuals can be self-represented.

**Q: What happens if I don't check "I viewed document"?**  
A: You cannot accept or reject the document. The checkbox is mandatory.

**Q: Can I stamp a document before accepting it?**  
A: No. Documents must be accepted first, then stamped.

**Q: Can parties file documents to draft cases?**  
A: No. Only approved/active cases allow party document filing.

**Q: What's the difference between Close and Archive?**  
A: Close ends the case but keeps it visible. Archive moves it to long-term storage (HU Admin only).

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| Can't submit case | Check all required documents uploaded |
| Can't accept document | Check "I viewed document" checkbox |
| Can't remove attorney | Entities must have attorneys |
| Case not in list | Check status filter |
| No notification received | Verify email address |
| Upload fails | Check file size (<10MB) and format |

---

## 📞 Support Contacts

**Technical Issues**: System Administrator  
**Case Questions**: HU Admin  
**Training**: ALU Managing Attorney

---

**Quick Reference Version**: 1.0  
**Last Updated**: January 2024
