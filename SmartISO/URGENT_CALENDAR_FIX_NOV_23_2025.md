# URGENT FIX: Department Admin Calendar Cross-Department Issue

## Issue Description

Department admin (IT Department, ID: 22) is seeing 5 submissions from Administration department (ID: 12) on the schedule calendar.

## Root Cause Analysis

Investigation revealed:
- IT Department Admin is a **signatory** on the CRSRF form
- There are exactly 5 CRSRF submissions with status='submitted' from Administration department
- These submissions have schedules created
- Despite correct database filtering in `getDepartmentSchedules()`, these 5 items are appearing on the calendar

## Findings

1. **Database queries are correct** - `getDepartmentSchedules(22)` returns 0 schedules ✓
2. **Department admin is a cross-department signatory** - on CRSRF form (Administration dept) ⚠️
3. **5 matching submissions found** - All from Administration, all with schedules, dept admin is signatory
4. **Bug location unknown** - The exact code path allowing cross-dept schedules is unclear

## Solution Implemented

### Fix 1: Enhanced Logging
Added comprehensive logging to `calendar()` method to trace execution:
- Log when department_admin code path is entered
- Log counts from `getDepartmentSchedules()`
- Log counts from `getDepartmentSubmissionsWithoutSchedules()`
- Log each individual schedule/submission
- **Location:** `app/Controllers/Schedule.php` lines 496-514

### Fix 2: Safeguard Filter
Added a secondary filter AFTER schedules are retrieved to catch any cross-department items:
- Filters schedules array by `requestor_department_id`
- Only applies to `department_admin` user type  
- Logs warning if cross-department items are removed
- **Location:** `app/Controllers/Schedule.php` lines 609-629

```php
// ADDITIONAL SAFEGUARD: For department admins, filter out any schedules from other departments
if ($userType === 'department_admin' && !empty($schedules)) {
    $userDepartmentId = session()->get('department_id');
    if ($userDepartmentId) {
        $beforeCount = count($schedules);
        $schedules = array_filter($schedules, function($schedule) use ($userDepartmentId) {
            if (isset($schedule['requestor_department_id'])) {
                return $schedule['requestor_department_id'] == $userDepartmentId;
            }
            return true;
        });
        $schedules = array_values($schedules);
        $afterCount = count($schedules);
        
        if ($beforeCount != $afterCount) {
            log_message('warning', 'Department Admin Calendar - SAFEGUARD FILTER ACTIVATED: Removed ' . ($beforeCount - $afterCount) . ' cross-department schedule(s)');
        }
    }
}
```

## Testing

### Test 1: Check Logs
After department admin accesses calendar, check logs:
```bash
type writable\logs\log-2025-11-23.php | findstr /C:"Department Admin Calendar"
```

**Expected Output:**
```
Department Admin Calendar START - User ID: 9 | User Type: department_admin | Department: 22
getDepartmentSchedules - Department ID: 22 | Found 0 schedule(s)
Department Admin Calendar - getDepartmentSchedules returned: 0 schedule(s)
Department Admin Calendar - getDepartmentSubmissionsWithoutSchedules returned: 0 submission(s)
Department Admin Calendar END - User ID: 9 | Department: 22 | Total schedules: 0
```

**If safeguard activates:**
```
Department Admin Calendar - SAFEGUARD FILTER ACTIVATED: Removed 5 cross-department schedule(s)
```

### Test 2: View Calendar
1. Log in as IT Department Admin
2. Navigate to `/schedule/calendar`
3. **Expected:** Empty calendar (0 items)
4. **Before fix:** Would see 5 submissions from Administration

### Test 3: Verify No Side Effects
1. Log in as Administration department admin
2. Check calendar shows their department's submissions
3. Verify filtering doesn't affect other departments

## Files Modified

1. **app/Controllers/Schedule.php**
   - Lines 496-514: Enhanced logging for department_admin code path
   - Lines 609-629: Safeguard filter for cross-department schedules

## Recommendations

### Immediate Action
1. Deploy this fix to prevent cross-department visibility
2. Monitor logs for safeguard activations
3. If safeguard logs appear, investigate root cause

### Long-term Solutions

**Option 1: Remove Cross-Department Signatories**
```sql
-- Remove IT Dept Admin as signatory on CRSRF form
DELETE FROM form_signatories 
WHERE form_id IN (SELECT id FROM forms WHERE code = 'CRSRF')
AND user_id = 9; -- IT Dept Admin
```

**Option 2: Departmental Form Ownership**
- Assign forms to specific departments
- Only allow signatories from the form's department
- Enforce at form configuration level

**Option 3: Separate "Approval" from "Calendar View"**
- Department admins can approve cross-department forms (if signatory)
- But calendar only shows their department's items
- This is what the current fix implements

## Related Issues

- See `DEPARTMENT_ADMIN_FIXES_NOV_23_2025.md` for notification filtering
- See `CALENDAR_FILTERING_FIX_NOV_23_2025.md` for fallback fix

Both notification and calendar now have department-based filtering for department admins.

## Status

✅ **SAFEGUARD IMPLEMENTED**  
⚠️  **ROOT CAUSE INVESTIGATION ONGOING**  
📋 **MONITORING REQUIRED**

The safeguard will prevent the issue while we determine exactly how the 5 items were getting through.

---

**Date:** November 23, 2025  
**Priority:** HIGH  
**Type:** Security/Privacy Issue
