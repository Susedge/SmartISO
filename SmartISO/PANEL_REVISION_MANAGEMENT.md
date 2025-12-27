# Panel Revision Management System

## Overview
This document describes the **Active Flag** system for managing panel revisions in SmartISO. This system prevents coexistence of multiple active panel versions and provides clear visibility of which panels are currently in use.

## Implementation Details

### Database Changes
A new `is_active` column has been added to the `dbpanel` table:
- **Type**: TINYINT(1)
- **Default**: 1 (active)
- **Purpose**: Indicates whether a panel version is currently active or archived

### Migration File
`app/Database/Migrations/2025-12-01-000001_AddPanelActiveStatus.php`

Run the migration with:
```bash
php spark migrate
```

## How It Works

### 1. Active Panel Status
- **Active (Green)**: Panel is available for use in new form creation and editing
- **Inactive (Red)**: Panel is archived/draft and not available for selection

### 2. Panel Selection Dropdowns
Only **ACTIVE** panels appear in:
- Form creation modal (`/admin/dynamicforms`)
- Form editing modal (`/admin/dynamicforms`)  
- Form panel assignment in Configurations (`/admin/configurations?type=forms`)

### 3. Panel Management View
All panels (active and inactive) are visible in:
- Panel Configuration page (`/admin/dynamicforms/panel-config`)
- Configurations panels tab (`/admin/configurations?type=panels`)

### 4. Copying Panels (Revisions)
When you copy a panel:
- The **new copy is created as INACTIVE** (draft revision)
- The **original panel remains ACTIVE**
- You can edit the copy, then toggle it to Active when ready
- Remember to deactivate the old version if needed

## User Interface

### Status Toggle
In the Panel Configuration page, each panel shows:
- A status badge (green "Active" or red "Inactive")
- A toggle switch to change status

Toggle behavior:
- Click to change status instantly (AJAX, no page reload)
- Toast notification confirms the change

### Visual Indicators
| Status   | Badge Color | Toggle State |
|----------|-------------|--------------|
| Active   | Green       | ON           |
| Inactive | Red         | OFF          |

## Workflow Examples

### Creating a New Revision
1. Go to Panel Configuration
2. Click "Copy" on the existing panel
3. The copy is created with "(Copy)" suffix and **Inactive** status
4. Edit the copy as needed
5. When ready, toggle the copy to **Active**
6. Toggle the old version to **Inactive**

### Archiving a Panel
1. Go to Panel Configuration
2. Find the panel to archive
3. Click the toggle switch to set it to **Inactive**
4. Panel is now hidden from selection dropdowns

### Reactivating a Panel
1. Go to Panel Configuration  
2. Find the inactive panel (red badge)
3. Click the toggle switch to set it to **Active**
4. Panel is now available in selection dropdowns

## Files Modified

### Model
- `app/Models/DbPanelModel.php`
  - Added `is_active` to allowed fields
  - `getActivePanels()` - Returns only active panels
  - `isPanelActive($panelName)` - Check if panel is active
  - `setPanelActive($panelName, $isActive)` - Update panel status

### Controllers
- `app/Controllers/Admin/DynamicForms.php`
  - `togglePanelStatus()` - AJAX endpoint for status toggle
  - `copyPanel()` - Creates copies as inactive
  - `index()` - Uses `getActivePanels()` for dropdowns

- `app/Controllers/Admin/Configurations.php`
  - `edit()` - Uses `getActivePanels()` for form panel selection

### Views
- `app/Views/admin/dynamicforms/panel_config.php`
  - Added Status column with toggle switch
  - JavaScript for AJAX toggle functionality

### Routes
- `app/Config/Routes.php`
  - Added `POST /admin/dynamicforms/toggle-panel-status`

## Important Notes

1. **Backward Compatibility**: The system checks if `is_active` column exists before using it, ensuring no errors before migration runs.

2. **Existing Data**: All existing panels default to `is_active = 1` (active) after migration.

3. **Form Submissions**: Existing form submissions with inactive panels still work - the active flag only affects panel selection in admin interfaces.

4. **Department Admin Access**: Department admins can only manage panels within their department scope.

## Troubleshooting

### Migration Issues
If migration fails:
```bash
php spark db:status
php spark migrate:rollback
php spark migrate
```

### Toggle Not Working
1. Check browser console for JavaScript errors
2. Verify the route is registered: `php spark routes`
3. Check CSRF token is being sent

### Panels Not Showing in Dropdowns
1. Verify the panel is set to Active
2. Check database: `SELECT panel_name, is_active FROM dbpanel GROUP BY panel_name`
3. Clear CodeIgniter cache: `php spark cache:clear`
