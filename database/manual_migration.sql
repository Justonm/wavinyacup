-- Manual Migration - Run these commands one by one in your MySQL client

-- Check if columns exist first, then add missing ones
DESCRIBE users;

-- Add missing columns to users table (skip if they already exist)
-- ALTER TABLE users ADD COLUMN temp_password VARCHAR(255) NULL;
-- ALTER TABLE users ADD COLUMN approved_by INT NULL;
-- ALTER TABLE users ADD COLUMN approved_at TIMESTAMP NULL;
-- ALTER TABLE users ADD COLUMN rejection_reason TEXT NULL;

-- Check coaches table
DESCRIBE coaches;

-- Add team_id to coaches table (skip if exists)
-- ALTER TABLE coaches ADD COLUMN team_id INT NULL;

-- Create coach_registrations table
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

-- Add system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('coach_self_registration_enabled', 'true', 'Whether coaches can self-register'),
('coach_approval_required', 'true', 'Whether coach registrations require admin approval')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
