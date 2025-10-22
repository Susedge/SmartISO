# Department-Based Access Control - Quick Fix Guide

**Priority:** 🔴 CRITICAL  
**Estimated Time:** 2-3 hours  
**Files to Modify:** 3 files (2 controllers, 1 model)

---

## Summary of Issues

The system verification identified **9 critical security vulnerabilities** that allow cross-department data access. These must be fixed immediately before production deployment.

### Vulnerability Categories
1. **Direct Access Bypasses** - viewSubmission, approveForm, serviceForm, export (4 issues)
2. **Listing Methods** - servicedByMe, approvedByMe, rejectedByMe (3 issues)
3. **Notification System** - All approvers notified regardless of department (1 issue)
4. **Service Staff Assignment** - Can assign staff from other departments (1 issue)

---

## File 1: app/Controllers/Forms.php (8 fixes)

### Fix 1: viewSubmission() - Line 747
**Add after line 757** (after initial permission check):

```php
// Department verification for non-admin users
$userDepartmentId = session()->get('department_id');
$isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);

if (!$isAdmin && $userDepartmentId) {
    // Get submitter's department
    $submitter = $this->userModel->find($submission['submitted_by']);
    if (!$submitter || $submitter['department_id'] != $userDepartmentId) {
        return redirect()->to('/dashboard')
            ->with('error', 'You can only view submissions from your department');
    }
}
```

---

### Fix 2: approveForm() - Line 1003
**Add after line 1018** (after getting submission):

```php
// Department verification for non-admin approvers
$userDepartmentId = session()->get('department_id');
$isAdmin = in_array($userType, ['admin', 'superuser']);

if (!$isAdmin && $userDepartmentId) {
    $requestor = $this->userModel->find($submission['submitted_by']);
    if (!$requestor || $requestor['department_id'] != $userDepartmentId) {
        return redirect()->to('/forms/pending-approval')
            ->with('error', 'You can only approve submissions from your department');
    }
}
```

---

### Fix 3: serviceForm() - Line 1075
**Add after getting submission** (after find operation):

```php
// Department verification for non-admin service staff
$userDepartmentId = session()->get('department_id');
$userType = session()->get('user_type');
$isAdmin = in_array($userType, ['admin', 'superuser']);

if (!$isAdmin && $userDepartmentId) {
    $requestor = $this->userModel->find($submission['submitted_by']);
    if (!$requestor || $requestor['department_id'] != $userDepartmentId) {
        return redirect()->to('/forms/pending-service')
            ->with('error', 'You can only service submissions from your department');
    }
}
```

---

### Fix 4: export() - Line 1730
**Replace the entire method:**

```php
public function export($id, $format = 'pdf')
{
    // Get user context
    $userId = session()->get('user_id');
    $userType = session()->get('user_type');
    $userDepartmentId = session()->get('department_id');
    $isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);
    
    // Ensure submission exists
    $submission = $this->formSubmissionModel->find($id);
    if (!$submission) {
        return redirect()->to('/forms/my-submissions')->with('error', 'Submission not found');
    }
    
    // Department verification for non-admins
    if (!$isAdmin && $userDepartmentId) {
        $submitter = $this->userModel->find($submission['submitted_by']);
        if (!$submitter || $submitter['department_id'] != $userDepartmentId) {
            return redirect()->to('/dashboard')
                ->with('error', 'You can only export submissions from your department');
        }
    }
    
    // Ensure submission is completed before allowing export
    if (($submission['status'] ?? '') !== 'completed') {
        return redirect()->to('/forms/my-submissions')->with('error', 'Export is only available for completed submissions');
    }

    $format = strtolower($format);

    // PdfGenerator::generateFormPdf() handles both PDF and Word formats
    if (in_array($format, ['pdf','word','docx'])) {
        return redirect()->to('/pdfgenerator/generateFormPdf/' . $id . '/' . $format);
    }

    return redirect()->back()->with('error', 'Invalid export format');
}
```

---

### Fix 5: servicedByMe() - Line 1296
**Add after the existing where clause** (after line ~1326):

```php
// Department filtering for non-admin service staff
$userDepartmentId = session()->get('department_id');
$userType = session()->get('user_type');
$isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);

if (!$isAdmin && $userDepartmentId) {
    $builder->where('requestor.department_id', $userDepartmentId);
}
```

---

### Fix 6: approvedByMe() - Line 1343
**Find the query builder section and add department filtering:**

```php
// After joining tables, before orderBy, add:
$userDepartmentId = session()->get('department_id');
$userType = session()->get('user_type');
$isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);

if (!$isAdmin && $userDepartmentId) {
    $builder->where('requestor.department_id', $userDepartmentId);
}
```

---

### Fix 7: rejectedByMe() - Line 1386
**Add same department filtering as approvedByMe:**

```php
// After joining tables, before orderBy, add:
$userDepartmentId = session()->get('department_id');
$userType = session()->get('user_type');
$isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);

if (!$isAdmin && $userDepartmentId) {
    $builder->where('requestor.department_id', $userDepartmentId);
}
```

---

### Fix 8: Service Staff Assignment Filter - Line 1045 (inside approveForm)
**Replace the service staff query** (around line 1045):

```php
// Get available service staff - filtered by department for non-admins
$userModel = new \App\Models\UserModel();
$userDepartmentId = session()->get('department_id');
$isAdmin = in_array(session()->get('user_type'), ['admin', 'superuser']);

if ($isAdmin) {
    // Admins can assign any service staff
    $serviceStaff = $userModel->where('user_type', 'service_staff')
                              ->where('active', 1)
                              ->findAll();
} else if ($userDepartmentId) {
    // Non-admins can only assign service staff from their department
    $serviceStaff = $userModel->where('user_type', 'service_staff')
                              ->where('active', 1)
                              ->where('department_id', $userDepartmentId)
                              ->findAll();
} else {
    $serviceStaff = [];
}
```

---

## File 2: app/Models/NotificationModel.php (1 fix)

### Fix 9: createSubmissionNotification() - Line 191
**Replace the entire method:**

```php
public function createSubmissionNotification($submissionId, $formCode)
{
    // Get submission details
    $submissionModel = new \App\Models\FormSubmissionModel();
    $submission = $submissionModel->find($submissionId);
    
    if (!$submission) return;
    
    // Get submitter's department
    $userModel = new UserModel();
    $submitter = $userModel->find($submission['submitted_by']);
    $submitterDepartment = $submitter['department_id'] ?? null;
    
    // Get form-specific assigned approvers
    $formSignatoryModel = new \App\Models\FormSignatoryModel();
    $assignedApprovers = $formSignatoryModel->getFormSignatories($submission['form_id']);
    
    // If no specific approvers assigned, fall back to approving authorities FROM THE SAME DEPARTMENT
    if (empty($assignedApprovers)) {
        if ($submitterDepartment) {
            // Only notify approvers from the same department
            $assignedApprovers = $userModel->where('user_type', 'approving_authority')
                                           ->where('department_id', $submitterDepartment)
                                           ->where('active', 1)
                                           ->findAll();
        } else {
            // No department - notify all (legacy support for data without departments)
            $assignedApprovers = $userModel->getUsersByType('approving_authority');
        }
    }
    
    $title = 'New Service Request Requires Approval';
    $message = "A new {$formCode} request has been submitted by " . ($submission['submitted_by_name'] ?? 'a user') . " and requires your approval.";
    
    foreach ($assignedApprovers as $approver) {
        $userId = isset($approver['user_id']) ? $approver['user_id'] : $approver['id'];

        // Insert only columns that exist in the current notifications table.
        $this->insert([
            'user_id'       => $userId,
            'submission_id' => $submissionId,
            'title'         => $title,
            'message'       => $message,
            'read'          => 0,
            'created_at'    => date('Y-m-d H:i:s')
        ]);

        // Send email notification
        $this->sendEmailNotification($userId, $title, $message);
    }
}
```

---

## File 3: UI Improvements (Optional - Lower Priority)

### Add Department Indicators to Views

**app/Views/forms/serviced_by_me.php**
**app/Views/forms/approved_by_me.php**
**app/Views/forms/rejected_by_me.php**

Add this badge at the top of each view (after the title):

```php
<?php 
$userDepartmentId = session()->get('department_id');
$userType = session()->get('user_type');
$isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);
if (!$isAdmin && $userDepartmentId): 
?>
    <div class="alert alert-info mb-3">
        <i class="fas fa-filter"></i> 
        <strong>Department Filter Active:</strong> Showing submissions from your department only.
    </div>
<?php endif; ?>
```

---

## Testing After Fixes

### Test Plan

1. **Cross-Department View Test**
   - User A (Dept 1, approver) accesses `/forms/viewSubmission/[Dept2_ID]`
   - Expected: Error message "You can only view submissions from your department"

2. **Cross-Department Approval Test**
   - User B (Dept 1, approver) accesses `/forms/approve/[Dept2_ID]`
   - Expected: Error message "You can only approve submissions from your department"

3. **Cross-Department Service Test**
   - User C (Dept 1, service) accesses `/forms/service/[Dept2_ID]`
   - Expected: Error message "You can only service submissions from your department"

4. **Cross-Department Export Test**
   - User D (Dept 1) accesses `/forms/export/[Dept2_ID]/pdf`
   - Expected: Error message "You can only export submissions from your department"

5. **Notification Filtering Test**
   - User E (Dept 1, requestor) submits a form
   - Check notifications table: Only Dept 1 approvers should receive notifications

6. **Service Staff Assignment Test**
   - User F (Dept 1, approver) approves submission
   - Check service staff dropdown: Only Dept 1 service staff should appear

7. **Admin Full Access Test**
   - Admin user performs all above actions
   - Expected: All actions succeed (no restrictions)

---

## Implementation Steps

1. **Backup current files**
   ```powershell
   Copy-Item app/Controllers/Forms.php app/Controllers/Forms.php.backup
   Copy-Item app/Models/NotificationModel.php app/Models/NotificationModel.php.backup
   ```

2. **Apply fixes in order**
   - Start with Forms.php (fixes 1-8)
   - Then NotificationModel.php (fix 9)
   - Finally, UI improvements (optional)

3. **Test each fix individually**
   - Test fix 1, then fix 2, etc.
   - Verify no regressions

4. **Perform comprehensive testing**
   - Execute all test scenarios
   - Check error logs for any issues

5. **Deploy to staging/production**
   - Only after all tests pass

---

## Rollback Plan

If issues occur:

```powershell
# Restore backup files
Copy-Item app/Controllers/Forms.php.backup app/Controllers/Forms.php
Copy-Item app/Models/NotificationModel.php.backup app/Models/NotificationModel.php
```

---

## Expected Behavior After Fixes

| Scenario | Before Fix | After Fix |
|----------|-----------|-----------|
| Approver views Dept 2 submission | ✅ Allowed | ❌ Denied |
| Approver approves Dept 2 submission | ✅ Allowed | ❌ Denied |
| Service staff services Dept 2 submission | ✅ Allowed | ❌ Denied |
| User exports Dept 2 submission PDF | ✅ Allowed | ❌ Denied |
| Dept 1 requestor submits form | 📧 All approvers notified | 📧 Only Dept 1 approvers notified |
| Approver assigns service staff | 👥 All service staff shown | 👥 Only Dept 1 service staff shown |
| Admin views any submission | ✅ Allowed | ✅ Allowed |
| "Serviced by me" shows Dept 2 items | ✅ Shows all | ✅ Only Dept 1 |

---

## Security Verification Checklist

After implementing all fixes, verify:

- [ ] Non-admin users cannot view submissions from other departments
- [ ] Non-admin approvers cannot approve submissions from other departments
- [ ] Non-admin service staff cannot service submissions from other departments
- [ ] Non-admin users cannot export submissions from other departments
- [ ] Notifications are only sent to approvers in the same department
- [ ] Service staff assignment only shows staff from the same department
- [ ] "Serviced by me" only shows submissions from user's department
- [ ] "Approved by me" only shows submissions from user's department
- [ ] "Rejected by me" only shows submissions from user's department
- [ ] Admin users retain full access to all departments
- [ ] Superusers retain full access to all departments
- [ ] Department admins retain full access to all departments
- [ ] Error messages are appropriate and don't leak information
- [ ] Audit logs capture all access attempts (including denied ones)

---

**Document Version:** 1.0  
**Last Updated:** October 22, 2025  
**Status:** Ready for Implementation
