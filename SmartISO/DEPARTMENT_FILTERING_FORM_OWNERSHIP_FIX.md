# DEPARTMENT FILTERING FIX - FORM OWNERSHIP MODEL

## Date: November 23, 2025

## Issue Description

**Original Problem:**
- IT Department Admin was seeing 5 submissions from Administration department on the schedule calendar
- Calendar was showing ALL department submissions instead of just their own

**Root Cause:**
The application was filtering by **requestor's department** instead of **form's department**. This is incorrect because:
- Forms belong to departments (forms table has `department_id` column)
- Department admins manage forms that belong to their department
- Requestors from any department can submit forms owned by other departments
- Example: CRSRF form belongs to Administration (dept 12), but requestors from IT department were submitting it

## Solution

Changed filtering logic from **requestor-based** to **form-based**:
- Department admins should see submissions for forms that **belong to their department**
- This aligns with the business logic: departments own and manage their forms

## Files Modified

### 1. **app/Models/ScheduleModel.php** - `getDepartmentSchedules()`

**Changed:**
```php
// OLD: Filter by requestor's department
->where('u.department_id', $departmentId)

// NEW: Filter by form's department
->where('f.department_id', $departmentId)
```

**Updated SELECT to include:**
```php
f.department_id as form_department_id
```

**Impact:** Department admins now only see schedules for forms owned by their department.

---

### 2. **app/Controllers/Schedule.php** - `getDepartmentSubmissionsWithoutSchedules()`

**Changed:**
```php
// OLD: Filter by requestor's department  
->where('u.department_id', $departmentId)

// NEW: Filter by form's department
->where('f.department_id', $departmentId)
```

**Updated SELECT to include:**
```php
f.department_id as form_department_id,
u.department_id as requestor_department_id
```

**Impact:** Submissions without schedules now also filtered by form ownership.

---

### 3. **app/Controllers/Schedule.php** - Safeguard Filter (lines 617-639)

**Changed:**
```php
// OLD: Check requestor_department_id
if (isset($schedule['requestor_department_id'])) {
    return $schedule['requestor_department_id'] == $userDepartmentId;
}

// NEW: Check form_department_id (primary), requestor as fallback
if (isset($schedule['form_department_id'])) {
    return $schedule['form_department_id'] == $userDepartmentId;
}
if (isset($schedule['requestor_department_id'])) {
    return $schedule['requestor_department_id'] == $userDepartmentId;
}
```

**Impact:** Safeguard filter now uses correct department field.

---

### 4. **app/Models/NotificationModel.php** - `getUserNotifications()`

**Changed:**
```php
// OLD: Join users table and filter by requestor's department
->join('users u', 'u.id = fs.submitted_by', 'left')
->orWhere('u.department_id', $userDepartment)

// NEW: Join forms table and filter by form's department
->join('forms f', 'f.id = fs.form_id', 'left')
->orWhere('f.department_id', $userDepartment)
```

**Impact:** Notifications now filtered by form ownership, not requestor.

---

### 5. **app/Models/NotificationModel.php** - `getUnreadCount()`

**Changed:**
```php
// OLD: Join users table and filter by requestor's department
->join('users u', 'u.id = fs.submitted_by', 'left')
->orWhere('u.department_id', $userDepartment)

// NEW: Join forms table and filter by form's department
->join('forms f', 'f.id = fs.form_id', 'left')
->orWhere('f.department_id', $userDepartment)
```

**Impact:** Unread notification count now based on form ownership.

---

## Testing

### Test Query Results

**IT Department Admin (Dept ID: 22)**

**Before Fix (filter by requestor dept):**
- Would see CRSRF submissions if IT users submitted them
- Was seeing 5 submissions from Administration

**After Fix (filter by form dept):**
- Only sees forms owned by IT department
- FORM2123 is the only form owned by IT dept (0 submissions currently)
- Result: 0 schedules shown (CORRECT)

### Verification Script

Created `tools/test_department_filtering_fix.php` to compare old vs new logic:

```bash
php tools/test_department_filtering_fix.php
```

**Expected Output:**
- Old Query: Shows submissions from IT requestors
- New Query: Shows submissions for IT-owned forms
- IT Department only owns FORM2123 (0 submissions)
- Result: 0 schedules (correct)

---

## Business Logic

### Form Ownership Model

```
Department → owns → Forms
Forms → has many → Submissions
Submissions → can have → Schedules
```

**Key Principle:**
- **Forms belong to departments** (forms.department_id)
- Department admins **manage their department's forms**
- Any user can submit any form (cross-department submissions are allowed)
- Department admins see **all submissions for their forms**, regardless of who submitted them

### Example

**CRSRF Form:**
- Code: CRSRF
- Description: Computer Repair Service Request Forms
- **Owned by:** Administration Department (ID: 12)

**Submissions:**
- Requestor from Administration (dept 12) submits CRSRF → Administration admin sees it ✓
- Requestor from IT (dept 22) submits CRSRF → Administration admin sees it ✓
- IT admin does NOT see CRSRF submissions (not their form) ✓

**FORM2123:**
- Code: FORM2123
- Description: Test
- **Owned by:** IT Department (ID: 22)

**Submissions:**
- Any user submits FORM2123 → IT admin sees it ✓
- Administration admin does NOT see FORM2123 submissions (not their form) ✓

---

## Impact Analysis

### What Changed for Department Admins

**Calendar View:**
- Previously: Saw schedules for submissions made by users in their department
- Now: See schedules for submissions to forms owned by their department

**Notifications:**
- Previously: Saw notifications for submissions from users in their department
- Now: See notifications for submissions to forms owned by their department

### Breaking Changes

⚠️ **Potential Impact:** If department admins were relying on seeing submissions made by their department's users (regardless of form), this behavior has changed.

**Rationale:** The form-based model is more correct because:
1. Forms have a `department_id` column (indicating ownership)
2. Department admins approve/manage specific forms
3. The signatory system already allows cross-department approvals
4. Business logic: departments manage their forms, not their users' submissions to other forms

---

## Related Documentation

- See `DEPARTMENT_ADMIN_FIXES_NOV_23_2025.md` - Initial notification fix attempt
- See `CALENDAR_FILTERING_FIX_NOV_23_2025.md` - Calendar fallback fix
- See `URGENT_CALENDAR_FIX_NOV_23_2025.md` - Safeguard filter (now updated with correct logic)

---

## Diagnostic Tools Created

1. **tools/analyze_department_issue.php** - Shows form dept vs requestor dept
2. **tools/verify_department_ownership.php** - Confirms form ownership
3. **tools/test_department_filtering_fix.php** - Compares old vs new filtering logic

---

## Status

✅ **FIXED AND TESTED**
✅ **BUSINESS LOGIC ALIGNED**
✅ **NO DATABASE CHANGES REQUIRED** (forms.department_id column already exists)

---

## Recommendations

### 1. Test with Real Users
- Have department admins verify they see correct submissions
- Check that cross-department form submissions work correctly

### 2. Clear Cache
- Users may need to clear browser cache
- Consider clearing server-side session cache if applicable

### 3. Monitor Logs
- Watch for "getDepartmentSchedules" log entries
- Check for safeguard filter activations (should be 0 now)

### 4. User Communication
If needed, inform department admins:
> "You now see submissions for forms that belong to your department, regardless of who submitted them. This ensures you manage all submissions for your department's forms."

---

**Priority:** HIGH  
**Type:** Bug Fix - Business Logic Correction  
**Tested:** Yes  
**Database Changes:** None required
