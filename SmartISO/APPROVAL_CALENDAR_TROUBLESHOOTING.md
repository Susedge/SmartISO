# Approval Calendar Troubleshooting Guide

## Issue
Newly approved submissions do not appear on the calendar, even though the fix scripts (`fix_schedule_approval_dates.php` and `fix_missing_assigned_staff.php`) successfully fixed historical records.

## What Was Checked

### Approval Date Logic ✓
The code **correctly** sets the approval date when approving submissions:
- `approved_at` is set to `date('Y-m-d H:i:s')` (current timestamp)
- `scheduled_date` is set to `date('Y-m-d')` (current date)
- This happens in `app/Controllers/Forms.php` in the `submitApproval()` method

### Schedule Creation Logic ✓
When a submission is approved with a service staff assigned:
1. The submission status is updated to `pending_service`
2. `approved_at` timestamp is recorded
3. A schedule entry is **automatically created** with:
   - `scheduled_date` = current date (approval date)
   - `assigned_staff_id` = selected service staff
   - `status` = 'pending'
   - ETA days and estimated date based on priority

## Enhanced Logging Added

I've added comprehensive logging to help diagnose the issue. The logs will now show:

### During Approval (`submitApproval()` method)
```
[APPROVAL] Updating submission {id} with data: {...}
[APPROVAL] Submission {id} approved and updated. Status: ..., Service Staff ID: ..., Approved At: ...
[APPROVAL] About to insert schedule for submission {id} with data: {...}
[APPROVAL] Successfully created schedule ID {id} for submission {id}
[APPROVAL] Schedule data: scheduled_date=..., status=..., assigned_staff=...
```

Or if the schedule already exists:
```
[APPROVAL] Schedule already exists for submission {id} (Schedule ID: {id}), updating...
[APPROVAL] Successfully updated schedule {id} with approval date {date}
[APPROVAL] Updated schedule data: {...}
```

## Testing Steps

### 1. Check Application Logs
After approving a new submission, check the CodeIgniter logs:
- Location: `writable/logs/log-YYYY-MM-DD.php`
- Look for `[APPROVAL]` prefixed messages
- Verify:
  - ✓ Submission update succeeded
  - ✓ `approved_at` is set to current timestamp
  - ✓ Schedule insert/update succeeded
  - ✓ `scheduled_date` matches today's date

### 2. Verify in Database
Run this query immediately after approval:
```sql
SELECT 
    fs.id as submission_id,
    fs.status,
    fs.approved_at,
    DATE(fs.approved_at) as approval_date,
    fs.service_staff_id,
    s.id as schedule_id,
    s.scheduled_date,
    s.assigned_staff_id,
    s.status as schedule_status
FROM form_submissions fs
LEFT JOIN schedules s ON s.submission_id = fs.id
WHERE fs.id = YOUR_SUBMISSION_ID;
```

Expected results:
- `fs.status` should be `pending_service`
- `fs.approved_at` should be current timestamp
- `s.id` should NOT be NULL (schedule exists)
- `s.scheduled_date` should match DATE(fs.approved_at)
- `s.assigned_staff_id` should match the selected service staff

### 3. Check Calendar Query
For service staff calendar view, verify submissions appear by running:
```sql
-- This mimics what getStaffSchedules() does
SELECT s.*, fs.form_id, fs.panel_name, fs.status as submission_status,
       f.code as form_code, f.description as form_description,
       u.full_name as requestor_name
FROM schedules s
LEFT JOIN form_submissions fs ON fs.id = s.submission_id
LEFT JOIN forms f ON f.id = fs.form_id
LEFT JOIN users u ON u.id = fs.submitted_by
WHERE s.assigned_staff_id = YOUR_STAFF_ID
ORDER BY s.scheduled_date ASC;
```

### 4. Check for "Virtual" Schedules
If a schedule isn't in the `schedules` table, the calendar tries to show it as a "virtual" schedule from `form_submissions`:
```sql
SELECT fs.id, fs.status, fs.created_at, fs.service_staff_id
FROM form_submissions fs
WHERE fs.service_staff_id = YOUR_STAFF_ID
  AND fs.status IN ('approved', 'pending_service', 'completed');
```

## Possible Root Causes

### 1. Schedule Insert Failure
**Symptoms**: Logs show `[APPROVAL] Failed to create schedule` or exception messages
**Causes**:
- Database constraint violation
- Missing required fields
- ScheduleModel validation rules blocking insert

**Solution**: Check the error message in logs and database error log

### 2. Calendar Cache Issue
**Symptoms**: Schedule exists in database but doesn't appear on calendar
**Causes**:
- Browser cache showing old calendar
- Calendar JavaScript not refreshing

**Solution**: 
- Hard refresh browser (Ctrl+F5)
- Clear browser cache
- Check browser console for JavaScript errors

### 3. Permission/Filter Issue
**Symptoms**: Schedule exists but filtered out by query
**Causes**:
- Service staff not properly assigned in database
- Department/office filtering excluding the record

**Solution**: 
- Verify `assigned_staff_id` matches the logged-in service staff
- Check if any department filters are active

### Fix added (Nov 24, 2025)

We discovered a case where a schedule row existed for a submission but its `assigned_staff_id` was missing (or different). The calendar previously excluded those submissions because the "virtual submission" query filtered out records when a schedule row existed. We removed that exclusion so all submissions assigned to a service staff are returned for calendar merging (the merge logic keeps real schedule rows when present). This ensures that:

- Submissions assigned to a staff member will appear in the staff calendar even if the schedule row is incorrectly assigned
- The calendar still prefers real schedule rows when both exist (no duplicate events)

If you still see missing events after this change, check the logs for schedule creation errors and verify the `schedules.assigned_staff_id` values.

### 4. Date/Time Zone Issue
**Symptoms**: Schedule date is off by one day
**Causes**:
- PHP timezone doesn't match database timezone
- `date()` function using wrong timezone

**Solution**: 
- Check `date_default_timezone_get()` in PHP
- Check database timezone settings
- Verify `approved_at` and `scheduled_date` values

## Diagnostic Tool

Use the new diagnostic script to check recent approvals:
```bash
cd SmartISO/tools
php check_recent_approvals.php
```

This will show:
- All approvals in the last 24 hours
- Whether each has a schedule entry
- If scheduled_date matches approval_date
- Any approved submissions missing schedules

## Expected Behavior

When an approver approves a submission:
1. Submission status → `pending_service`
2. `approved_at` → current timestamp
3. Schedule entry created with:
   - `scheduled_date` = current date
   - `assigned_staff_id` = selected service staff
   - `status` = 'pending'
4. Calendar immediately shows the new schedule (may need refresh)

## If Problem Persists

If the logs show successful schedule creation but the calendar still doesn't show it:

1. Check the calendar view code: `app/Controllers/Schedule.php` → `index()` method
2. Verify the SQL queries being executed (check debug logs)
3. Test with a different service staff user
4. Check if calendar is filtering by date range (current day might be outside range)
5. Verify FullCalendar JavaScript is properly configured

## Files Modified
- `app/Controllers/Forms.php` - Added detailed `[APPROVAL]` logging
- `tools/check_recent_approvals.php` - New diagnostic tool

## Next Steps

After approving a test submission:
1. Check the logs for `[APPROVAL]` messages
2. Run the SQL verification queries
3. Check if the schedule appears in database
4. If yes → calendar display issue
5. If no → schedule creation issue (check error logs)

## Recent code changes

- Added model-level schedule creation: `FormSubmissionModel::createScheduleOnApproval` is now called whenever a submission is approved (covering admin, bulk, and UI approval flows). This guarantees a schedule row is created or updated when a submission becomes approved/pending_service.
- Updated admin bulk approval flows to call the model's approve helper so schedules and notifications are created consistently.

### Department admin notifications fix (Nov 24, 2025)

- Some department admins were not receiving notifications for service assignments. The notification system now sends assignment notifications to:
  - the assigned service staff (existing behavior)
  - the submission approver (when present)
  - department admins for the form's department
  - global admins (audit)

This reduces cases where department admins saw no notifications for events relevant to their department.
