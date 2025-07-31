-- Create database and user for Machakos County Team Registration System
CREATE DATABASE IF NOT EXISTS machakos_teams;
CREATE USER IF NOT EXISTS 'machakos_user'@'localhost' IDENTIFIED BY 'machakos123';
GRANT ALL PRIVILEGES ON machakos_teams.* TO 'machakos_user'@'localhost';
FLUSH PRIVILEGES;
EXIT; 