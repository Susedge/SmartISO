# Requestor Enhancements - November 23, 2025

## Overview
This document describes enhancements made to the requestor functionality to improve form access, filtering, and notification systems.

---

## Changes Implemented

### 1. **Requestor Form Access - View All Forms**

**Previous Behavior:**
- Requestors were restricted to viewing only forms from their assigned department/office
- Same access restrictions as other non-admin users

**New Behavior:**
- Requestors can now view ALL available forms regardless of department or office
- Optional filtering by department and office to help requestors find relevant forms
- No restrictions on which forms they can submit

**Rationale:**
Requestors should be able to submit forms for any department/office based on their service needs. The approval workflow ensures proper routing based on form configuration and department assignment.

**Files Modified:**
- `app/Controllers/Forms.php` - `index()` method
  - Added `$isRequestor` flag
  - Removed automatic department/office restrictions for requestors
  - Requestors can optionally filter forms using dropdowns

---

### 2. **Form Filtering UI for Requestors**

**Implementation:**
- Added Department and Office filter dropdowns for requestors
- Filters are optional - requestors can view all forms without filtering
- Filter dropdowns auto-submit when changed
- Reset button to clear all filters

**Features:**
- Department filter: Shows all departments
- Office filter: Shows all offices (cascading based on department selection)
- JavaScript-based office filtering based on department
- Visual indicator showing "Full Access" status

**User Interface:**
```
✓ Full Access: You can view and submit all available forms.
Use filters below to narrow down your search by department or office.

[Department Filter Dropdown] [Office Filter Dropdown] [Filter Button] [Reset Button]
```

**Files Modified:**
- `app/Views/forms/index.php`
  - Added new conditional block for requestors (between admin and non-admin sections)
  - Uses same filtering mechanism as global admins
  - Different visual styling with success alert

---

### 3. **Approval Workflow Integrity**

**Verification:**
The approval workflow remains department and form-based:

1. **Form Submission:**
   - Requestors can submit any form
   - Submission is recorded with requestor's user ID
   - Status set to "submitted"
   - Priority set based on user role (requestors get 'low' by default)

2. **Notification Routing:**
   - `NotificationModel::createSubmissionNotification()` handles routing
   - Notifies approvers assigned to the specific form via `form_signatories` table
   - Falls back to approving authorities from the **requestor's department**
   - Ensures proper department-based routing even when requestors submit forms for other departments

3. **Approval Process:**
   - Approvers must be assigned to the form via `form_signatories`
   - Department admins can only approve forms from their department
   - Global admins can view all but must be assigned to approve specific forms
   - Uses `isAssignedApprover()` verification

**No Changes Required:**
The existing approval workflow correctly handles submissions from requestors regardless of which form they submit. The department-based routing is based on the **requestor's department**, not the form's department.

---

### 4. **Completed Form Notification for Requestors**

**Status:** ✅ Already Implemented

**Implementation Location:**
- `app/Models/FormSubmissionModel.php` - `markAsServiced()` method
- `app/Models/NotificationModel.php` - `createServiceCompletionNotification()` method

**How It Works:**
1. When service staff completes a form and signs:
   ```php
   $this->formSubmissionModel->markAsServiced($submissionId, $serviceStaffId, $notes);
   ```

2. This method updates the submission status to 'completed' and triggers notification:
   ```php
   $notificationModel->createServiceCompletionNotification($submissionId, $submission['submitted_by']);
   ```

3. Notification sent to requestor:
   - **Title:** "Service Completed"
   - **Message:** "Your service request has been completed successfully. You can now provide feedback about your experience."
   - **Email:** Sent via EmailService if configured
   - **In-app:** Appears in notification bell

**Notification Content:**
```php
$title = 'Service Completed';
$message = 'Your service request has been completed successfully. 
            You can now provide feedback about your experience.';
```

---

## Testing Instructions

### Test 1: Requestor Can View All Forms

**Objective:** Verify requestors can see forms from all departments/offices

**Steps:**
1. Log in as a requestor user (user_type = 'requestor')
2. Navigate to Forms page (`/forms`)
3. Verify you can see forms from multiple departments and offices
4. Verify "Full Access" message is displayed

**Expected Results:**
- All active forms are displayed in the table
- Forms from different departments/offices are visible
- Success alert shows "Full Access: You can view and submit all available forms"
- Department and Office filter dropdowns are visible

**Database Query to Verify:**
```sql
-- Check requestor user
SELECT id, username, user_type, department_id, office_id FROM users WHERE user_type = 'requestor';

-- Check forms across departments
SELECT id, code, description, department_id, office_id FROM forms ORDER BY department_id;

-- Verify form access is not restricted
SELECT COUNT(*) as total_forms FROM forms WHERE active = 1;
```

---

### Test 2: Form Filtering for Requestors

**Objective:** Verify filtering works correctly for requestors

**Steps:**
1. Log in as a requestor
2. Navigate to Forms page
3. Select a department from the dropdown
4. Verify office dropdown updates to show only offices from that department
5. Submit the filter
6. Verify forms are filtered accordingly
7. Click Reset button
8. Verify all forms are displayed again

**Expected Results:**
- Department filter affects office dropdown options
- Filtering works correctly (shows forms matching selected criteria)
- Reset clears filters and shows all forms
- Filter selection persists after page reload

**Database Query to Test Filtering:**
```sql
-- Test department filter (e.g., department_id = 12)
SELECT f.*, d.description as dept_name, o.description as office_name
FROM forms f
LEFT JOIN departments d ON d.id = f.department_id
LEFT JOIN offices o ON o.id = f.office_id
WHERE (f.department_id = 12 OR o.department_id = 12);

-- Test office filter (e.g., office_id = 2)
SELECT f.*, o.description as office_name, d.description as dept_name
FROM forms f
LEFT JOIN offices o ON o.id = f.office_id
LEFT JOIN departments d ON d.id = o.department_id
WHERE f.office_id = 2;
```

---

### Test 3: Form Submission and Approval Routing

**Objective:** Verify submissions from requestors route to correct approvers

**Steps:**
1. Log in as a requestor from Department A
2. Submit a form that belongs to Department B
3. Check notifications table for approver notifications
4. Verify approvers from requestor's Department A receive notifications (not Department B)
5. Log in as an approver from Department A
6. Verify they can see and approve the submission

**Expected Results:**
- Submission created successfully
- Notifications sent to approvers based on requestor's department
- Approvers assigned to the form via form_signatories receive notifications
- Approval process works normally

**Database Queries:**
```sql
-- Check submission was created
SELECT * FROM form_submissions WHERE submitted_by = [requestor_user_id] ORDER BY id DESC LIMIT 1;

-- Check which approvers were notified
SELECT n.*, u.full_name, u.user_type, u.department_id
FROM notifications n
JOIN users u ON u.id = n.user_id
WHERE n.submission_id = [submission_id];

-- Verify form signatories for the form
SELECT fs.*, u.full_name, u.user_type
FROM form_signatories fs
JOIN users u ON u.id = fs.user_id
WHERE fs.form_id = [form_id];
```

---

### Test 4: Service Completion Notification

**Objective:** Verify requestors receive notification when service staff completes their form

**Prerequisites:**
1. Have a form submission in 'approved' status with service staff assigned
2. Know the submission ID and requestor ID

**Steps:**
1. Log in as service staff
2. Navigate to Pending Service forms
3. Find the test submission and click "Service Form"
4. Fill in service notes
5. Sign the form (mark as serviced)
6. Log out and log in as the requestor
7. Check notifications (bell icon in header)
8. Verify "Service Completed" notification appears
9. Click notification to view the completed form

**Expected Results:**
- Service staff successfully signs and completes the form
- Requestor receives in-app notification with title "Service Completed"
- Notification message: "Your service request has been completed successfully..."
- Email notification sent (if email is configured)
- Clicking notification navigates to the completed form
- Form status is "completed"

**Database Queries:**
```sql
-- Update submission to completed status (simulated by service staff)
UPDATE form_submissions 
SET service_staff_signature_date = NOW(), 
    service_notes = 'Test completion',
    status = 'completed',
    completed = 1,
    completion_date = NOW()
WHERE id = [submission_id];

-- Check notification was created
SELECT * FROM notifications 
WHERE submission_id = [submission_id] 
AND user_id = [requestor_id]
AND title = 'Service Completed';

-- Verify email sending (check logs)
-- Check: writable/logs/log-[date].log for email sending attempts
```

**Manual Trigger (if needed):**
```php
// In PHP command line or temporary test file
require 'vendor/autoload.php';

$notificationModel = new \App\Models\NotificationModel();
$submissionId = 123; // Your test submission ID
$requestorId = 3;    // Your test requestor ID

$notificationModel->createServiceCompletionNotification($submissionId, $requestorId);

echo "Notification created successfully!\n";
```

---

## Technical Details

### Database Schema Relationships

**Form Submission Flow:**
```
form_submissions
├── form_id          → forms.id
├── submitted_by     → users.id (requestor)
├── approver_id      → users.id (approving authority)
├── service_staff_id → users.id (service staff)
└── status           (submitted → approved → completed)
```

**Approval Assignment:**
```
form_signatories
├── form_id    → forms.id
└── user_id    → users.id (assigned approver)
```

**Department/Office Relationships:**
```
users
├── department_id → departments.id
└── office_id     → offices.id

forms
├── department_id → departments.id (optional)
└── office_id     → offices.id (optional)

offices
└── department_id → departments.id
```

---

### Code Locations

**Form Access Logic:**
- `app/Controllers/Forms.php::index()` (Lines ~110-260)
- `app/Views/forms/index.php` (Lines ~20-95)

**Submission Routing:**
- `app/Models/NotificationModel.php::createSubmissionNotification()` (Lines ~176-256)
- Determines which approvers to notify based on form signatories and requestor's department

**Completion Notification:**
- `app/Models/FormSubmissionModel.php::markAsServiced()` (Lines ~249-268)
- `app/Models/NotificationModel.php::createServiceCompletionNotification()` (Lines ~323-339)

**Approval Verification:**
- `app/Controllers/Forms.php::isAssignedApprover()` (Lines ~82-97)
- `app/Controllers/Forms.php::submitApproval()` (Lines ~1749-1850)

---

## Configuration

### Email Notifications (Optional)

If you want requestors to receive email notifications for completed forms:

1. Configure email settings in `app/Config/Email.php`
2. Set SMTP credentials (Gmail, etc.)
3. Test email configuration:
   ```bash
   php spark test:email youremail@example.com
   ```

4. Email will be sent automatically when:
   - Form is submitted (to approvers)
   - Form is approved/rejected (to requestor)
   - Service staff completes form (to requestor)
   - Form is assigned to service staff (to service staff)

**Email Configuration Reference:**
- See: `GMAIL_NOTIFICATIONS_GUIDE.md` in project root
- Email library: `app/Libraries/EmailService.php`
- Email config: `app/Config/Email.php`

---

## Rollback Instructions

If you need to revert these changes:

### 1. Revert Controller Changes
```bash
# In PowerShell from project root
cd SmartISO
git diff app/Controllers/Forms.php
# If using git, revert specific lines or entire file
git checkout HEAD -- app/Controllers/Forms.php
```

### 2. Revert View Changes
```bash
git checkout HEAD -- app/Views/forms/index.php
```

### 3. Manual Rollback (if not using Git)

**File: `app/Controllers/Forms.php`**
Change line ~118:
```php
$isRequestor = ($userType === 'requestor');  // REMOVE THIS

// Change from:
if ($isGlobalAdmin || $isRequestor) {
// Back to:
if ($isGlobalAdmin) {
```

Change line ~146:
```php
// Change from:
if ($isGlobalAdmin || $isRequestor) {
// Back to:
if ($isGlobalAdmin) {
```

Change line ~175:
```php
// Change from:
if (!$isRequestor) {
    // Apply restrictions for non-requestor users
    ...
} else {
    // Apply optional filters for requestors
    ...
}
// Back to:
if (!empty($selectedDepartment) && !empty($selectedOffice)) {
    // Original filtering logic without requestor check
    ...
}
```

**File: `app/Views/forms/index.php`**
Remove lines ~57-95 (the requestor-specific filtering section):
```php
<?php elseif ($displayIsRequestor): ?>
    <!-- Requestors can view all forms and filter by department/office -->
    ...
<?php else: ?>
```

---

## Summary

✅ **Requestors can view all forms** - No longer restricted to their department/office
✅ **Filtering UI provided** - Optional department and office filters to help find forms
✅ **Submission routing intact** - Forms route to correct approvers based on configuration
✅ **Completion notification working** - Already implemented, requestors get notified when service staff completes forms
✅ **Backward compatible** - Other user roles (admin, service staff, etc.) are unaffected

**Benefits:**
- Improved user experience for requestors
- Better form discoverability
- Maintains security through approval workflow
- No breaking changes to existing functionality

---

## Support and Troubleshooting

### Common Issues

**Issue:** Requestors not seeing all forms
- **Solution:** Clear browser cache, check user_type is 'requestor', verify forms are active

**Issue:** Filters not working
- **Solution:** Check JavaScript console for errors, verify office.department_id is set correctly

**Issue:** Approval routing broken
- **Solution:** Verify form_signatories table has correct approver assignments, check requestor's department_id is set

**Issue:** Completion notification not received
- **Solution:** Check notifications table, verify service staff called markAsServiced(), check email configuration if using email notifications

### Logging

Enable debug logging to troubleshoot:
```php
// In app/Config/Logger.php
public $threshold = 4; // 4 = debug level
```

Check logs:
```
writable/logs/log-[date].log
```

Look for:
- "Requestor filtering forms" - Form filtering activity
- "createServiceCompletionNotification" - Completion notification creation
- "User {userId} ({userType}) granted access to approval pages" - Approval access

---

**Document Version:** 1.0  
**Date:** November 23, 2025  
**Author:** SmartISO Development Team
