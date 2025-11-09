# Security Enhancement: WHERE Clause Filtering

## Overview

This document describes the security improvements implemented to prevent unauthorized data access through department and office filtering. The system now enforces access control using database WHERE clauses instead of relying on user-provided dropdown values.

## Security Issue Addressed

**CRITICAL**: The previous implementation allowed users to manipulate dropdown filter values to potentially view submissions from departments/offices they should not have access to. For example:
- A department admin could change the department dropdown value to see other departments' data
- An office-scoped user could modify the office filter to view other offices' submissions

## Solution Implemented

### 1. Session-Based Access Control

**File**: `app/Controllers/Auth.php`

Added `office_id` to session data during login:

```php
$sessionData = [
    'user_id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'full_name' => $user['full_name'],
    'user_type' => $user['user_type'],
    'department_id' => $user['department_id'] ?? null,
    'office_id' => $user['office_id'] ?? null,  // NEW: Now stored in session
    'is_department_admin' => ($user['user_type'] === 'department_admin'),
    'isLoggedIn' => true,
    'last_activity' => time()
];
```

### 2. Automatic WHERE Clause Filtering

**Files Updated**:
- `app/Controllers/Forms.php` - Multiple methods
- `app/Views/forms/pending_approval.php`
- `app/Views/forms/index.php`

#### Access Levels

1. **Global Admins** (`admin`, `superuser`)
   - Can view ALL submissions from any department/office
   - Can use dropdown filters to narrow their view
   - No WHERE clause restrictions applied

2. **Department Admins** (`department_admin`)
   - Automatically restricted to their `department_id` via WHERE clause
   - If they have `office_id`, also restricted to that office
   - Cannot bypass restrictions via dropdowns

3. **Approving Authorities** (`approving_authority`)
   - Automatically restricted to their `department_id` via WHERE clause
   - If they have `office_id`, also restricted to that office
   - Cannot bypass restrictions via dropdowns

4. **Requestors and Service Staff**
   - Automatically scoped to their department/office
   - Can only view/submit forms for their assigned areas

## Implementation Details

### Controller Changes

#### `pendingApproval()` Method

**Before** (INSECURE):
```php
$departmentFilter = $this->request->getGet('department');
$officeFilter = $this->request->getGet('office');

// Apply filters from user input
if ($departmentFilter) {
    $builder->where('users.department_id', $departmentFilter);
}
if ($officeFilter) {
    $builder->where('forms.office_id', $officeFilter);
}
```

**After** (SECURE):
```php
$userDepartmentId = session()->get('department_id');
$userOfficeId = session()->get('office_id');
$isGlobalAdmin = in_array($userType, ['admin', 'superuser']);

// SECURITY: Enforce department filtering for non-global admins using WHERE clause
// This cannot be bypassed by manipulating dropdown values
if (!$isGlobalAdmin && $userDepartmentId) {
    $builder->where('users.department_id', $userDepartmentId);
    log_message('info', "Non-admin user {$userType} restricted to department {$userDepartmentId}");
}

// SECURITY: Enforce office filtering if user has office assignment
if (!$isGlobalAdmin && $userOfficeId) {
    $builder->where('forms.office_id', $userOfficeId);
    log_message('info', "Non-admin user {$userType} restricted to office {$userOfficeId}");
}
```

#### Other Methods Updated

The same pattern was applied to:
- `index()` - Available forms list
- `departmentSubmissions()` - Department admin view
- `bulkApprove()` - Mass approval function
- `mySubmissions()` - Already scoped, verified security
- `pendingService()` - Already scoped, verified security
- `completedForms()` - Already scoped, verified security

### View Changes

#### Global Admin View
- Shows department and office dropdown filters
- Can filter across all departments/offices
- Uses GET parameters to apply filters

#### Non-Admin View
- Dropdowns completely hidden
- Shows an informational alert with their assigned department/office
- Example:
  ```
  ℹ️ Your Access:
  Department: Engineering | Office: CAD Department
  You can only approve forms from your assigned department/office.
  ```
- Cannot manipulate filters since they don't exist in the UI

### Logging

Added security logging for transparency and audit:

```php
log_message('info', "Non-admin user {$userType} restricted to department {$userDepartmentId}");
log_message('info', "Non-admin user {$userType} restricted to office {$userOfficeId}");
log_message('warning', "User {$userId} ({$userType}) attempted to approve submission {$submissionId} from different department");
```

## Testing Recommendations

### Test Cases for Department Admins

1. ✅ **Positive**: Can view submissions from their department
2. ✅ **Negative**: Cannot view submissions from other departments (even if they manually craft URL with department=X)
3. ✅ **Positive**: Can approve forms from their department
4. ✅ **Negative**: Cannot approve forms from other departments
5. ✅ **Positive**: Can only assign service staff from their department

### Test Cases for Office-Scoped Users

1. ✅ **Positive**: Can view forms assigned to their office
2. ✅ **Negative**: Cannot view forms from other offices in the same department
3. ✅ **Negative**: Cannot bypass office restriction via URL manipulation

### Test Cases for Global Admins

1. ✅ **Positive**: Can view all submissions
2. ✅ **Positive**: Can use dropdown filters
3. ✅ **Positive**: Can approve forms from any department/office
4. ✅ **Positive**: Can assign any service staff

## SQL Injection Prevention

All filtering still uses CodeIgniter's Query Builder with parameter binding:

```php
$builder->where('users.department_id', $userDepartmentId);  // Safe: uses prepared statements
$builder->where('forms.office_id', $userOfficeId);          // Safe: uses prepared statements
```

## Migration Notes

### Breaking Changes
- **None**: The system maintains backward compatibility
- Old URLs with department/office parameters will be ignored for non-admins
- Global admins can still use all previous functionality

### Session Updates
- Users need to log out and log back in to get `office_id` in their session
- Existing sessions without `office_id` will work but won't have office-level filtering
- Consider adding a migration script to force re-login or update existing sessions

### Database Requirements
- No schema changes required
- Existing `department_id` and `office_id` columns in `users` table must be populated correctly
- Ensure users have proper department/office assignments

## Security Benefits

1. **Authorization Bypass Prevention**: Users cannot manipulate filters to see other departments' data
2. **Principle of Least Privilege**: Users only see data they're authorized to access
3. **Audit Trail**: Security-related access is logged
4. **Defense in Depth**: Multiple layers of validation (session + WHERE clause + authorization checks)
5. **Clear UI**: Non-admin users see exactly what they have access to

## Performance Considerations

- **Improved**: WHERE clauses with proper indexes are faster than client-side filtering
- **Reduced Data Transfer**: Only authorized data is queried from database
- **Better Scalability**: Database handles filtering instead of PHP

## Recommended Indexes

Ensure these indexes exist for optimal performance:

```sql
-- Users table
CREATE INDEX idx_users_department ON users(department_id);
CREATE INDEX idx_users_office ON users(office_id);

-- Forms table
CREATE INDEX idx_forms_office ON forms(office_id);
CREATE INDEX idx_forms_department ON forms(department_id);

-- Form submissions
CREATE INDEX idx_submissions_status ON form_submissions(status);
```

## Future Enhancements

1. **Role-Based Access Control (RBAC)**: Implement more granular permissions
2. **Department Hierarchy**: Support parent/child department relationships
3. **Multi-Department Users**: Allow users to belong to multiple departments
4. **Temporary Access Grants**: Allow admins to grant temporary cross-department access
5. **Access Audit Report**: Dashboard showing who accessed what data

## Compliance

This implementation supports:
- **GDPR**: Data minimization principle (users only see necessary data)
- **ISO 27001**: Access control (A.9.4.1)
- **SOC 2**: Logical Access Controls (CC6.1)

## Files Modified

### Controllers
- `app/Controllers/Auth.php` - Added office_id to session
- `app/Controllers/Forms.php` - Updated multiple methods with WHERE clause filtering

### Views
- `app/Views/forms/pending_approval.php` - Conditional display of filters
- `app/Views/forms/index.php` - Conditional display of filters

### Documentation
- `APPROVAL_SYSTEM_FIX.md` - Previous approval system fixes
- `SECURITY_FILTERING_UPDATE.md` - This document

## Support

For questions or issues:
1. Check application logs: `writable/logs/log-YYYY-MM-DD.log`
2. Review security logs for unauthorized access attempts
3. Verify user's department_id and office_id in database
4. Ensure user has logged out and back in after updates

---

**Last Updated**: November 9, 2025  
**Version**: 1.0  
**Security Level**: HIGH PRIORITY
