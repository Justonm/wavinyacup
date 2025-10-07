-- Add consent tracking to players and users tables

-- Add to players table
ALTER TABLE `players`
ADD COLUMN `consent_given_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp when the player gave data processing consent';

-- Add to users table (for coaches, captains, etc.)
ALTER TABLE `users`
ADD COLUMN `consent_given_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp when the user gave data processing consent';

-- It's good practice to also add an index if you plan to query this field often,
-- though it's not strictly necessary for just tracking.
-- CREATE INDEX idx_consent_given_at ON players(consent_given_at);
-- CREATE INDEX idx_user_consent_given_at ON users(consent_given_at);
