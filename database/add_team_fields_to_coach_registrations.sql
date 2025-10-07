-- Add missing team information fields to coach_registrations table
-- This migration adds the new team fields that were added to the coach self-registration form

ALTER TABLE coach_registrations 
ADD COLUMN IF NOT EXISTS team_description TEXT NULL AFTER team_name,
ADD COLUMN IF NOT EXISTS founded_year INT NULL AFTER team_description,
ADD COLUMN IF NOT EXISTS home_ground VARCHAR(255) NULL AFTER founded_year,
ADD COLUMN IF NOT EXISTS team_colors VARCHAR(100) NULL AFTER home_ground,
ADD COLUMN IF NOT EXISTS team_logo VARCHAR(255) NULL AFTER team_colors,
ADD COLUMN IF NOT EXISTS team_photo VARCHAR(255) NULL AFTER team_logo;

-- Also need to add the missing columns to the teams table if they don't exist
ALTER TABLE teams 
ADD COLUMN IF NOT EXISTS team_description TEXT NULL AFTER name,
ADD COLUMN IF NOT EXISTS owner_name VARCHAR(100) NULL AFTER ward_id,
ADD COLUMN IF NOT EXISTS owner_id_number VARCHAR(50) NULL AFTER owner_name,
ADD COLUMN IF NOT EXISTS owner_phone VARCHAR(20) NULL AFTER owner_id_number,
ADD COLUMN IF NOT EXISTS founded_year INT NULL AFTER owner_phone,
ADD COLUMN IF NOT EXISTS home_ground VARCHAR(255) NULL AFTER founded_year,
ADD COLUMN IF NOT EXISTS team_colors VARCHAR(100) NULL AFTER home_ground,
ADD COLUMN IF NOT EXISTS logo_path VARCHAR(255) NULL AFTER team_colors;

-- Update any existing records to have default values for founded_year
UPDATE coach_registrations SET founded_year = YEAR(CURDATE()) WHERE founded_year IS NULL;
UPDATE teams SET founded_year = YEAR(CURDATE()) WHERE founded_year IS NULL;
