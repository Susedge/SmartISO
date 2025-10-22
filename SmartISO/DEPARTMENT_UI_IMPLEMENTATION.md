# Department-Based UI Access Control Implementation

## Overview
This document describes the implementation of department-based access control across all UI components in the SmartISO system. All visible data, actions, and options are now properly scoped to the user's assigned department, while global admins retain full visibility and access.

**Implementation Date**: October 22, 2025

---

## Role-Based Access Matrix

| User Role | Department Filtering | Can See Other Departments | Can Switch Departments |
|-----------|---------------------|---------------------------|------------------------|
| **Superuser** | ❌ No filtering | ✅ All departments | ✅ Yes |
| **Admin** | ❌ No filtering | ✅ All departments | ✅ Yes |
| **Department Admin** | ✅ Own department only | ❌ No | ❌ No |
| **Approving Authority** | ✅ Own department only | ❌ No | ❌ No |
| **Service Staff** | ✅ Own department only | ❌ No | ❌ No |
| **Requestor** | ✅ Own department only | ❌ No | ❌ No |

---

## Files Modified

### Controllers

#### 1. **Forms.php** (`app/Controllers/Forms.php`)
**Changes:**
- `index()`: Automatically filters forms by user's department for non-admin users
- `mySubmissions()`: Enhanced to support department admin filtering and regular user filtering
- `pendingApproval()`: Filters pending submissions by department with proper query building
- `pendingService()`: Filters service assignments by requestor's department

**Key Logic:**
```php
$isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
$isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');

if (!$isGlobalAdmin && $userDepartmentId) {
    $selectedDepartment = (int)$userDepartmentId; // Force department filter
}
```

#### 2. **Dashboard.php** (`app/Controllers/Dashboard.php`)
**Changes:**
- `index()`: All dashboard statistics now filtered by department
- Status summaries respect department boundaries
- Separate logic for each user role (requestor, approver, service_staff, admin)

**Statistics Filtered:**
- Pending approvals count
- Approved submissions count
- Rejected submissions count
- Completed submissions count
- Service assignments count

#### 3. **Schedule.php** (`app/Controllers/Schedule.php`)
**Changes:**
- `index()`: Calendar events filtered by department for non-admins
- Added support for department admin viewing department schedules
- Service staff schedules filtered by requestor department
- Approving authority schedules filtered by department

**New Helper Methods:**
- `getDepartmentSubmissionsWithoutSchedules($departmentId)`: Get unscheduled submissions for a department
- `getServiceStaffSubmissionsWithoutSchedules($staffId, $departmentId)`: Updated to support department filtering

#### 4. **Analytics.php** (`app/Controllers/Analytics.php`)
**Changes:**
- `index()`: Pass department filter to all analytics methods
- `getOverviewData($filterDepartmentId)`: All overview stats filtered by department
- `getFormStatistics($filterDepartmentId)`: Form usage and processing times filtered by department

**Analytics Filtered:**
- Total submissions
- Status distribution
- Recent submissions
- Completion rate
- Form usage statistics
- Processing time metrics

### Models

#### 5. **ScheduleModel.php** (`app/Models/ScheduleModel.php`)
**New Method:**
```php
public function getDepartmentSchedules($departmentId)
```
Returns all schedules for submissions from users in the specified department.

### Views

#### 6. **forms/index.php** (`app/Views/forms/index.php`)
**Changes:**
- Added department filter badge when filtering is active
- Department selector disabled for non-admin users
- Hidden input maintains department filter value
- Visual indicator: "Filtered by: [Department Name]" badge

#### 7. **forms/my_submissions.php** (`app/Views/forms/my_submissions.php`)
**Changes:**
- Added badge indicating department-specific filtering
- Shows "Showing department-specific submissions only" for non-admins

#### 8. **forms/pending_approval.php** (`app/Views/forms/pending_approval.php`)
**Changes:**
- Added department filter indicator badge
- Department selector disabled for non-admins with "Department restricted" note
- Fixed department dropdown to use array structure (id/description)
- Hidden input maintains department filter

#### 9. **forms/pending_service.php** (`app/Views/forms/pending_service.php`)
**Changes:**
- Added department filter badge
- Shows "Showing department-specific submissions only" for service staff

---

## Technical Implementation Details

### Session Variables Used

| Variable | Purpose | Set By |
|----------|---------|--------|
| `user_type` | User role (admin, requestor, etc.) | Authentication |
| `department_id` | User's assigned department | User record |
| `is_department_admin` | Boolean flag for department admin role | Login process |
| `scoped_department_id` | Department ID for department admin filtering | Login process |

### Database Queries

All department filtering uses JOINs to the users table:

```php
$builder->join('users', 'users.id = form_submissions.submitted_by')
        ->where('users.department_id', $filterDepartmentId);
```

### Filter Logic Pattern

Consistent pattern used across all controllers:

```php
$userType = session()->get('user_type');
$userDepartmentId = session()->get('department_id');
$isGlobalAdmin = in_array($userType, ['admin', 'superuser']);
$isDepartmentAdmin = session()->get('is_department_admin') && session()->get('scoped_department_id');

$filterDepartmentId = null;
if (!$isGlobalAdmin && $userDepartmentId) {
    $filterDepartmentId = $userDepartmentId;
}
if ($isDepartmentAdmin) {
    $filterDepartmentId = session()->get('scoped_department_id');
}
```

---

## UI/UX Enhancements

### Visual Indicators

1. **Badge Indicators**: Info badges show when data is department-filtered
   ```html
   <span class="badge bg-info text-dark">
       <i class="fas fa-filter me-1"></i>
       Filtered by: [Department Name]
   </span>
   ```

2. **Disabled Controls**: Department selectors disabled for non-admins with explanatory text

3. **Hidden Inputs**: Maintain filter state even when controls are disabled

### User Experience

- **Non-Admin Users**: See only their department data automatically
- **No Manual Override**: Cannot change department filter
- **Clear Communication**: Visual indicators show filtering is active
- **Consistent Behavior**: Same filtering logic across all pages

---

## Data Isolation

### What Non-Admins CANNOT See

1. ❌ Forms from other departments
2. ❌ Submissions from users in other departments
3. ❌ Schedules for other department submissions
4. ❌ Analytics data from other departments
5. ❌ Dashboard statistics from other departments

### What Global Admins CAN See

1. ✅ All forms across all departments
2. ✅ All submissions from any user
3. ✅ All schedules system-wide
4. ✅ Complete analytics and statistics
5. ✅ Unrestricted dashboard data

---

## Testing Checklist

### Test Scenarios

- [ ] **Requestor**: Can only see own submissions and department forms
- [ ] **Approving Authority**: Can only approve submissions from own department
- [ ] **Service Staff**: Can only see service assignments from own department
- [ ] **Department Admin**: Can manage users/forms in own department only
- [ ] **Global Admin**: Can see and manage everything
- [ ] **Department Filter**: Persists across page navigation
- [ ] **Cross-Department Access**: Attempting to view other department data returns no results or error
- [ ] **UI Indicators**: Badges and messages display correctly
- [ ] **Disabled Controls**: Department selectors properly disabled for non-admins

### Security Validation

- [ ] Direct URL manipulation doesn't bypass department filters
- [ ] Session variables correctly set and validated
- [ ] Database queries always include department filter for non-admins
- [ ] API endpoints respect department boundaries
- [ ] No data leakage in AJAX responses

---

## Migration Notes

### Backward Compatibility

- ✅ Existing admin/superuser workflows unchanged
- ✅ Department admin functionality preserved
- ✅ No database schema changes required
- ✅ All existing features continue to work

### Deployment Steps

1. Deploy updated controller files
2. Deploy updated view files
3. Deploy updated model files
4. Clear application cache: `php spark cache:clear`
5. Test with different user roles
6. Verify no cross-department data leakage

---

## Performance Considerations

### Database Impact

- Additional JOINs to users table for department filtering
- Indexes recommended:
  ```sql
  CREATE INDEX idx_users_department ON users(department_id);
  CREATE INDEX idx_form_submissions_submitted_by ON form_submissions(submitted_by);
  ```

### Query Optimization

- Use of `countAllResults(false)` to preserve query state
- Proper use of query builder cloning for multiple counts
- Selective JOIN only when department filtering is needed

---

## Future Enhancements

### Potential Improvements

1. **Multi-Department Access**: Allow users to belong to multiple departments
2. **Department Hierarchy**: Support parent/child department relationships
3. **Temporary Access**: Time-limited cross-department access for special cases
4. **Audit Logging**: Track when admins view other department data
5. **Department Dashboards**: Specialized views for department admins

### Configuration Options

Consider adding settings for:
- Allow/disallow cross-department visibility for specific roles
- Department isolation strictness levels
- Department-based notification preferences

---

## Support & Maintenance

### Common Issues

**Issue**: User sees no data after login
- **Cause**: User has no department assigned
- **Solution**: Assign user to a department in user management

**Issue**: Department filter not working
- **Cause**: Session variables not set
- **Solution**: Check authentication flow and session storage

**Issue**: Global admin sees department filter
- **Cause**: User type not correctly identified
- **Solution**: Verify `user_type` in session

### Debug Mode

To debug department filtering, add to controller methods:
```php
log_message('debug', 'Department Filter - User: ' . $userId . ', Type: ' . $userType . ', Dept: ' . $filterDepartmentId);
```

---

## Compliance & Security

### Data Privacy

- Each department's data isolated from others
- No cross-department data exposure
- Audit trail via application logs

### Access Control

- Role-based permissions enforced
- Department-based restrictions applied
- Admin override capability maintained

---

## Conclusion

The department-based UI implementation provides comprehensive data isolation while maintaining flexibility for administrative users. All UI components now consistently respect department boundaries, ensuring users only see data relevant to their assigned department.

**Key Benefits:**
- ✅ Enhanced data security and privacy
- ✅ Improved user experience (less clutter)
- ✅ Maintained admin flexibility
- ✅ Consistent filtering across all pages
- ✅ Clear visual feedback to users

For questions or issues, please refer to the technical contact or system administrator.
