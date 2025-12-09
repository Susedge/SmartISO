-- Fix PDO Form Routing - November 23, 2025
-- This script helps fix the "all forms route to single department admin" issue

-- =============================================================================
-- DIAGNOSTIC SECTION - Run first to understand current state
-- =============================================================================

-- 1. Check all department admins and their departments
SELECT 
    u.id,
    u.username,
    u.full_name,
    u.user_type,
    d.code as dept_code,
    d.description as dept_name,
    u.active
FROM users u
LEFT JOIN departments d ON d.id = u.department_id
WHERE u.user_type = 'department_admin'
ORDER BY d.description, u.full_name;

-- 2. Find PDO department
SELECT id, code, description
FROM departments
WHERE code = 'PDO' OR description LIKE '%PDO%';

-- 3. Check which forms have specific signatories (override dept routing)
SELECT 
    f.id as form_id,
    f.code as form_code,
    f.description as form_description,
    d.description as form_department,
    COUNT(fs.user_id) as signatory_count
FROM forms f
LEFT JOIN departments d ON d.id = f.department_id
LEFT JOIN form_signatories fs ON fs.form_id = f.id
GROUP BY f.id
HAVING signatory_count > 0
ORDER BY f.description;

-- 4. See who receives notifications for specific forms
-- Replace X with the form_id from query above
SELECT 
    f.code as form_code,
    f.description as form_description,
    u.full_name as signatory_name,
    u.user_type,
    u.department_id,
    d.description as signatory_department
FROM form_signatories fs
LEFT JOIN forms f ON f.id = fs.form_id
LEFT JOIN users u ON u.id = fs.user_id
LEFT JOIN departments d ON d.id = u.department_id
-- WHERE fs.form_id = X  -- Uncomment and set form_id
ORDER BY f.code, u.full_name;

-- =============================================================================
-- FIX OPTION 1: Remove form signatories to enable department-based routing
-- =============================================================================
-- This makes forms route to ALL dept admins from submitter's department
-- Recommended if you want automatic department-based routing

-- Preview what will be deleted (safe to run)
SELECT 
    fs.id,
    f.code as form_code,
    u.full_name as current_signatory,
    u.user_type
FROM form_signatories fs
LEFT JOIN forms f ON f.id = fs.form_id
LEFT JOIN users u ON u.id = fs.user_id
WHERE f.code = 'CRSRF';  -- Change to your form code

-- UNCOMMENT TO EXECUTE: Remove signatories for specific form
-- DELETE FROM form_signatories WHERE form_id = (
--     SELECT id FROM forms WHERE code = 'CRSRF'  -- Change to your form code
-- );

-- UNCOMMENT TO EXECUTE: Remove ALL form signatories (use with caution)
-- DELETE FROM form_signatories;

-- =============================================================================
-- FIX OPTION 2: Add PDO department admin to existing form signatories
-- =============================================================================
-- This keeps existing signatories and adds PDO dept admin
-- Use if you want specific approvers PLUS dept admin

-- First, identify PDO dept admin user ID
SELECT 
    u.id as user_id,
    u.username,
    u.full_name,
    d.description as department
FROM users u
LEFT JOIN departments d ON d.id = u.department_id
WHERE u.user_type = 'department_admin'
  AND (d.code = 'PDO' OR d.description LIKE '%PDO%')
  AND u.active = 1;

-- UNCOMMENT TO EXECUTE: Add PDO dept admin to PDO forms
-- INSERT INTO form_signatories (form_id, user_id, required)
-- SELECT 
--     f.id as form_id,
--     u.id as user_id,
--     1 as required
-- FROM forms f
-- CROSS JOIN users u
-- CROSS JOIN departments d
-- WHERE f.department_id = d.id
--   AND (d.code = 'PDO' OR d.description LIKE '%PDO%')
--   AND u.user_type = 'department_admin'
--   AND u.department_id = d.id
--   AND u.active = 1
--   AND NOT EXISTS (
--       SELECT 1 FROM form_signatories fs2 
--       WHERE fs2.form_id = f.id AND fs2.user_id = u.id
--   );

-- =============================================================================
-- FIX OPTION 3: Create department admins for departments without them
-- =============================================================================

-- Check which departments have NO department admin
SELECT 
    d.id,
    d.code,
    d.description,
    COUNT(u.id) as dept_admin_count
FROM departments d
LEFT JOIN users u ON u.department_id = d.id AND u.user_type = 'department_admin' AND u.active = 1
GROUP BY d.id
HAVING dept_admin_count = 0
ORDER BY d.description;

-- UNCOMMENT TO EXECUTE: Promote existing user to department admin
-- UPDATE users 
-- SET user_type = 'department_admin'
-- WHERE id = X  -- Replace with user ID
--   AND department_id = Y;  -- Replace with department ID

-- UNCOMMENT TO EXECUTE: Create new department admin user
-- INSERT INTO users (username, password, full_name, email, user_type, department_id, office_id, active)
-- VALUES (
--     'dept_admin_pdo',  -- username
--     '$2y$10$...',  -- hashed password (generate with password_hash())
--     'PDO Department Admin',  -- full name
--     'pdo_admin@example.com',  -- email
--     'department_admin',  -- user type
--     X,  -- PDO department ID from query above
--     NULL,  -- office_id (optional)
--     1  -- active
-- );

-- =============================================================================
-- VERIFICATION QUERIES - Run after applying fixes
-- =============================================================================

-- 1. Verify form signatories after changes
SELECT 
    f.code,
    f.description,
    COUNT(fs.user_id) as signatory_count
FROM forms f
LEFT JOIN form_signatories fs ON fs.form_id = f.id
GROUP BY f.id
ORDER BY f.description;

-- 2. Verify department admin coverage
SELECT 
    d.description as department,
    COUNT(u.id) as dept_admin_count,
    GROUP_CONCAT(u.username) as dept_admins
FROM departments d
LEFT JOIN users u ON u.department_id = d.id 
    AND u.user_type = 'department_admin' 
    AND u.active = 1
GROUP BY d.id
ORDER BY d.description;

-- 3. Test notification routing simulation
-- This shows who WOULD be notified for a new submission
SELECT 
    'Form Signatories' as routing_method,
    f.code as form_code,
    u.full_name as would_be_notified,
    u.user_type,
    d.description as user_department
FROM forms f
LEFT JOIN form_signatories fs ON fs.form_id = f.id
LEFT JOIN users u ON u.id = fs.user_id
LEFT JOIN departments d ON d.id = u.department_id
WHERE f.code = 'CRSRF'  -- Change to your form code
  AND u.active = 1

UNION ALL

-- If no signatories, show dept-based routing
SELECT 
    'Department-Based (Fallback)' as routing_method,
    'All Forms Without Signatories' as form_code,
    u.full_name as would_be_notified,
    u.user_type,
    d.description as user_department
FROM users u
LEFT JOIN departments d ON d.id = u.department_id
WHERE u.user_type IN ('approving_authority', 'department_admin')
  AND u.active = 1
  -- AND u.department_id = X  -- Uncomment to filter by submitter's dept
ORDER BY routing_method, user_department, would_be_notified;

-- 4. Check recent submission notification patterns
SELECT 
    DATE(n.created_at) as date,
    u.user_type,
    d.description as notified_dept,
    COUNT(*) as notification_count
FROM notifications n
LEFT JOIN users u ON u.id = n.user_id
LEFT JOIN departments d ON d.id = u.department_id
WHERE n.title = 'New Service Request Requires Approval'
  AND n.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(n.created_at), u.user_type, d.description
ORDER BY date DESC, notified_dept;

-- =============================================================================
-- RECOMMENDED FIX SEQUENCE
-- =============================================================================
-- 
-- For PDO department routing to work correctly:
-- 
-- 1. Run DIAGNOSTIC queries to understand current setup
-- 2. Choose ONE fix option:
--    - Option 1: Remove signatories (simple, automatic dept routing)
--    - Option 2: Add PDO admin to signatories (specific control)
--    - Option 3: Create dept admins for missing departments
-- 3. Execute the chosen fix (uncomment the appropriate SQL)
-- 4. Run VERIFICATION queries to confirm fix
-- 5. Test by submitting a form and checking notifications
-- 6. Check logs: grep "Submission Notification" writable/logs/log-*.php
-- 
-- =============================================================================

-- Quick one-liner to see current issue:
SELECT 
    'Forms with signatories (override dept routing)' as info,
    COUNT(*) as count
FROM forms f
WHERE EXISTS (SELECT 1 FROM form_signatories fs WHERE fs.form_id = f.id)
UNION ALL
SELECT 
    'Departments without dept admin' as info,
    COUNT(*) as count
FROM departments d
WHERE NOT EXISTS (
    SELECT 1 FROM users u 
    WHERE u.department_id = d.id 
      AND u.user_type = 'department_admin' 
      AND u.active = 1
);
