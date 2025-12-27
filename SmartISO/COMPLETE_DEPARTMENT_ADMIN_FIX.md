# Complete Department Admin Filtering Fix

## Date: November 23, 2025

## Summary
Fixed department admin filtering across **3 pages** to use **form ownership** instead of **requestor's department**.

---

## The Core Issue

**Business Logic:** Forms belong to departments. Department admins manage their department's forms and should see all submissions to those forms, regardless of who submitted them.

**Bug:** The application was filtering by **requestor's department** instead of **form's department**, causing incorrect visibility.

---

## Pages Fixed

### 1. ✅ Schedule Calendar (`/schedule/calendar`)
**File:** `app/Controllers/Schedule.php` - `calendar()` method

**Changes:**
- ScheduleModel.php: `getDepartmentSchedules()` now filters by `forms.department_id`
- Schedule.php: `getDepartmentSubmissionsWithoutSchedules()` filters by `forms.department_id`
- Added safeguard filter checking `form_department_id`
- Excluded department admins from fallback logic

---

### 2. ✅ Schedule List (`/schedule`)
**File:** `app/Controllers/Schedule.php` - `index()` method

**Changes:**
- Fixed: `$isDepartmentAdmin = ($userType === 'department_admin')` (was checking non-existent `is_department_admin` session var)
- Uses `department_id` from session (not `scoped_department_id`)
- Added safeguard filter
- Excluded department admins from fallback logic
- Added detailed logging

---

### 3. ✅ Department Submissions (`/forms/department-submissions`)
**File:** `app/Controllers/Forms.php` - `departmentSubmissions()` method

**Changes:**
```php
// OLD: Filter by requestor's department
$deptUserIds = $this->userModel->where('department_id', $userDeptId)->findColumn('id');
->whereIn('form_submissions.submitted_by', $deptUserIds)

// NEW: Filter by form's department
->where('forms.department_id', $userDeptId)
```

**Statistics query also updated:**
```php
// OLD
$builder->whereIn('submitted_by', $deptUserIds);

// NEW
$builder->join('forms', 'forms.id = form_submissions.form_id')
       ->where('forms.department_id', $userDeptId);
```

**Removed:** Office filter (not relevant for form ownership)

---

## Database Schema

```
forms table:
  - department_id  ← Forms BELONG to departments

form_submissions table:
  - form_id        ← References forms table
  - submitted_by   ← User who submitted (can be from any department)

users table:
  - department_id  ← User's department (NOT used for filtering dept admin views)
```

---

## Example: CRSRF Form

**Form Details:**
- Code: CRSRF
- Name: Computer Repair Service Request Forms
- **Owner:** Administration Department (ID: 12)
- Submissions: 18 total

**Visibility:**
- ✅ Administration dept admin sees all 18 CRSRF submissions
- ❌ IT dept admin does NOT see CRSRF submissions
- ✅ IT dept admin only sees submissions for FORM2123 (owned by IT)

---

## Testing Results

### IT Department Admin (dept_admin_it)

**Before Fix:**
- Schedule Calendar: Showed 5 CRSRF submissions ❌
- Schedule List: Showed 18 total submissions ❌
- Department Submissions: Would show submissions from IT users ❌

**After Fix:**
- Schedule Calendar: Shows 0 schedules ✅
- Schedule List: Shows 0 schedules ✅
- Department Submissions: Shows 0 submissions ✅

*All correct because IT dept owns FORM2123 which has 0 submissions*

---

## Files Modified

1. **app/Models/ScheduleModel.php**
   - `getDepartmentSchedules()`: Changed WHERE clause from `u.department_id` to `f.department_id`

2. **app/Models/NotificationModel.php**
   - `getUserNotifications()`: Changed join from users to forms, filter by `f.department_id`
   - `getUnreadCount()`: Changed join from users to forms, filter by `f.department_id`

3. **app/Controllers/Schedule.php**
   - `calendar()`: Added detailed logging, updated safeguard filter to check `form_department_id`
   - `index()`: Fixed `$isDepartmentAdmin` check, added logging, added safeguard filter
   - `getDepartmentSubmissionsWithoutSchedules()`: Changed WHERE clause to filter by `f.department_id`

4. **app/Controllers/Forms.php**
   - `departmentSubmissions()`: Removed user-based filtering, changed to form-based filtering
   - Updated statistics queries to filter by form's department
   - Removed office filter (not relevant)

---

## Diagnostic Tools Created

1. `tools/analyze_department_issue.php` - Shows form vs requestor dept mismatch
2. `tools/verify_department_ownership.php` - Confirms form ownership
3. `tools/test_department_filtering_fix.php` - Compares old vs new logic
4. `tools/final_department_filtering_test.php` - Comprehensive test suite
5. `tools/test_department_submissions_fix.php` - Tests department submissions page
6. `tools/debug_session_issue.php` - Checks session variables

---

## Logs to Monitor

After accessing pages as dept_admin_it, check logs for:

```
Schedule Index - isDepartmentAdmin: TRUE
Department Admin Index START - User ID: 9 | Department: 22
getDepartmentSchedules returned: 0 schedule(s)
Department Admin Index END - Total schedules: 0
```

If you see "ELSE BLOCK TRIGGERED" or "SAFEGUARD FILTER ACTIVATED", there's still an issue.

---

## User Testing Checklist

1. Clear browser cache
2. Log in as `dept_admin_it`
3. Check these pages:
   - ✅ `/schedule` - Should show 0 schedules
   - ✅ `/schedule/calendar` - Should show empty calendar
   - ✅ `/forms/department-submissions` - Should show 0 submissions
4. Verify NO CRSRF items appear anywhere

---

## Documentation Files

- `DEPARTMENT_FILTERING_FORM_OWNERSHIP_FIX.md` - Detailed technical documentation
- `SCHEDULE_INDEX_FIX.md` - Schedule list page fix
- `QUICK_FIX_SUMMARY.md` - Quick reference
- `BEFORE_AFTER_COMPARISON.md` - Visual comparison
- `THIS_FILE.md` - Complete summary

---

## Status

✅ **ALL 3 PAGES FIXED**  
✅ **TESTED AND VERIFIED**  
✅ **NO DATABASE CHANGES REQUIRED**  
✅ **CONSISTENT LOGIC ACROSS ALL VIEWS**

---

**Priority:** HIGH  
**Type:** Bug Fix - Business Logic Correction  
**Impact:** Department admins now see correct submissions based on form ownership
