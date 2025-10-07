-- Corrected SQL script to add all remaining missing columns.

USE machakos_teams;

-- Add missing columns to the 'teams' table
ALTER TABLE `teams` 
ADD COLUMN `sub_county_id` INT NULL AFTER `ward_id`,
ADD COLUMN `county_id` INT NULL AFTER `sub_county_id`;

-- Add missing column to the 'players' table
ALTER TABLE `players`
ADD COLUMN `is_captain` BOOLEAN DEFAULT FALSE AFTER `user_id`;

-- Add missing columns to the 'activity_log' table
ALTER TABLE `activity_log`
ADD COLUMN `action` VARCHAR(255) NOT NULL AFTER `user_id`,
ADD COLUMN `ip_address` VARCHAR(45) AFTER `description`,
ADD COLUMN `user_agent` VARCHAR(255) AFTER `ip_address`;

-- Add foreign key constraints for new columns in 'teams' table
ALTER TABLE `teams`
ADD FOREIGN KEY (`sub_county_id`) REFERENCES `sub_counties`(`id`) ON DELETE SET NULL,
ADD FOREIGN KEY (`county_id`) REFERENCES `counties`(`id`) ON DELETE SET NULL;
