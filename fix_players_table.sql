-- Fix players table schema to match registration form
USE machakos_teams;

-- Add missing columns to players table
ALTER TABLE players 
ADD COLUMN first_name VARCHAR(50) NOT NULL AFTER team_id,
ADD COLUMN last_name VARCHAR(50) NOT NULL AFTER first_name,
ADD COLUMN gender ENUM('male', 'female') NOT NULL AFTER last_name,
ADD COLUMN player_image VARCHAR(255) NULL AFTER preferred_foot,
ADD COLUMN id_photo_front VARCHAR(255) NULL AFTER player_image,
ADD COLUMN id_photo_back VARCHAR(255) NULL AFTER id_photo_front,
ADD COLUMN created_by INT NULL AFTER id_photo_back;

-- Add foreign key for created_by
ALTER TABLE players 
ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Show the updated table structure
DESCRIBE players;
