ALTER TABLE `team_registrations`
ADD COLUMN `owner_consent_given_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp when consent was given for the team owner data';
