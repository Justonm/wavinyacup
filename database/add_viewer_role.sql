-- Add 'viewer' to the user roles
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'county_admin', 'sub_county_admin', 'ward_admin', 'coach', 'captain', 'player', 'viewer') NOT NULL;
