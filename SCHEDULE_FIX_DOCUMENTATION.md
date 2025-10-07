# Schedule View Fix - All Submissions Visible to Admin

## Issue Description
Admin users were unable to see all submissions in the schedule view. Some submissions created by requestors were not appearing in the admin's schedule calendar, even though other submissions were visible.

## Root Cause Analysis

The issue was caused by the system's schedule creation workflow:

1. **Auto-Schedule Configuration**: The system has two configuration flags:
   - `autoCreateScheduleOnSubmit` = **false** (disabled by default)
   - `autoCreateScheduleOnApproval` = **true** (enabled by default)

2. **The Problem**: 
   - When a requestor creates a submission, NO schedule entry is automatically created (because `autoCreateScheduleOnSubmit` is false)
   - The Schedule controller only displays items from the `schedules` table
   - Therefore, submissions without schedule entries were invisible in the schedule view

3. **Why Some Worked**:
   - Submissions that went through the approval process had schedules auto-created
   - Manually created schedule entries would show up
   - But fresh submissions from requestors had no schedule entry, so they were invisible to admins

## Solution Implemented

Modified the Schedule controller to include "virtual" schedule entries for submissions that don't have actual schedule records yet. This ensures admins can see ALL submissions, regardless of whether a schedule entry exists.

### Changes Made

**File**: `app/Controllers/Schedule.php`

#### 1. Modified `index()` Method
For admin/superuser users, now fetches both:
- Real schedules from the schedules table
- Virtual schedules for submissions without schedule entries

```php
// Admin and superuser can see all schedules AND submissions without schedules
if (in_array($userType, ['admin', 'superuser'])) {
    $schedules = $this->scheduleModel->getSchedulesWithDetails();
    
    // Also get submissions that don't have schedule entries yet
    $submissionsWithoutSchedules = $this->getSubmissionsWithoutSchedules();
    // Merge them into the schedules array
    $schedules = array_merge($schedules, $submissionsWithoutSchedules);
}
```

#### 2. Modified `calendar()` Method
Applied the same logic to the calendar view to ensure consistency.

#### 3. Added New Helper Method: `getSubmissionsWithoutSchedules()`
This private method:
- Queries the `form_submissions` table for submissions without schedule entries
- Uses a SQL NOT EXISTS clause to find submissions lacking schedule records
- Only includes active submissions (submitted, approved, pending_service)
- Formats them as "virtual" schedule entries with default values
- Prefixes IDs with 'sub-' to distinguish from real schedules

**Key Features**:
- Uses the submission's created date as the scheduled date
- Sets default time to 09:00:00
- Marks status as 'pending'
- Notes indicate "Pending schedule assignment"
- Preserves submission details (form code, requestor name, etc.)

## Benefits

1. **Complete Visibility**: Admins now see ALL submissions in the schedule view
2. **No Data Loss**: Submissions are visible even before formal scheduling
3. **Better Workflow**: Admins can identify submissions that need schedule assignment
4. **Non-Breaking**: Other user types (requestor, service_staff, approving_authority) maintain their existing filtered views
5. **Backward Compatible**: Existing schedule entries continue to work normally

## Testing Recommendations

1. **Create Test Submissions**:
   - Log in as a requestor
   - Create multiple new submissions
   - Don't approve or schedule them yet

2. **Verify Admin View**:
   - Log in as admin
   - Navigate to Schedule Calendar or Schedule Index
   - Confirm ALL submissions appear (both scheduled and unscheduled)

3. **Check Virtual Entries**:
   - Unscheduled submissions should show:
     - Status: "pending"
     - Notes: "Pending schedule assignment"
     - Date: Submission creation date
     - Time: 09:00:00 (default)

4. **Verify Other Users**:
   - Requestors should only see their own submissions
   - Service staff should only see assigned schedules
   - Approving authorities should only see submissions they approved

## Configuration Notes

The fix works regardless of the `autoCreateScheduleOnSubmit` setting. However, you can enable automatic schedule creation by:

1. **Enable in Config** (app/Config/App.php):
   ```php
   public bool $autoCreateScheduleOnSubmit = true;
   ```

2. **Or Enable via Database** (configuration table):
   ```sql
   INSERT INTO configuration (config_key, config_value, description) 
   VALUES ('auto_create_schedule_on_submit', '1', 'Auto-create schedule when submission is created');
   ```

## Related Files

- `app/Controllers/Schedule.php` - Main changes
- `app/Models/ScheduleModel.php` - No changes needed
- `app/Config/App.php` - Configuration reference
- `app/Controllers/Forms.php` - Auto-schedule creation logic

## Date Applied
October 7, 2025
