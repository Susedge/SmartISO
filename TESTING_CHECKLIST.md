# Post-Implementation Testing Checklist

**Date:** October 22, 2025  
**Purpose:** Verify all security fixes are working correctly  
**Test Environment:** Development/Staging

---

## Prerequisites

Before testing, ensure:
- [ ] Application cache cleared
- [ ] Test users created in at least 2 different departments
- [ ] Test data includes submissions from both departments
- [ ] All user roles available (admin, approver, service_staff, requestor)

---

## Test Setup

### Test Users Required:

| Username | Role | Department | Purpose |
|----------|------|------------|---------|
| admin_user | admin | N/A | Test admin bypass |
| dept1_approver | approving_authority | Department 1 | Test dept filtering |
| dept2_approver | approving_authority | Department 2 | Test cross-dept block |
| dept1_service | service_staff | Department 1 | Test service filtering |
| dept2_service | service_staff | Department 2 | Test cross-dept block |
| dept1_requestor | requestor | Department 1 | Test requestor view |
| dept2_requestor | requestor | Department 2 | Test requestor view |

### Test Data Required:

- [ ] Submission #1 - Submitted by dept1_requestor (Department 1)
- [ ] Submission #2 - Submitted by dept2_requestor (Department 2)
- [ ] At least one completed submission in each department

---

## Security Test Scenarios

### Test Group 1: Cross-Department View Prevention

#### Test 1.1: Approver Cross-Department View ❌ Should FAIL
**User:** dept1_approver (Department 1)  
**Action:** Navigate to `/forms/viewSubmission/[DEPT2_SUBMISSION_ID]`  
**Expected Result:** Redirected to dashboard with error "You can only view submissions from your department"  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

#### Test 1.2: Approver Same-Department View ✅ Should SUCCEED
**User:** dept1_approver (Department 1)  
**Action:** Navigate to `/forms/viewSubmission/[DEPT1_SUBMISSION_ID]`  
**Expected Result:** Submission details displayed  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

#### Test 1.3: Admin Cross-Department View ✅ Should SUCCEED
**User:** admin_user  
**Action:** Navigate to `/forms/viewSubmission/[ANY_SUBMISSION_ID]`  
**Expected Result:** Submission details displayed (no restrictions)  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

---

### Test Group 2: Cross-Department Approval Prevention

#### Test 2.1: Approver Cross-Department Approval ❌ Should FAIL
**User:** dept1_approver (Department 1)  
**Action:** Navigate to `/forms/approve/[DEPT2_SUBMISSION_ID]`  
**Expected Result:** Redirected with error "You can only approve submissions from your department"  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

#### Test 2.2: Approver Same-Department Approval ✅ Should SUCCEED
**User:** dept1_approver (Department 1)  
**Action:** Navigate to `/forms/approve/[DEPT1_SUBMISSION_ID]`  
**Expected Result:** Approval form displayed  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

---

### Test Group 3: Cross-Department Service Prevention

#### Test 3.1: Service Staff Cross-Department Service ❌ Should FAIL
**User:** dept1_service (Department 1)  
**Action:** Navigate to `/forms/service/[DEPT2_SUBMISSION_ID]`  
**Expected Result:** Redirected with error "You can only service submissions from your department"  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

#### Test 3.2: Service Staff Same-Department Service ✅ Should SUCCEED
**User:** dept1_service (Department 1)  
**Action:** Navigate to `/forms/service/[DEPT1_SUBMISSION_ID]` (assigned to them)  
**Expected Result:** Service form displayed  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

---

### Test Group 4: Cross-Department Export Prevention

#### Test 4.1: User Cross-Department Export ❌ Should FAIL
**User:** dept1_requestor (Department 1)  
**Action:** Navigate to `/forms/export/[DEPT2_COMPLETED_ID]/pdf`  
**Expected Result:** Redirected with error "You can only export submissions from your department"  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

#### Test 4.2: User Same-Department Export ✅ Should SUCCEED
**User:** dept1_requestor (Department 1)  
**Action:** Navigate to `/forms/export/[DEPT1_COMPLETED_ID]/pdf`  
**Expected Result:** PDF generated and downloaded  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

---

### Test Group 5: Department-Filtered Listings

#### Test 5.1: Serviced By Me - Department Filter
**User:** dept1_service (Department 1)  
**Action:** Navigate to `/forms/serviced-by-me`  
**Expected Result:** Only shows submissions from Department 1  
**Actual Result:** _______________  
**Check:** Verify all submissions have requestors from Department 1  
**Status:** [ ] PASS [ ] FAIL

#### Test 5.2: Approved By Me - Department Filter
**User:** dept1_approver (Department 1)  
**Action:** Navigate to `/forms/approved-by-me`  
**Expected Result:** Only shows submissions from Department 1  
**Actual Result:** _______________  
**Check:** Verify all submissions have requestors from Department 1  
**Status:** [ ] PASS [ ] FAIL

#### Test 5.3: Rejected By Me - Department Filter
**User:** dept1_approver (Department 1)  
**Action:** Navigate to `/forms/rejected-by-me`  
**Expected Result:** Only shows submissions from Department 1  
**Actual Result:** _______________  
**Check:** Verify all submissions have requestors from Department 1  
**Status:** [ ] PASS [ ] FAIL

---

### Test Group 6: Notification System

#### Test 6.1: Department-Scoped Notifications
**User:** dept1_requestor (Department 1)  
**Action:** Submit a new form  
**Expected Result:** Only Department 1 approvers receive notifications  
**Verification Steps:**
1. Submit form as dept1_requestor
2. Query notifications table: `SELECT * FROM notifications WHERE submission_id = [NEW_SUBMISSION_ID]`
3. Join with users table to get department_id of notified users
4. Confirm all notified users are from Department 1

**SQL Query:**
```sql
SELECT n.*, u.username, u.department_id 
FROM notifications n 
JOIN users u ON u.id = n.user_id 
WHERE n.submission_id = [SUBMISSION_ID]
```

**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

---

### Test Group 7: Service Staff Assignment

#### Test 7.1: Service Staff Dropdown - Department Filter
**User:** dept1_approver (Department 1)  
**Action:** Open approval form for a Department 1 submission  
**Expected Result:** Service staff dropdown only shows Department 1 service staff  
**Verification:** Check dropdown options, ensure no Department 2 service staff appear  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

#### Test 7.2: Service Staff Dropdown - Admin Full Access
**User:** admin_user  
**Action:** Open approval form for any submission  
**Expected Result:** Service staff dropdown shows ALL active service staff (all departments)  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

---

### Test Group 8: Main Listing Pages (Regression Test)

#### Test 8.1: Forms Index - Department Filter
**User:** dept1_requestor (Department 1)  
**Action:** Navigate to `/forms`  
**Expected Result:** 
- Badge shows "Filtered by: Department 1"
- Department dropdown disabled
- Only Department 1 forms shown

**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

#### Test 8.2: Pending Approval - Department Filter
**User:** dept1_approver (Department 1)  
**Action:** Navigate to `/forms/pending-approval`  
**Expected Result:** Only submissions from Department 1 requestors shown  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

#### Test 8.3: Pending Service - Department Filter
**User:** dept1_service (Department 1)  
**Action:** Navigate to `/forms/pending-service`  
**Expected Result:** Only submissions from Department 1 requestors shown  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

---

## Admin Bypass Verification

### Test 9.1: Admin Unrestricted View
**User:** admin_user  
**Action:** View submissions from multiple departments  
**Expected Result:** Can view ALL submissions regardless of department  
**Status:** [ ] PASS [ ] FAIL

### Test 9.2: Admin Unrestricted Approve
**User:** admin_user  
**Action:** Approve submissions from any department  
**Expected Result:** Can approve ALL submissions  
**Status:** [ ] PASS [ ] FAIL

### Test 9.3: Admin Full Service Staff List
**User:** admin_user  
**Action:** Check service staff dropdown in approval form  
**Expected Result:** Shows ALL active service staff from ALL departments  
**Status:** [ ] PASS [ ] FAIL

---

## Error Log Verification

### Check Application Logs
**Location:** `writable/logs/`  
**Action:** Review logs for any errors related to the new department checks  
**Expected:** No PHP errors, warnings, or exceptions  
**Findings:** _______________  
**Status:** [ ] PASS [ ] FAIL

---

## Database Verification

### Check Notification Recipients
```sql
-- Verify notifications are department-scoped
SELECT 
    s.id as submission_id,
    submitter.department_id as submitter_dept,
    n.user_id as notified_user_id,
    approver.department_id as approver_dept,
    approver.username
FROM form_submissions s
JOIN users submitter ON submitter.id = s.submitted_by
JOIN notifications n ON n.submission_id = s.id
JOIN users approver ON approver.id = n.user_id
WHERE s.created_at > '2025-10-22'  -- Recent submissions
ORDER BY s.id DESC;
```

**Expected:** submitter_dept = approver_dept for all rows  
**Actual Result:** _______________  
**Status:** [ ] PASS [ ] FAIL

---

## Performance Check

### Test Response Times
**Action:** Test each fixed method with larger dataset  
**Expected:** Response time < 2 seconds  

| Method | Response Time | Status |
|--------|---------------|--------|
| viewSubmission() | _____ ms | [ ] PASS [ ] FAIL |
| approveForm() | _____ ms | [ ] PASS [ ] FAIL |
| serviceForm() | _____ ms | [ ] PASS [ ] FAIL |
| servicedByMe() | _____ ms | [ ] PASS [ ] FAIL |
| approvedByMe() | _____ ms | [ ] PASS [ ] FAIL |
| rejectedByMe() | _____ ms | [ ] PASS [ ] FAIL |

---

## Overall Test Results

### Summary

| Category | Total Tests | Passed | Failed | Pass Rate |
|----------|-------------|--------|--------|-----------|
| Security | 13 | ___ | ___ | ___% |
| Listings | 3 | ___ | ___ | ___% |
| Admin Bypass | 3 | ___ | ___ | ___% |
| System | 3 | ___ | ___ | ___% |
| **TOTAL** | **22** | **___** | **___** | **___%** |

---

## Sign-Off

### Test Completion

- [ ] All critical security tests passed
- [ ] Admin bypass confirmed working
- [ ] No errors in application logs
- [ ] Database verification complete
- [ ] Performance acceptable

### Approval

| Role | Name | Signature | Date |
|------|------|-----------|------|
| **Tester** | ___________ | ___________ | ______ |
| **Developer** | ___________ | ___________ | ______ |
| **Security Review** | ___________ | ___________ | ______ |
| **Project Manager** | ___________ | ___________ | ______ |

---

## Issues Found

If any tests fail, document here:

### Issue #1
**Test:** _______________  
**Description:** _______________  
**Severity:** [ ] Critical [ ] High [ ] Medium [ ] Low  
**Action Required:** _______________

### Issue #2
**Test:** _______________  
**Description:** _______________  
**Severity:** [ ] Critical [ ] High [ ] Medium [ ] Low  
**Action Required:** _______________

---

**Checklist Status:** ⏳ PENDING  
**Overall Status:** ⏳ AWAITING TESTING  
**Next Action:** Execute all test scenarios and document results
