-- Coach Approval System Database Updates
-- Add approval status and temporary password fields to users table

ALTER TABLE users ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved';
ALTER TABLE users ADD COLUMN temp_password VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN approved_by INT NULL;
ALTER TABLE users ADD COLUMN approved_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN rejection_reason TEXT NULL;

-- Add foreign key for approved_by
ALTER TABLE users ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- Update coaches table to include team_id reference
ALTER TABLE coaches ADD COLUMN team_id INT NULL;
ALTER TABLE coaches ADD FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL;

-- Create coach_registrations table for tracking self-registrations
CREATE TABLE coach_registrations (
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
