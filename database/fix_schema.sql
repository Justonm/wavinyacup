-- Combined Schema Fixes for Wavinya Cup


-- 1. Add missing columns to the 'users' table for login tracking and coach approval.
ALTER TABLE users 
ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER is_active,
ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER last_login,
ADD COLUMN temp_password VARCHAR(255) NULL AFTER approval_status,
ADD COLUMN approved_by INT NULL AFTER temp_password,
ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by,
ADD COLUMN rejection_reason TEXT NULL AFTER approved_at,
ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- 2. Create the 'activity_log' table for user action tracking.
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. Add 'team_id' to the 'coaches' table.
ALTER TABLE coaches 
ADD COLUMN team_id INT NULL AFTER user_id,
ADD FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL;

-- 4. Add image path columns to 'users', 'players', and 'teams' tables.
ALTER TABLE users ADD COLUMN profile_image_path VARCHAR(255) NULL;
ALTER TABLE players ADD COLUMN player_image_path VARCHAR(255) NULL;
ALTER TABLE players ADD COLUMN id_card_image_path VARCHAR(255) NULL;
ALTER TABLE teams ADD COLUMN team_image_path VARCHAR(255) NULL;

