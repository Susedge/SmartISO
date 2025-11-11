# Approver Logic Cleanup - November 11, 2025

## Problem Statement
Global admins (admin/superuser) were able to review and approve forms even when they were not assigned as approvers via the `form_signatories` table. This violated the intended security model where ONLY explicitly assigned approvers should be able to approve forms.

## Root Causes Identified

### 1. **canUserApprove() Was Too Permissive**
- Allowed `approving_authority` and `department_admin` types automatically
- Allowed `admin`/`superuser` types based on `admin_can_approve` configuration
- Did NOT verify if users were actually assigned as approvers for any forms

### 2. **pendingApproval() Had Special Admin Bypass**
- Only applied `form_signatories` filtering for `approving_authority` users
- Allowed `admin`/`superuser` to see ALL pending submissions
- Applied department/office filtering for non-admins, but admins bypassed this

### 3. **submitApproval() Had No Assignment Verification**
- Only checked department matching for non-global admins
- Did NOT verify if user was in `form_signatories` for the specific form
- Allowed any admin to approve any form

### 4. **approveForm() Had No Assignment Verification**
- Same issues as `submitApproval()`
- Allowed viewing approval form without being assigned

### 5. **approveAll() Had Admin Bypass**
- Used department/office filtering instead of `form_signatories`
- Admins could bulk approve any forms

---

## Solution Implemented

### **New Security Model: Assignment-Based Approval**

**Core Principle**: User type (admin, department_admin, approving_authority, etc.) does NOT grant approval privileges. Only explicit assignment via `form_signatories` table grants approval rights.

### Changes Made

#### 1. **canUserApprove() Method** (Lines 45-68)
**Before**:
```php
// Allowed based on user_type
if (in_array($userType, ['approving_authority', 'department_admin'])) {
    return true;
}
// Checked admin_can_approve config for admin/superuser
if (in_array($userType, ['admin', 'superuser'])) {
    return $this->configurationModel->getConfig('admin_can_approve', true);
}
```

**After**:
```php
// Check if user is assigned as approver for ANY form via form_signatories
$formSignatoryModel = new \App\Models\FormSignatoryModel();
$assignedForms = $formSignatoryModel->where('user_id', $userId)->findAll();

if (!empty($assignedForms)) {
    return true;
}
return false;
```

**Impact**:
- ✅ User must be in `form_signatories` to access pending approvals page
- ✅ User type is irrelevant - only assignment matters
- ✅ Removed `admin_can_approve` configuration dependency

---

#### 2. **pendingApproval() Method** (Lines 476-560)
**Before**:
```php
// Special handling for approving_authority only
if ($userType === 'approving_authority') {
    $builder->join('form_signatories fsig', 'fsig.form_id = forms.id', 'inner');
    $builder->where('fsig.user_id', $userId);
}

// Department filtering for non-admins
if (!$isGlobalAdmin && $userDepartmentId) {
    $builder->where('users.department_id', $userDepartmentId);
}
```

**After**:
```php
// ALL users filtered by form_signatories
$builder->join('form_signatories fsig', 'fsig.form_id = forms.id', 'inner');
$builder->where('fsig.user_id', $userId);
log_message('info', "User {$userId} ({$userType}) restricted to assigned forms via form_signatories");
```

**Impact**:
- ✅ Admin/superuser see ONLY forms they're assigned to
- ✅ Department admin see ONLY forms they're assigned to
- ✅ Removed department/office bypass for admins
- ✅ Consistent filtering for all user types

---

#### 3. **submitApproval() Method** (Lines 1517-1550)
**Before**:
```php
// Department verification for non-global admins only
if ($needsDepartmentCheck && $userDepartmentId) {
    // Check department match
}
// No form_signatories verification
```

**After**:
```php
// CRITICAL: Verify user is assigned as approver for this specific form
$formSignatoryModel = new \App\Models\FormSignatoryModel();
$isAssignedApprover = $formSignatoryModel
    ->where('form_id', $submission['form_id'])
    ->where('user_id', $userId)
    ->first();

if (!$isAssignedApprover) {
    log_message('warning', "User {$userId} attempted to approve submission {$submissionId} but is not assigned");
    return redirect()->to('/forms/pending-approval')
        ->with('error', 'You are not assigned as an approver for this form. Only assigned signatories can approve.');
}
```

**Impact**:
- ✅ Verifies assignment for EVERY approval attempt
- ✅ No admin bypass
- ✅ Prevents direct URL manipulation

---

#### 4. **approveForm() Method** (Lines 1022-1110)
**Before**:
```php
// Department check for non-global admins
// Service staff filtered by department for non-admins
if ($isGlobalAdmin) {
    $serviceStaff = $userModel->where('user_type', 'service_staff')->findAll();
} else {
    // Filter by department
}
```

**After**:
```php
// CRITICAL: Verify user is assigned as approver
$formSignatoryModel = new \App\Models\FormSignatoryModel();
$isAssignedApprover = $formSignatoryModel
    ->where('form_id', $submission['form_id'])
    ->where('user_id', $userId)
    ->first();

if (!$isAssignedApprover) {
    return redirect()->to('/forms/pending-approval')
        ->with('error', 'You are not assigned as an approver for this form.');
}

// All active service staff available (no department restriction)
$serviceStaff = $userModel->where('user_type', 'service_staff')
                          ->where('active', 1)
                          ->findAll();
```

**Impact**:
- ✅ Verifies assignment before showing approval form
- ✅ Any service staff can be assigned (not restricted by department)
- ✅ Simplified logic - no special admin handling

---

#### 5. **approveAll() Method** (Lines 1618-1688)
**Before**:
```php
// Department/office filtering for non-admins
if (!$isGlobalAdmin && $userDepartmentId) {
    $builder->where('users.department_id', $userDepartmentId);
}
if (!$isGlobalAdmin && $userOfficeId) {
    $builder->where('forms.office_id', $userOfficeId);
}
// No form_signatories check in loop
```

**After**:
```php
// Query only forms user is assigned to
$builder->join('form_signatories fsig', 'fsig.form_id = forms.id', 'inner')
        ->where('fsig.user_id', $userId);

// Double-check in loop
foreach ($pendingSubmissions as $submission) {
    $isAssignedApprover = $formSignatoryModel
        ->where('form_id', $submission['form_id'])
        ->where('user_id', $userId)
        ->first();
    
    if (!$isAssignedApprover) {
        continue; // Skip non-assigned forms
    }
    // Approve...
}
```

**Impact**:
- ✅ Only approves forms user is assigned to
- ✅ Double verification for safety
- ✅ No admin bypass

---

## Summary of Changes

### Removed Features
1. ❌ **admin_can_approve configuration** - No longer used or checked
2. ❌ **Department/office filtering for admins** - Not relevant with assignment-based model
3. ❌ **User type-based approval privileges** - User type alone doesn't grant approval rights
4. ❌ **Global admin bypass** - Admins follow same rules as everyone else

### New Security Rules
1. ✅ **ALL users must be in `form_signatories` to approve**
2. ✅ **Assignment checked for EVERY approval action**
3. ✅ **Consistent logic across all user types**
4. ✅ **No bypass mechanisms**

---

## Testing Instructions

### Test 1: Admin NOT Assigned as Approver
1. Log in as admin/superuser
2. Ensure admin is NOT in `form_signatories` for any form
3. Try to access "Pending Approval"
4. **Expected**: Redirected to dashboard with error: *"You are not assigned as an approver for any forms"*

### Test 2: Admin Assigned as Approver
1. Log in as admin/superuser
2. Assign admin to a form via System Settings → Forms → Form Signatories
3. Try to access "Pending Approval"
4. **Expected**: Can access, but ONLY see forms assigned to them

### Test 3: Department Admin as Approver
1. Log in as department_admin
2. Ensure they're assigned to specific forms via `form_signatories`
3. Access "Pending Approval"
4. **Expected**: See ONLY assigned forms (even if other forms are from their department)

### Test 4: Approving Authority
1. Log in as approving_authority
2. Access "Pending Approval"
3. **Expected**: See ONLY forms they're assigned to in `form_signatories`

### Test 5: Direct URL Manipulation
1. Log in as any user
2. Try to access `/forms/approve-form/{submission_id}` for non-assigned form
3. **Expected**: Redirected with error: *"You are not assigned as an approver for this form"*

### Test 6: Approve All Function
1. Log in as user assigned to some (but not all) pending forms
2. Click "Approve All"
3. **Expected**: Only approves forms they're assigned to, skips others

---

## Database Query to Verify Assignments

```sql
-- Check which users are assigned as approvers for which forms
SELECT 
    u.username,
    u.user_type,
    f.code as form_code,
    f.description as form_description,
    fs.order_position
FROM form_signatories fs
JOIN users u ON u.id = fs.user_id
JOIN forms f ON f.id = fs.form_id
ORDER BY f.code, fs.order_position;

-- Check if specific user is assigned to any forms
SELECT 
    f.code,
    f.description
FROM form_signatories fs
JOIN forms f ON f.id = fs.form_id
WHERE fs.user_id = [USER_ID];
```

---

## Migration Guide

### For Admins Who Need Approval Rights
If admins previously had `admin_can_approve` enabled and need to approve forms, they must now be:
1. Added to `form_signatories` table for each form they need to approve
2. Assigned through: System Settings → Forms → Select Form → Form Signatories → Add Signatory

### For Department Admins
Department admins are no longer automatically approvers. They must be:
1. Explicitly assigned to forms via `form_signatories`
2. Assignment is per-form, not department-wide

---

## Files Modified
- `app/Controllers/Forms.php` - Methods: `canUserApprove`, `pendingApproval`, `submitApproval`, `approveForm`, `approveAll`

## Lines Changed
- Lines 45-68: canUserApprove() - Complete rewrite
- Lines 476-560: pendingApproval() - Removed admin bypass
- Lines 1517-1550: submitApproval() - Added assignment verification
- Lines 1022-1110: approveForm() - Added assignment verification
- Lines 1618-1688: approveAll() - Added assignment filtering

---

## Rollback Instructions

If needed to rollback, the previous logic allowed:
- `approving_authority` and `department_admin` types to always approve
- `admin`/`superuser` to approve based on `admin_can_approve` config
- Department/office filtering for non-admins

However, the new logic is more secure and follows the principle of least privilege. Rollback is NOT recommended.

---

## Benefits of New System

1. **Security**: No user can approve forms they're not explicitly assigned to
2. **Consistency**: Same rules for all user types
3. **Clarity**: Approval rights are explicit, not implicit based on user type
4. **Auditability**: Easy to see who can approve what via `form_signatories` table
5. **Flexibility**: Any user (including admins) can be assigned as approvers on a per-form basis

---

## Deprecated Configuration

The `admin_can_approve` configuration is no longer used. You can:
1. Leave it in the database (harmless)
2. Or remove it with:
```sql
DELETE FROM configurations WHERE config_key = 'admin_can_approve';
```

The system no longer checks this configuration.
