# Pending Approval & Department Submission Fixes

## Issues Fixed

### 1. All Approvers Redirected to Dashboard on Pending Approval
**Root Cause**: The `admin_can_approve` configuration was missing from the database, causing admin and superuser accounts to be denied access.

**Fix Applied**:
- Changed default behavior: If configuration is missing, default to `true` (allow access)
- Added error handling in `canUserApprove()` method
- Improved error messages to distinguish between different user types
- Added detailed logging to track approval access attempts

**Files Modified**:
- `app/Controllers/Forms.php` - `canUserApprove()` method (lines 45-68)
- `app/Controllers/Forms.php` - `pendingApproval()` method (lines 476-491)

### 2. Department Admin Cannot View Department Submissions
**Root Cause**: Access control is working correctly, but better logging and error messages were needed to diagnose session issues.

**Fix Applied**:
- Added detailed logging to track access attempts
- Improved error message to be more specific
- Added debugging information for user_type and department_id

**Files Modified**:
- `app/Controllers/Forms.php` - `departmentSubmissions()` method (lines 2263-2273)

---

## SQL Script to Run

**IMPORTANT**: Before testing, run the SQL script to ensure the `admin_can_approve` configuration exists:

```sql
-- Location: tools/fix_approval_issues.sql
-- This script will:
-- 1. Check if admin_can_approve exists
-- 2. Create it if missing (enabled by default)
-- 3. Display current user configurations
```

To run the script:
1. Start your MySQL server (XAMPP Control Panel → Start MySQL)
2. Open phpMyAdmin or MySQL command line
3. Select the `smartiso` database
4. Run the SQL script from `tools/fix_approval_issues.sql`

**OR** use command line:
```powershell
mysql -u root -p smartiso < tools/fix_approval_issues.sql
```

---

## How to Test

### Test 1: Approving Authority Access
1. Log in as a user with `user_type = 'approving_authority'`
2. Click on "Pending Approval" link
3. **Expected**: Should see pending approvals list
4. **If fails**: Check logs at `writable/logs/` for "Pending Approval Access Denied"

### Test 2: Department Admin Access
1. Log in as a user with `user_type = 'department_admin'`
2. Click on "Pending Approval" link
3. **Expected**: Should see pending approvals list
4. Click on "Department Submissions" link
5. **Expected**: Should see submissions from their department
6. **If fails**: Check logs for "departmentSubmissions access denied"

### Test 3: Super Admin / Admin Access
1. Log in as a user with `user_type = 'superuser'` or `'admin'`
2. Click on "Pending Approval" link
3. **Expected**: Should see pending approvals list
4. **If fails**: 
   - Check if `admin_can_approve` configuration exists in database
   - Run the SQL script to create it
   - Check logs for configuration errors

---

## Logging Information

All access attempts are now logged. To view logs:

```powershell
cd C:\xampp\htdocs\SmartISO-5\SmartISO
Get-Content writable/logs/log-*.php | Select-String -Pattern "Pending Approval|departmentSubmissions|canUserApprove" -Context 2
```

### Log Messages to Look For:

**Success Messages**:
```
User approved for pending approvals - User Type: approving_authority
departmentSubmissions access attempt - User Type: department_admin, Department ID: 5
```

**Error Messages**:
```
Pending Approval Access Denied - User Type: admin
departmentSubmissions access denied - User Type: requestor, Department ID: NULL
Error checking admin_can_approve config: [error details]
```

---

## Configuration Check

To manually check if the configuration exists:

```sql
SELECT * FROM configurations WHERE config_key = 'admin_can_approve';
```

**Expected Result**:
- `config_key`: admin_can_approve
- `config_value`: 1
- `config_type`: boolean
- `config_description`: Allow admin and superuser to approve forms

**If Not Found**: Run the SQL script in `tools/fix_approval_issues.sql`

---

## Manual Configuration (Alternative)

If the SQL script doesn't work, you can manually insert the configuration:

```sql
INSERT INTO configurations (config_key, config_value, config_type, config_description, created_at, updated_at)
VALUES ('admin_can_approve', '1', 'boolean', 'Allow admin and superuser to approve forms', NOW(), NOW());
```

---

## Department Admin Session Requirements

For department admin to access department submissions, the session must have:
- `user_type` = 'department_admin'
- `department_id` = (valid department ID)

To check current session values, add this to any controller:
```php
log_message('info', 'Session data: ' . json_encode(session()->get()));
```

---

## Common Issues & Solutions

### Issue: "Access denied" for department admin
**Solution**: 
1. Verify user has `user_type = 'department_admin'` in database
2. Verify user has a valid `department_id` assigned
3. Check session is properly set during login
4. Look for log message: "departmentSubmissions access denied"

### Issue: Admin/superuser still redirected
**Solution**:
1. Run the SQL script to create `admin_can_approve` configuration
2. Verify configuration value is '1' (enabled)
3. Clear browser cache and cookies
4. Log out and log back in
5. Check logs for "Admin approval check" messages

### Issue: Approving authority redirected
**Solution**:
1. Verify user has `user_type = 'approving_authority'` in database
2. This should NEVER happen now - the code explicitly allows this type
3. Check logs immediately for error messages
4. May indicate database connection or session issues

---

## Summary of Changes

### Code Changes:
1. **canUserApprove()**: Default to true for admins, added error handling, better logging
2. **pendingApproval()**: Improved error messages, specific feedback per user type
3. **departmentSubmissions()**: Added detailed logging and better error message

### Database Changes:
1. Created `admin_can_approve` configuration (via SQL script)

### New Files:
1. `tools/fix_approval_issues.sql` - SQL script to fix configuration
2. `tools/check_admin_can_approve.php` - PHP script to check configuration (requires MySQL running)

---

## Rollback (If Needed)

If these changes cause issues, you can:

1. Disable admin approval:
```sql
UPDATE configurations SET config_value = '0' WHERE config_key = 'admin_can_approve';
```

2. Remove the configuration:
```sql
DELETE FROM configurations WHERE config_key = 'admin_can_approve';
```

Note: Approving authority and department admin access will ALWAYS work regardless of this configuration.
