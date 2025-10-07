# Fixes Applied - October 7, 2025

## Issue 1: Dashboard Form Status Summary Display

### Problem
The dashboard was not displaying the correct numbers for form status summaries. This was caused by reusing the same model builder instance for multiple `countAllResults()` calls, which causes the builder state to be consumed after the first query.

### Solution
Modified `app/Controllers/Dashboard.php` to use separate database table builder instances for each count query instead of reusing the FormSubmissionModel instance. This ensures each count operation has its own fresh query builder.

### Files Modified
- `app/Controllers/Dashboard.php`

### Changes Made
- Changed from using `$formSubmissionModel->where()->countAllResults()` to `$this->db->table('form_submissions')->where()->countAllResults()`
- Applied this fix to all three user types: requestor, approving_authority, and service_staff
- Each status count now uses its own builder instance, preventing query state conflicts

## Issue 2: Default Priority Level for New Submissions

### Problem
When creating new submissions, the default priority level was set to 'normal'. The requirement was to change this to 'low'.

### Solution
Updated multiple files to ensure the default priority level is 'low':

1. **Forms Controller** - Changed the default priority in the submit() method
2. **Database Migration** - Updated the original migration file and created a new migration
3. **Database Schema** - Applied the migration to update the column default value

### Files Modified
- `app/Controllers/Forms.php`
- `app/Database/Migrations/2025-08-01-070000_AddPriorityAndReferenceFeatures.php`
- `app/Database/Migrations/2025-10-07-000000_UpdatePriorityDefaultToLow.php` (new file)

### Changes Made
1. In `Forms.php`:
   - Changed default priority from 'normal' to 'low' in the submit() method (line ~218)
   - Updated fallback priority from 'normal' to 'low' when validation fails (2 occurrences)

2. In original migration file:
   - Changed the default value of the priority column from 'normal' to 'low'

3. Created new migration:
   - `2025-10-07-000000_UpdatePriorityDefaultToLow.php` to modify the existing database column
   - This migration updates the form_submissions table to change the default priority from 'normal' to 'low'
   - Migration has been successfully executed

## Testing Recommendations

1. **Dashboard Status Summary:**
   - Log in as different user types (requestor, approving_authority, service_staff)
   - Check that the dashboard displays accurate counts for each status category
   - Verify numbers match the actual form submissions in the database

2. **Default Priority Level:**
   - Create a new form submission as a requestor
   - Verify that the priority is automatically set to 'low'
   - Check the database to confirm the priority field contains 'low'
   - Ensure admin and service staff can still manually set different priorities

## Database Changes

The following SQL was executed via migration:
```sql
ALTER TABLE `form_submissions` 
MODIFY `priority` ENUM('low','normal','high','urgent','critical') 
DEFAULT 'low' NOT NULL;
```

## Rollback Instructions

If needed, you can rollback the priority default change by running:
```bash
php spark migrate:rollback
```

This will revert the priority default back to 'normal'.
