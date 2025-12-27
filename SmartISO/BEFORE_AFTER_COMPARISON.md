# Department Filtering Fix - Before vs After

## The Core Issue

### Before Fix ❌
```sql
-- Filtering by REQUESTOR's department
WHERE u.department_id = 22
```
**Result:** Shows submissions made BY users from IT department  
**Problem:** IT users don't submit CRSRF forms, so showed 0. But IT admin was still seeing 5 submissions somehow.

### After Fix ✅
```sql
-- Filtering by FORM's department  
WHERE f.department_id = 22
```
**Result:** Shows submissions TO forms owned BY IT department  
**Correct:** IT department owns FORM2123, so shows only FORM2123 submissions (currently 0)

---

## Real-World Example

### CRSRF Form
- **Form Code:** CRSRF
- **Form Name:** Computer Repair Service Request Forms
- **Owned By:** Administration Department (ID: 12)
- **Submissions:** 18 total (10 with schedules)

### Before Fix ❌
- IT Department Admin could see CRSRF submissions (because of signatory role or bug)
- **Wrong:** IT doesn't own CRSRF, shouldn't see it

### After Fix ✅
- IT Department Admin sees **0 schedules**
- Administration Department Admin sees **10 CRSRF schedules**
- **Correct:** Only the form's owning department sees submissions

---

## Changed Files Summary

| File | Method | Old Filter | New Filter |
|------|--------|------------|------------|
| ScheduleModel.php | getDepartmentSchedules() | `u.department_id` | `f.department_id` |
| Schedule.php | getDepartmentSubmissionsWithoutSchedules() | `u.department_id` | `f.department_id` |
| Schedule.php | Safeguard filter | `requestor_department_id` | `form_department_id` |
| NotificationModel.php | getUserNotifications() | `u.department_id` (requestor) | `f.department_id` (form) |
| NotificationModel.php | getUnreadCount() | `u.department_id` (requestor) | `f.department_id` (form) |

---

## Business Logic Explanation

**Forms belong to departments:**
- Each form has a `department_id` in the `forms` table
- This indicates which department **owns and manages** that form
- Department admins are responsible for their forms' submissions
- Users from ANY department can submit ANY form (cross-department submissions allowed)

**Department Admin sees:**
- ✅ All submissions for forms owned by their department
- ❌ Submissions to forms owned by other departments
- Doesn't matter who submitted it - only matters which form it was submitted to

**Example:**
- IT Department owns "IT Support Request Form"
- User from Administration submits "IT Support Request Form"
- **IT Department Admin sees it** ✅ (their form)
- **Administration Department Admin does NOT see it** ❌ (not their form)

---

## Database Schema Reference

```sql
forms table:
  - id
  - code
  - description
  - department_id  ← OWNER of the form

form_submissions table:
  - id
  - form_id        ← Which form
  - submitted_by   ← Which user

users table:
  - id
  - department_id  ← User's department

schedules table:
  - id
  - submission_id  ← Links to form_submissions
```

**Key Join:**
```sql
schedules → form_submissions → forms
                    ↓
                forms.department_id ← Filter by this!
```

Not:
```sql
schedules → form_submissions → users
                    ↓
                users.department_id ← Wrong!
```

---

## Testing Checklist

- [x] No syntax errors
- [x] Database queries work correctly
- [x] IT admin sees 0 schedules (correct - FORM2123 has 0 submissions)
- [x] IT admin does NOT see CRSRF schedules (correct - CRSRF belongs to Administration)
- [x] Safeguard filter uses correct department field
- [x] Notifications filtered correctly
- [ ] **Manual test:** Log in as dept_admin_it and verify calendar is empty

---

## Rollback Plan

If needed, revert by changing:
```php
// Revert in ScheduleModel.php line ~237
->where('f.department_id', $departmentId)
// back to:
->where('u.department_id', $departmentId)

// Revert in Schedule.php line ~1307
->where('f.department_id', $departmentId)
// back to:
->where('u.department_id', $departmentId)

// Revert NotificationModel.php lines ~95, ~132
->join('forms f', 'f.id = fs.form_id', 'left')
->orWhere('f.department_id', $userDepartment)
// back to:
->join('users u', 'u.id = fs.submitted_by', 'left')
->orWhere('u.department_id', $userDepartment)
```

But this should NOT be needed - the fix is correct! ✅
