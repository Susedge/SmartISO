# Department Admin Fixes - Testing Guide

## Prerequisites

1. Have at least one department admin user
2. Have submissions from multiple departments
3. Have schedules for submissions from multiple departments

## Test 1: Notification Filtering

### Expected Behavior
Department admins should only see notifications for submissions from their own department.

### Steps
1. Identify a department admin:
   ```sql
   SELECT u.id, u.full_name, u.department_id, d.description as dept_name
   FROM users u
   LEFT JOIN departments d ON d.id = u.department_id
   WHERE u.user_type = 'department_admin' AND u.active = 1
   LIMIT 1;
   ```

2. Run diagnostic:
   ```bash
   php tools/test_notification_filter_query.php
   ```

3. Check output for:
   - ✅ "Filter blocked X cross-department notification(s)"
   - ✅ Filtered count should be 0 or only from same department

4. Log in as department admin in browser
5. Navigate to `/notifications`
6. Verify only submissions from your department appear

### Log Verification
Check `writable/logs/log-YYYY-MM-DD.php` for:
```
Filtering notifications for department_admin (User ID: X, Dept: Y)
```

---

## Test 2: Calendar Filtering

### Expected Behavior
Department admins should only see schedules where the requestor is from their department.

### Steps
1. Run diagnostic:
   ```bash
   php tools/check_calendar_issue.php
   ```

2. Check output shows:
   - Department admin's department ID
   - Number of schedules from that department
   - ✅ No schedules from other departments visible

3. Log in as department admin in browser
4. Navigate to `/schedule/calendar`
5. Click on any event to see details
6. Verify requestor is from your department

### Log Verification
Check `writable/logs/log-YYYY-MM-DD.php` for:
```
getDepartmentSchedules - Department ID: X | Found N schedule(s)
```

---

## Test 3: Cross-Department Scenario

### Setup
Create test data where department admin would see cross-department items:

```sql
-- Check if IT Dept Admin (dept 22) has notifications from Admin dept (12)
SELECT COUNT(*) as cross_dept_count
FROM notifications n
LEFT JOIN form_submissions fs ON fs.id = n.submission_id
LEFT JOIN users submitter ON submitter.id = fs.submitted_by
WHERE n.user_id = 9  -- IT Dept Admin
  AND submitter.department_id != 22  -- Not from IT dept
  AND submitter.department_id = 12;  -- From Admin dept
```

### Expected Result
- **Before Fix:** Count > 0 (cross-department notifications visible)
- **After Fix:** Count = 0 (filtered out by getUserNotifications)

---

## Test 4: Form Signatories

### Check Current Signatories
```sql
SELECT f.code, f.description as form_name,
       u.full_name as signatory_name,
       u.user_type,
       u.department_id as signatory_dept,
       f.department_id as form_dept,
       CASE 
           WHEN u.department_id = f.department_id THEN 'Same Dept'
           ELSE 'CROSS DEPT'
       END as routing_type
FROM form_signatories fs
LEFT JOIN forms f ON f.id = fs.form_id
LEFT JOIN users u ON u.id = fs.user_id
WHERE u.user_type = 'department_admin'
ORDER BY routing_type DESC, f.code;
```

### Interpretation
- **Same Dept:** Normal department-based routing
- **CROSS DEPT:** Cross-department signatory assignment
  - These will still receive notifications (in database)
  - But won't see them in UI (filtered by getUserNotifications)

### To Remove Cross-Department Signatories (Optional)
```sql
-- BACKUP FIRST!
SELECT * FROM form_signatories INTO OUTFILE '/tmp/form_signatories_backup.csv';

-- Remove cross-department assignments
DELETE fs FROM form_signatories fs
LEFT JOIN forms f ON f.id = fs.form_id
LEFT JOIN users u ON u.id = fs.user_id
WHERE f.department_id IS NOT NULL 
  AND u.department_id IS NOT NULL
  AND f.department_id != u.department_id
  AND u.user_type = 'department_admin';
```

---

## Test 5: Manual Browser Testing

### As Department Admin (e.g., IT Department)

1. **Submit Test Form**
   - Log in as requestor from IT department
   - Submit any form
   - Log out

2. **Check Notifications**
   - Log in as IT department admin
   - Go to `/notifications`
   - ✅ Should see the IT dept submission notification
   - ✅ Should NOT see submissions from other departments

3. **Check Calendar**
   - Go to `/schedule/calendar`
   - ✅ Should only see schedules from IT department requestors
   - ✅ Should NOT see schedules from other departments

4. **Submit from Different Department**
   - Log in as requestor from Administration department
   - Submit a form
   - Log out
   - Log in as IT department admin
   - Check notifications and calendar
   - ✅ Should NOT see the Administration submission

---

## Test 6: Multi-Department Admin Comparison

If you have admins from multiple departments:

1. Log in as Department A admin
2. Note submission IDs visible in notifications/calendar
3. Log out
4. Log in as Department B admin
5. Note submission IDs visible
6. Compare: Should be completely different sets (no overlap)

---

## Automated Test Script

Create a comprehensive test:

```php
<?php
// Run all diagnostic tests
echo "Running comprehensive department admin tests...\n\n";

echo "1. Notification Filter Test:\n";
system('php tools/test_notification_filter_query.php');

echo "\n\n2. Notification Issue Check:\n";
system('php tools/check_notification_issue.php');

echo "\n\n3. Calendar Issue Check:\n";
system('php tools/check_calendar_issue.php');

echo "\n\nAll tests complete!\n";
```

Save as `tools/test_all_dept_admin.php` and run:
```bash
php tools/test_all_dept_admin.php
```

---

## Success Criteria

✅ **Notification Filtering:**
- Department admin sees only notifications from their department
- Unread count reflects filtered notifications
- No cross-department notifications in UI

✅ **Calendar Filtering:**
- Department admin sees only schedules from their department
- Calendar events show only same-department requestors
- No cross-department schedules visible

✅ **Logging:**
- Log entries show correct filtering
- Department ID logged for verification
- Count of filtered items logged

✅ **User Experience:**
- Clean, department-focused view
- No confusion from cross-department items
- Performance maintained (efficient queries)

---

## Troubleshooting

### Issue: Department admin still sees all notifications
**Check:**
1. Is user_type exactly 'department_admin'? (case-sensitive)
2. Does user have department_id set?
3. Are logs showing "Filtering notifications for department_admin"?
4. Clear browser cache and retry

### Issue: Department admin sees no notifications at all
**Check:**
1. Are there submissions from their department?
2. Check database: `SELECT * FROM notifications WHERE user_id = X`
3. Verify department_id matches between admin and submissions
4. Check if notifications were created (check created_at timestamps)

### Issue: Calendar shows no schedules
**Check:**
1. Are there schedules where requestor.department_id matches admin's department?
2. Run: `php tools/check_calendar_issue.php` to see available schedules
3. Check logs for "getDepartmentSchedules" entries
4. Verify requestor users have department_id set

---

**Last Updated:** November 23, 2025  
**Status:** ✅ Ready for Testing
