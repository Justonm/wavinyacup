-- SQL Commands to Fix Coach Ward Assignment
-- Run these commands directly in your database

-- 1. First, let's check the current state
SELECT 'Current Muthetheni ward info:' as info;
SELECT w.id, w.name, w.code, w.sub_county_id, sc.name as sub_county_name 
FROM wards w 
JOIN sub_counties sc ON w.sub_county_id = sc.id 
WHERE w.name = 'Muthetheni';

-- 2. Check Mwala sub-county ID
SELECT 'Mwala sub-county info:' as info;
SELECT id, name, code FROM sub_counties WHERE name = 'Mwala';

-- 3. Update Muthetheni ward to be in Mwala sub-county (assuming Mwala has ID 8)
UPDATE wards 
SET sub_county_id = 8, code = 'MUTH_MWL' 
WHERE name = 'Muthetheni';

-- 4. Check coach ID 65 current registration
SELECT 'Coach 65 current registration:' as info;
SELECT cr.*, w.name as ward_name, sc.name as sub_county_name
FROM coach_registrations cr
LEFT JOIN wards w ON cr.ward_id = w.id
LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id
WHERE cr.user_id = 65;

-- 5. Get the updated Muthetheni ward ID
SELECT 'Updated Muthetheni ward:' as info;
SELECT id, name, code, sub_county_id FROM wards WHERE name = 'Muthetheni';

-- 6. Update or insert coach registration for coach ID 65 with Muthetheni ward
-- First, get the Muthetheni ward ID
SET @muthetheni_ward_id = (SELECT id FROM wards WHERE name = 'Muthetheni');

-- Update existing registration or insert new one
INSERT INTO coach_registrations (user_id, ward_id, status, created_at, updated_at)
VALUES (65, @muthetheni_ward_id, 'approved', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
ward_id = @muthetheni_ward_id, 
updated_at = NOW();

-- 7. Verify the final result
SELECT 'Final verification:' as info;
SELECT 
    u.id as coach_id,
    u.first_name,
    u.last_name,
    w.name as ward_name,
    sc.name as sub_county_name,
    cr.status
FROM users u
JOIN coach_registrations cr ON u.id = cr.user_id
JOIN wards w ON cr.ward_id = w.id
JOIN sub_counties sc ON w.sub_county_id = sc.id
WHERE u.id = 65;
