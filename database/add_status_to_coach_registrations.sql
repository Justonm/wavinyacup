-- Add status column to coach_registrations table
ALTER TABLE `coach_registrations`
ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'Registration status: pending, approved, rejected' AFTER `ward_id`;
