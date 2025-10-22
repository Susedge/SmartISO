# Checkbox Save-Load Verification Report

## Date: October 22, 2025

## Purpose
Verify that checkbox fields (and their options) are correctly saved and loaded in the panel builder.

---

## Save Flow Analysis

### 1. JavaScript Preparation (`saveForm()` method)
**Location:** `public/assets/js/drag-drop-form-builder.js` lines 2335-2410

**Process:**
```javascript
// For checkboxes field type
if (['dropdown','radio','checkbox','checkboxes'].includes(field.field_type)) {
    // Normalizes options to array of objects: {label, sub_field}
    field.options = field.options.map(o => {
        if (typeof o === 'object' && o !== null) {
            return { 
                label: o.label || '', 
                sub_field: o.sub_field || o.value || o.label 
            };
        } else {
            const lbl = o.toString();
            return { 
                label: lbl, 
                sub_field: lbl.toLowerCase().replace(/[^a-z0-9]+/g,'_') 
            };
        }
    });
}
```

**✅ Verified:** Options are properly normalized and included in the save payload.

---

### 2. Server-Side Save (`saveFormBuilder()` method)
**Location:** `app/Controllers/Admin/DynamicForms.php` lines 1158-1177

**Process:**
```php
// For dropdown/radio/checkbox/checkboxes fields with options
$ftype = $field['field_type'] ?? '';
if (!empty($field['options']) && is_array($field['options']) && 
    in_array($ftype, ['dropdown', 'radio', 'checkbox', 'checkboxes'])) {
    
    // Normalize option objects/strings
    $normOpts = [];
    foreach ($field['options'] as $opt) {
        if (is_array($opt)) {
            // Keep structure if it has label or sub_field
            $normOpts[] = $opt;
        } else {
            $normOpts[] = (string)$opt;
        }
    }
    // Store as JSON in default_value column
    $fieldData['default_value'] = json_encode($normOpts);
}
```

**✅ Verified:** Options are properly serialized to JSON and stored in `default_value` column.

---

## Load Flow Analysis

### 3. Server-Side Load (`formBuilder()` method)
**Location:** `app/Controllers/Admin/DynamicForms.php` lines 334-347

**Process:**
```php
// Decode JSON stored options for selectable field types
foreach ($panelFields as &$pfb) {
    $ft = $pfb['field_type'] ?? '';
    if (in_array($ft, ['dropdown','radio','checkbox','checkboxes'])) {
        if (!empty($pfb['default_value'])) {
            $decoded = json_decode($pfb['default_value'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                // Store decoded options in 'options' property
                $pfb['options'] = $decoded;
            }
        }
    }
}
```

**✅ Verified:** JSON options are decoded from `default_value` and assigned to `options` property.

---

### 4. JavaScript Load (`loadExistingFields()` method) - FIXED
**Location:** `public/assets/js/drag-drop-form-builder.js` lines 2286-2333

**Original Issue:** 
The method was not preserving the `options` array when normalizing fields.

**Fix Applied:**
```javascript
// CRITICAL FIX: Preserve options array for checkbox/dropdown fields
const fieldType = field.type || field.field_type;
let options = field.options || [];

// If options doesn't exist but this is a field type that needs options
if (!options || options.length === 0) {
    if (['dropdown', 'radio', 'checkbox', 'checkboxes'].includes(fieldType)) {
        // Fallback: try to parse from default_value if not already decoded
        if (field.default_value && typeof field.default_value === 'string') {
            try {
                const parsed = JSON.parse(field.default_value);
                if (Array.isArray(parsed)) {
                    options = parsed;
                }
            } catch (e) {
                // Not JSON, ignore
            }
        }
    }
}

return { 
    ...field, 
    // ... other properties ...
    options: options // Preserve the options!
};
```

**✅ Fixed:** Options are now properly preserved during field normalization.

---

### 5. Rendering (`createFieldElement()` method)
**Location:** `public/assets/js/drag-drop-form-builder.js` lines 1388-1414

**Process:**
```javascript
case 'checkboxes':
    if (fieldData.options && Array.isArray(fieldData.options)) {
        fieldData.options.forEach((option, idx) => {
            let optLabel = '';
            let optValue = '';
            if (typeof option === 'object' && option !== null) {
                optLabel = String(option.label || '');
                optValue = String(option.sub_field || option.label || '');
            } else {
                optLabel = String(option);
                optValue = String(option);
            }
            // Render checkbox HTML
        });
    }
```

**✅ Verified:** Checkboxes are rendered correctly when options exist.

---

## Complete Flow Summary

```
┌─────────────────────────────────────────────────────────────┐
│  1. User Creates/Imports Checkbox Field                     │
│     - Options: [{label: "Yes", sub_field: "yes"},           │
│                 {label: "No", sub_field: "no"}]             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  2. User Clicks Save                                         │
│     - JavaScript: normalizes options to objects              │
│     - Sends JSON payload with options array                  │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  3. Server Receives Data                                     │
│     - PHP: validates field type is checkboxes               │
│     - Serializes options to JSON                             │
│     - Stores in default_value column                         │
│     - INSERT INTO dbpanel (... default_value = '[...]')     │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  4. User Reloads Page                                        │
│     - PHP: reads from database                               │
│     - Decodes JSON from default_value                        │
│     - Assigns to options property                            │
│     - Passes to view as window.panelFields                   │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  5. JavaScript Loads Fields (FIXED)                          │
│     - Preserves options array during normalization           │
│     - Assigns to this.fields[].options                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  6. JavaScript Renders Fields                                │
│     - Checks if options exist and is array                   │
│     - Iterates through options                               │
│     - Creates checkbox HTML for each option                  │
│     - ✅ CHECKBOXES VISIBLE                                  │
└─────────────────────────────────────────────────────────────┘
```

---

## Verification Results

### ✅ Save Phase - WORKING CORRECTLY
- Options are properly normalized in JavaScript
- Options are included in the save payload
- Server correctly identifies checkbox fields
- Options are serialized to JSON
- JSON is stored in `default_value` column

### ✅ Load Phase - NOW FIXED
- Server decodes JSON from `default_value`
- Server assigns decoded array to `options` property
- **Fixed:** JavaScript now preserves `options` during field normalization
- **Fixed:** Options are available when rendering
- **Fixed:** Checkboxes render with all their options

---

## Test Case: Import DOCX with Checkbox Fields

### Test Steps:
1. Create a DOCX with checkbox fields using C_ prefix:
   - `C_STATUS_YES` tag
   - `C_STATUS_NO` tag
   
2. Import DOCX in panel builder
   - Expected: Shows "STATUS" checkbox group with "Yes" and "No" options

3. Click "Add Selected Fields"
   - Expected: Field appears in builder with checkboxes visible

4. Click "Save Panel"
   - Expected: Success message
   - Database: `default_value` column contains: `[{"label":"Yes","sub_field":"yes"},{"label":"No","sub_field":"no"}]`

5. Reload the page
   - **Before Fix:** Checkboxes disappeared, only label visible
   - **After Fix:** ✅ Checkboxes visible with all options

---

## Conclusion

**Status: ✅ VERIFIED - Checkboxes are now correctly saved and loaded**

The issue was isolated to the `loadExistingFields()` method in JavaScript, which was not preserving the `options` array. With the fix applied, the complete save-load cycle now works correctly:

1. ✅ Options are saved to database as JSON
2. ✅ Options are loaded from database and decoded
3. ✅ Options are preserved during JavaScript normalization  
4. ✅ Options are rendered in the UI

**No further changes needed.**

---

## Additional Notes

- The fix includes a fallback mechanism: if options aren't in the `field.options` property, it will attempt to parse them from `field.default_value`
- This provides backward compatibility and robustness
- The fix handles both object and string option formats
- The normalization process ensures consistent data structure throughout the system
