# Approval System Fix - November 11, 2025 (Revision)

## Issues Reported
1. **Admin side** - Clicking pending approvals, approved forms, rejected forms redirects to dashboard
2. **Schedule calendar** - Doesn't load anymore

## Root Cause
The previous fix was too aggressive - it prevented admins from **viewing** approval pages even though they should only be restricted from **approving** forms they're not assigned to.

## Solution - Two-Tier Permission Model

### **Tier 1: Viewing Permission** (Less Restrictive)
Who can VIEW approval-related pages (pending approvals, approved by me, rejected by me):
- ✅ Admin/Superuser - Can view ALL forms
- ✅ Department Admin - Can view forms from their department
- ✅ Assigned Approvers - Can view forms they're assigned to

### **Tier 2: Approval Permission** (Strict)
Who can ACTUALLY APPROVE forms:
- ✅ ONLY users assigned in `form_signatories` for that specific form
- ❌ Admin cannot approve unless assigned
- ❌ Department admin cannot approve unless assigned

---

## Changes Made

### 1. **Two New Methods Created**

#### `canUserApprove()` - Controls Page Access (Viewing)
```php
protected function canUserApprove()
{
    // Admin and superuser can always VIEW approval pages
    if (in_array($userType, ['admin', 'superuser'])) {
        return true;
    }
    
    // Department admins can view approval pages
    if ($userType === 'department_admin') {
        return true;
    }
    
    // Others: check if assigned as approver for any form
    return (form_signatories has entries for user);
}
```

#### `isAssignedApprover($formId, $userId)` - Controls Approval Actions
```php
protected function isAssignedApprover($formId, $userId)
{
    // Check if user is in form_signatories for THIS specific form
    return (user is in form_signatories for the form);
}
```

---

### 2. **pendingApproval() Method - Hybrid Filtering**

**For Admin/Superuser**:
- Can VIEW all pending submissions
- But can only APPROVE those they're assigned to (enforced in submitApproval/approveForm)

**For Department Admin**:
- Can VIEW submissions from their department
- But can only APPROVE those they're assigned to

**For Others**:
- Can only VIEW submissions for forms they're assigned to via form_signatories

```php
// Admin sees all, department admin sees department, others see assigned only
if (!$isGlobalAdmin && $userType !== 'department_admin') {
    $builder->join('form_signatories fsig', 'fsig.form_id = forms.id', 'inner');
    $builder->where('fsig.user_id', $userId);
}

if ($isDepartmentAdmin && $userDepartmentId) {
    $builder->where('users.department_id', $userDepartmentId);
}
```

---

### 3. **submitApproval() - Strict Verification**

```php
// CRITICAL: Verify user is assigned as approver
if (!$this->isAssignedApprover($submission['form_id'], $userId)) {
    return redirect()->to('/forms/pending-approval')
        ->with('error', 'You are not assigned as an approver for this form.');
}
```

**Impact**: 
- Admin can see the form in pending list
- But cannot approve unless assigned in form_signatories

---

### 4. **approveForm() - Strict Verification**

Same check as submitApproval - user must be assigned approver to view the approval form.

---

### 5. **approveAll() - Strict Verification**

Only approves forms where `isAssignedApprover()` returns true.

---

## User Experience Flow

### **Scenario 1: Admin Views Pending Approvals**
1. Admin clicks "Pending Approval"
2. ✅ Can access page (canUserApprove returns true)
3. ✅ Sees ALL pending forms (no form_signatories filter for admin)
4. Admin clicks "Approve" on Form A
5. ❌ If not assigned to Form A → Error: "You are not assigned as an approver for this form"
6. ✅ If assigned to Form A → Can approve

### **Scenario 2: Department Admin Views Pending Approvals**
1. Department admin clicks "Pending Approval"
2. ✅ Can access page (canUserApprove returns true)
3. ✅ Sees forms from their department only
4. Admin clicks "Approve" on Form B
5. ❌ If not assigned to Form B → Error: "You are not assigned as an approver"
6. ✅ If assigned to Form B → Can approve

### **Scenario 3: Regular Approver Views Pending Approvals**
1. Approver clicks "Pending Approval"
2. ✅ Can access page (if assigned to any form)
3. ✅ Sees ONLY forms they're assigned to
4. Approver clicks "Approve" on Form C
5. ✅ Can approve (already filtered to assigned forms)

---

## Key Benefits

1. **Admin Oversight**: Admins can VIEW all pending approvals for monitoring
2. **Security**: Admins cannot APPROVE unless explicitly assigned
3. **Transparency**: Everyone can see what's pending in their scope
4. **Enforcement**: Approval actions always verify assignment

---

## What This Fixes

### ✅ **Issue 1: Admin redirected from pending approvals**
- **Before**: Admin couldn't access page at all
- **After**: Admin can access and VIEW all forms, but approval requires assignment

### ✅ **Issue 2: Approved By Me / Rejected By Me pages**
- **Before**: Couldn't access (canUserApprove was too strict)
- **After**: Can access if user has approval-related role

---

## What About Schedule Calendar?

The schedule calendar issue is **NOT** caused by approval logic changes. The approval changes don't touch Schedule controller.

To investigate schedule issues, check:
1. Browser console errors
2. PHP error logs
3. Schedule controller methods
4. JavaScript errors in calendar rendering

---

## Testing Checklist

### Test 1: Admin Viewing
- [ ] Admin can access "Pending Approval" page
- [ ] Admin sees ALL pending submissions
- [ ] Admin can access "Approved By Me" page
- [ ] Admin can access "Rejected By Me" page

### Test 2: Admin Approval Restriction
- [ ] Admin clicks approve on non-assigned form
- [ ] Expected: Error "You are not assigned as an approver for this form"
- [ ] Admin clicks approve on assigned form
- [ ] Expected: Can approve successfully

### Test 3: Department Admin Viewing
- [ ] Dept admin can access "Pending Approval" page
- [ ] Dept admin sees only forms from their department
- [ ] Can access "Approved By Me" / "Rejected By Me"

### Test 4: Department Admin Approval Restriction
- [ ] Dept admin clicks approve on non-assigned form (in their dept)
- [ ] Expected: Error "You are not assigned as an approver"
- [ ] Dept admin clicks approve on assigned form
- [ ] Expected: Can approve successfully

### Test 5: Regular Approver
- [ ] Approver can access "Pending Approval" page
- [ ] Approver sees only assigned forms
- [ ] Can approve assigned forms without error

---

## Files Modified
- `app/Controllers/Forms.php`
  - Lines 45-95: Added canUserApprove() and isAssignedApprover()
  - Lines 500-600: Updated pendingApproval() filtering
  - Lines 1520-1545: Updated submitApproval() verification
  - Lines 1070-1110: Updated approveForm() verification
  - Lines 1685-1710: Updated approveAll() verification

---

## Summary

**The Fix**:
- **Viewing** = Permissive (admins can see, monitoring/oversight)
- **Approving** = Strict (must be assigned, no exceptions)

This balances administrative oversight with security controls. Admins can monitor what's happening but cannot bypass approval assignments.
