# Manual Test Plan: Checkbox Save/Load Verification

## Test Date: October 22, 2025
## Tester: [Your Name]
## Status: ✅ READY FOR TESTING

---

## Prerequisites
1. SmartISO system is installed and running
2. Database migrations have been run
3. You have admin access to the panel builder
4. A DOCX file with checkbox fields (C_ prefix tags) OR manual checkbox creation

---

## Test Case 1: Import DOCX with Checkboxes

### Step 1: Prepare Test DOCX File
1. Open Microsoft Word
2. Go to Developer tab → Controls
3. Add Rich Text Content Controls with these tags:
   - Tag: `C_APPROVAL_YES` (Value: "Yes")
   - Tag: `C_APPROVAL_NO` (Value: "No")
   - Tag: `C_APPROVAL_MAYBE` (Value: "Maybe")
4. Save as `test_checkboxes.docx`

### Step 2: Import to Panel Builder
1. Navigate to Admin → Configurations → Panels
2. Click "Create New Panel" → Name it "Test Checkbox Panel"
3. Open the panel in "Drag & Drop Builder"
4. Click "Import DOCX" button
5. Select `test_checkboxes.docx`
6. **Expected Result:** Modal shows "APPROVAL" checkbox group with 3 options
7. **Verify:** Options shown: "Yes", "No", "Maybe"

### Step 3: Add Fields to Panel
1. Check the "APPROVAL" checkbox group
2. Click "Add Selected Fields"
3. **Expected Result:** Field appears in builder area
4. **Verify:** You can see 3 checkboxes with labels

### Step 4: Save Panel
1. Click "Save Panel" button
2. **Expected Result:** Success notification
3. **Verify:** No errors in console

### Step 5: Verify Database Storage
Open your database tool (phpMyAdmin) and run:
```sql
SELECT field_name, field_type, default_value 
FROM dbpanel 
WHERE panel_name = 'Test Checkbox Panel' 
AND field_type = 'checkboxes';
```

**Expected Result:**
- `field_name`: approval
- `field_type`: checkboxes
- `default_value`: Should contain JSON array like:
  ```json
  [
    {"label":"Yes","sub_field":"yes"},
    {"label":"No","sub_field":"no"},
    {"label":"Maybe","sub_field":"maybe"}
  ]
  ```

### Step 6: Reload Page Test
1. Press F5 or click the panel builder link again
2. **Expected Result:** Panel loads successfully
3. **CRITICAL VERIFICATION:** ✅ Checkboxes are visible with all 3 options
4. **If checkboxes disappeared:** ❌ Test FAILED

### Step 7: Edit and Re-save
1. Click the edit icon (pencil) on the APPROVAL field
2. In the options manager, verify all 3 options are shown
3. Add a 4th option: "Not Applicable"
4. Click Save
5. Click "Save Panel" again
6. Reload page
7. **Expected Result:** 4 checkboxes now visible

---

## Test Case 2: Manually Create Checkbox Field

### Step 1: Create New Panel
1. Admin → Configurations → Panels → Create "Manual Checkbox Test"
2. Open in Drag & Drop Builder

### Step 2: Create Checkbox Field
1. In Field Configuration panel, set:
   - Field Type: Checkboxes
   - Label: "Services Required"
   - Name: "services"
   - Width: 12
2. In Options field, enter:
   ```
   Installation
   Maintenance
   Repair
   Consultation
   ```
3. Drag the field palette item into the canvas
4. **Expected Result:** Field appears with 4 checkboxes

### Step 3: Save and Reload
1. Click "Save Panel"
2. Wait for success message
3. Reload page (F5)
4. **Expected Result:** ✅ All 4 checkboxes visible

---

## Test Case 3: Edit Existing Checkbox Options

### Step 1: Load Panel with Checkboxes
1. Open any panel that has checkbox fields
2. **Expected Result:** Checkboxes are visible

### Step 2: Edit Options
1. Click the pencil icon on a checkbox field
2. Options Manager modal opens
3. Modify an option label (e.g., "Yes" → "Approved")
4. Add a new option
5. Remove an option
6. Click Save in modal
7. **Expected Result:** Field updates in builder

### Step 3: Save Panel
1. Click "Save Panel"
2. **Expected Result:** Success message

### Step 4: Verify Persistence
1. Reload page
2. Click edit on the same field
3. **Expected Result:** Your changes are persisted
4. **Verify:** Modified labels, new option, and removed option are correct

---

## Test Case 4: Different Checkbox Scenarios

### Scenario A: Single Checkbox (not checkboxes)
1. Create field with type: "checkbox" (singular)
2. Label: "I agree to terms"
3. Save and reload
4. **Expected Result:** Single checkbox, no options needed

### Scenario B: Empty Options Handling
1. Create checkboxes field
2. Don't provide any options
3. Try to save
4. **Expected Result:** Field saved with empty array
5. On reload: Field shows but no checkboxes (because no options)

### Scenario C: Options with Special Characters
1. Create checkboxes with options:
   - "Yes & No"
   - "50% Complete"
   - "Option #1"
2. Save and reload
3. **Expected Result:** Labels preserved, sub_fields sanitized

---

## Expected Results Summary

| Test Case | Before Fix | After Fix |
|-----------|-----------|-----------|
| Import DOCX → Save → Reload | ❌ Checkboxes disappeared | ✅ Checkboxes visible |
| Manual Create → Save → Reload | ❌ Checkboxes disappeared | ✅ Checkboxes visible |
| Edit Options → Save → Reload | ❌ Changes lost | ✅ Changes persisted |
| Special characters in labels | ⚠️ May break | ✅ Labels preserved |

---

## Debugging Tips

If checkboxes are missing after reload:

1. **Check Browser Console:**
   - Press F12 → Console tab
   - Look for JavaScript errors
   - Check if `window.panelFields` contains options

2. **Check Database:**
   ```sql
   SELECT * FROM dbpanel 
   WHERE panel_name = 'YOUR_PANEL_NAME' 
   AND field_type IN ('checkbox', 'checkboxes');
   ```
   - Verify `default_value` contains JSON
   - Check if JSON is valid

3. **Check Network Tab:**
   - F12 → Network tab
   - Save panel and check the request payload
   - Verify `options` array is in the payload

4. **Check PHP Logs:**
   - Location: `SmartISO/writable/logs/`
   - Look for saveFormBuilder debug messages
   - Check for JSON encoding errors

---

## Success Criteria

✅ All checkboxes visible after page reload  
✅ Options preserved after save-load cycle  
✅ Can edit options and changes persist  
✅ Database contains valid JSON  
✅ No JavaScript errors in console  
✅ No PHP errors in logs  

---

## Notes

- The fix was applied to `loadExistingFields()` method
- Options are stored as JSON in `default_value` column
- Both object and string option formats are supported
- Backward compatibility maintained with fallback parsing
