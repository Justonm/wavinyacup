-- Coach Approval System Database Updates (Safe Version)
-- Add approval status and temporary password fields to users table (only if they don't exist)

-- Add columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
ADD COLUMN IF NOT EXISTS temp_password VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS approved_by INT NULL,
ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL;

-- Add foreign key for approved_by (only if it doesn't exist)
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = 'machakos_teams' 
                  AND TABLE_NAME = 'users' 
                  AND CONSTRAINT_NAME LIKE '%approved_by%');

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE users ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL', 
    'SELECT "Foreign key already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update coaches table to include team_id reference (only if it doesn't exist)
ALTER TABLE coaches ADD COLUMN IF NOT EXISTS team_id INT NULL;

-- Add foreign key for coaches.team_id (only if it doesn't exist)
SET @fk_exists2 = (SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
                   WHERE TABLE_SCHEMA = 'machakos_teams' 
                   AND TABLE_NAME = 'coaches' 
                   AND CONSTRAINT_NAME LIKE '%team_id%');

SET @sql2 = IF(@fk_exists2 = 0, 
    'ALTER TABLE coaches ADD FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL', 
    'SELECT "Foreign key already exists" as message');

PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Create coach_registrations table for tracking self-registrations (only if it doesn't exist)
CREATE TABLE IF NOT EXISTS coach_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    team_name VARCHAR(100) NOT NULL,
    ward_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Update system settings for coach registration
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('coach_self_registration_enabled', 'true', 'Whether coaches can self-register'),
('coach_approval_required', 'true', 'Whether coach registrations require admin approval')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
