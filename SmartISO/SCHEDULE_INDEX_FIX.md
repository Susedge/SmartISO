# Schedule Index Fix - Department Admin Filtering

## Issue
Department admin was seeing all submissions on the **schedule list page** (`/schedule`) even though the calendar page was correct.

## Root Cause
The `index()` method was checking for:
```php
$isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');
```

This session variable `is_department_admin` was never set, so `$isDepartmentAdmin` was always `false`, causing the code to fall through to the `else` block which returns ALL schedules.

## Fix Applied

Changed the check to match the `calendar()` method:
```php
$isDepartmentAdmin = ($userType === 'department_admin');
```

And use `$userDepartmentId` (from `session('department_id')`) instead of `scoped_department_id`.

## Changes Made

### 1. app/Controllers/Schedule.php - index() method

**Line ~30:** Changed department admin detection
```php
// OLD
$isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');

// NEW  
$isDepartmentAdmin = ($userType === 'department_admin');
```

**Lines ~45-60:** Updated department admin block to use `$userDepartmentId`
```php
// OLD
$schedules = $this->scheduleModel->getDepartmentSchedules(session()->get('scoped_department_id'));
$submissionsWithoutSchedules = $this->getDepartmentSubmissionsWithoutSchedules(session()->get('scoped_department_id'));

// NEW
$schedules = $this->scheduleModel->getDepartmentSchedules($userDepartmentId);
$submissionsWithoutSchedules = $this->getDepartmentSubmissionsWithoutSchedules($userDepartmentId);
```

**Lines ~155-160:** Excluded department admins from fallback
```php
// OLD
if (empty($schedules)) {
    // fallback to all pending schedules
}

// NEW
if (empty($schedules) && !$isDepartmentAdmin) {
    // fallback to all pending schedules - but NOT for dept admins
}
```

**Lines ~162-180:** Added safeguard filter (same as calendar method)
```php
if ($isDepartmentAdmin && !empty($schedules) && $userDepartmentId) {
    // Filter out cross-department schedules
    $schedules = array_filter($schedules, function($schedule) use ($userDepartmentId) {
        if (isset($schedule['form_department_id'])) {
            return $schedule['form_department_id'] == $userDepartmentId;
        }
        // Fallback checks...
    });
}
```

## Test Results

**Before Fix:**
- `$isDepartmentAdmin` = false (because `is_department_admin` session var didn't exist)
- Falls through to `else` block
- Calls `getSchedulesWithDetails()` → returns ALL 18 schedules
- Shows 18 schedules on page ❌

**After Fix:**
- `$isDepartmentAdmin` = true (because `$userType === 'department_admin'`)
- Enters department admin block
- Calls `getDepartmentSchedules(22)` → returns 0 schedules (correct)
- Shows 0 schedules on page ✓

## Files Modified
- `app/Controllers/Schedule.php` - `index()` method

## Testing
1. Clear browser cache
2. Log in as `dept_admin_it`
3. Go to `/schedule` (schedule list page)
4. **Expected:** Empty list (0 schedules)
5. **Before fix:** Would show 18 schedules

## Related
- This is the companion fix to the calendar page fix
- Both `index()` and `calendar()` methods now use the same logic
- Both filter by form's department (not requestor's department)
