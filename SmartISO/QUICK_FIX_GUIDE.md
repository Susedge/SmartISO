# Quick Fix Summary - November 23, 2025

## 🎯 Issues & Solutions

### Issue 1: "All forms route to single department admin"
**Root Cause:** The CRSRF form has **specific approvers assigned** in the `form_signatories` table. This is working as designed - forms with signatories route to those specific people, NOT to department admins.

**Solution Options:**

**Option A: Use Department-Based Routing (Recommended for PDO)**
```sql
-- Remove specific signatories to enable department routing
DELETE FROM form_signatories WHERE form_id IN (
    SELECT id FROM forms WHERE code = 'CRSRF' -- or PDO form code
);
```
After this, forms will route to ALL approvers and department admins from the **submitter's department**.

**Option B: Keep Signatories, Add PDO Department Admin**
```sql
-- Add PDO department admin to form signatories
INSERT INTO form_signatories (form_id, user_id, required)
SELECT 
    (SELECT id FROM forms WHERE code = 'PDO_FORM_CODE'),
    (SELECT id FROM users WHERE user_type = 'department_admin' AND department_id = (SELECT id FROM departments WHERE code = 'PDO')),
    1;
```

**Option C: Create Department Admins for Each Department**
```sql
-- Promote existing users to department admin
UPDATE users 
SET user_type = 'department_admin' 
WHERE id = X AND department_id = Y;
```

---

### Issue 2: "Department admin notifications not triggering"
**Status:** ✅ Already working correctly

The notification system routes correctly:
1. If form has signatories → notify signatories only
2. If no signatories → notify all approvers + dept admins from **submitter's department**
3. Always notify global admins (admin/superuser)

**Verification:** Check logs at `writable/logs/log-YYYY-MM-DD.php` for:
```
INFO - Submission Notification - Notifying: [Name] (Type: department_admin)
```

---

### Issue 3: "Requestor completion notifications missing"
**Status:** ✅ Already implemented

Completion notifications are sent when service staff marks form as serviced. Implemented in:
- `FormSubmissionModel::markAsServiced()` (line 249)
- `NotificationModel::createServiceCompletionNotification()` (line 337)

**Verification:**
```sql
SELECT * FROM notifications 
WHERE title = 'Service Completed' 
ORDER BY created_at DESC;
```

---

### Issue 4: "Calendar shows all submissions to all department admins"
**Status:** ✅ FIXED

Added department_admin case to `Schedule::calendar()` method. Department admins now see ONLY their department's submissions.

**Fixed In:** `app/Controllers/Schedule.php` lines 484-505

**Test:**
1. Log in as dept admin
2. Go to `/schedule/calendar`
3. Verify only your department's submissions appear

---

## 📋 Quick Action Checklist

### For PDO Form Routing Issue:
- [ ] Identify PDO form ID: `SELECT id FROM forms WHERE code = 'PDO_FORM_CODE';`
- [ ] Check if form has signatories: `SELECT * FROM form_signatories WHERE form_id = [PDO_FORM_ID];`
- [ ] **Option 1:** Remove signatories: `DELETE FROM form_signatories WHERE form_id = [PDO_FORM_ID];`
- [ ] **Option 2:** Add PDO dept admin to signatories (see SQL above)
- [ ] Test by submitting PDO form
- [ ] Verify PDO dept admin receives notification

### For Department Admin Setup:
- [ ] Check dept admin coverage: Run `tools/test_department_fixes.php`
- [ ] Assign dept admins to each department: `UPDATE users SET user_type='department_admin', department_id=X WHERE id=Y;`
- [ ] Verify active flag: `UPDATE users SET active=1 WHERE user_type='department_admin';`

### For Calendar Testing:
- [ ] Log in as dept admin from Department A
- [ ] Navigate to calendar
- [ ] Verify only Department A submissions visible
- [ ] Log in as dept admin from Department B
- [ ] Verify different submissions (Department B only)
- [ ] Check logs: `grep "Department Admin Calendar" writable/logs/log-*.php`

---

## 🔍 Diagnostic Commands

### Check Department Coverage:
```bash
cd SmartISO
php tools/test_department_fixes.php
```

### Check Form Routing:
```sql
-- See who gets notified for a specific form
SELECT DISTINCT u.full_name, u.user_type, u.department_id, d.description as dept
FROM form_signatories fs
LEFT JOIN users u ON u.id = fs.user_id
LEFT JOIN departments d ON d.id = u.department_id
WHERE fs.form_id = [FORM_ID];
```

### Check Recent Notifications:
```sql
SELECT n.created_at, u.username, u.user_type, fs.form_id, f.code
FROM notifications n
LEFT JOIN users u ON u.id = n.user_id
LEFT JOIN form_submissions fs ON fs.id = n.submission_id
LEFT JOIN forms f ON f.id = fs.form_id
WHERE n.title = 'New Service Request Requires Approval'
ORDER BY n.created_at DESC
LIMIT 10;
```

### Monitor Logs:
```bash
# Watch for new notifications
tail -f writable/logs/log-2025-11-23.php | grep "Submission Notification"

# Check calendar access
grep "Department Admin Calendar" writable/logs/log-*.php
```

---

## 📄 Full Documentation

See `FIXES_NOVEMBER_2025.md` for comprehensive details, including:
- Complete code changes
- Database schema reference
- Testing procedures
- Rollback instructions
- Performance notes

---

## ⚡ TL;DR

1. **Form routing works correctly** - it's using form signatories (by design)
2. **To fix PDO routing:** Remove CRSRF signatories OR add PDO dept admin to signatories
3. **Notifications work** - added logging for visibility
4. **Completion notifications work** - already implemented
5. **Calendar now filtered** - dept admins see only their department

**Most Important Fix:** Delete from `form_signatories` table to enable department-based routing.
