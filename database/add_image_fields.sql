-- Add image fields to existing tables
-- Run this after the main schema.sql

USE machakos_teams;

-- Add profile_image field to users table
ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) AFTER phone;

-- Add player_image field to players table
ALTER TABLE players ADD COLUMN player_image VARCHAR(255) AFTER preferred_foot;

-- Add coach_image field to coaches table
ALTER TABLE coaches ADD COLUMN coach_image VARCHAR(255) AFTER specialization;

-- Add team_photo field to teams table (in addition to existing logo_path)
ALTER TABLE teams ADD COLUMN team_photo VARCHAR(255) AFTER logo_path;

-- Create uploads directory structure (this will be handled by PHP)
-- The system will create: uploads/teams/, uploads/players/, uploads/coaches/ 