-- Machakos County Team Registration System Database Schema
-- FIFA-style team registration system

-- Create database
CREATE DATABASE IF NOT EXISTS machakos_teams;
USE machakos_teams;

-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'county_admin', 'sub_county_admin', 'ward_admin', 'coach', 'captain', 'player') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(15),
    id_number VARCHAR(20) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Counties table
CREATE TABLE counties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sub-counties table
CREATE TABLE sub_counties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    county_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (county_id) REFERENCES counties(id) ON DELETE CASCADE
);

-- Wards table
CREATE TABLE wards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sub_county_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sub_county_id) REFERENCES sub_counties(id) ON DELETE CASCADE
);

-- Teams table
CREATE TABLE teams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    ward_id INT NOT NULL,
    coach_id INT,
    captain_id INT,
    team_code VARCHAR(20) UNIQUE NOT NULL,
    founded_year INT,
    home_ground VARCHAR(100),
    team_colors VARCHAR(100),
    logo_path VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (captain_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Players table
CREATE TABLE players (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    team_id INT,
    position ENUM('goalkeeper', 'defender', 'midfielder', 'forward') NOT NULL,
    jersey_number INT,
    height_cm INT,
    weight_kg DECIMAL(5,2),
    date_of_birth DATE NOT NULL,
    nationality VARCHAR(50) DEFAULT 'Kenyan',
    preferred_foot ENUM('left', 'right', 'both') DEFAULT 'right',
    market_value DECIMAL(10,2) DEFAULT 0.00,
    contract_start_date DATE,
    contract_end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
);

-- Coaches table
CREATE TABLE coaches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    license_number VARCHAR(50) UNIQUE,
    license_type ENUM('basic', 'intermediate', 'advanced', 'professional') NOT NULL,
    experience_years INT DEFAULT 0,
    specialization VARCHAR(100),
    certifications TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Team registrations table
CREATE TABLE team_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    team_id INT NOT NULL,
    season_year INT NOT NULL,
    registration_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Player registrations table
CREATE TABLE player_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    player_id INT NOT NULL,
    team_id INT NOT NULL,
    season_year INT NOT NULL,
    registration_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Matches table
CREATE TABLE matches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    home_team_id INT NOT NULL,
    away_team_id INT NOT NULL,
    match_date DATETIME NOT NULL,
    venue VARCHAR(100),
    home_score INT DEFAULT 0,
    away_score INT DEFAULT 0,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'scheduled',
    referee VARCHAR(100),
    match_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (home_team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (away_team_id) REFERENCES teams(id) ON DELETE CASCADE
);

-- Player statistics table
CREATE TABLE player_statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    player_id INT NOT NULL,
    match_id INT NOT NULL,
    goals_scored INT DEFAULT 0,
    assists INT DEFAULT 0,
    yellow_cards INT DEFAULT 0,
    red_cards INT DEFAULT 0,
    minutes_played INT DEFAULT 0,
    rating DECIMAL(3,1) DEFAULT 0.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);

-- System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Machakos County data
INSERT INTO counties (name, code) VALUES ('Machakos', 'MCK');

-- Insert Machakos Sub-counties
INSERT INTO sub_counties (county_id, name, code) VALUES
(1, 'Machakos Town', 'MCT'),
(1, 'Mavoko', 'MVK'),
(1, 'Kangundo', 'KGD'),
(1, 'Kathiani', 'KTH'),
(1, 'Yatta', 'YTT'),
(1, 'Masinga', 'MSG'),
(1, 'Matungulu', 'MTL'),
(1, 'Mwala', 'MWL');

-- Insert Wards for each Sub-county
-- Machakos Town (5 wards)
INSERT INTO wards (sub_county_id, name, code) VALUES
(1, 'Machakos Central', 'MCT001'),
(1, 'Muvuti', 'MCT002'),
(1, 'Kola', 'MCT003'),
(1, 'Kalama', 'MCT004'),
(1, 'Mutituni', 'MCT005');

-- Mavoko (4 wards)
INSERT INTO wards (sub_county_id, name, code) VALUES
(2, 'Athi River', 'MVK001'),
(2, 'Kinanie', 'MVK002'),
(2, 'Muthwani', 'MVK003'),
(2, 'Syokimau', 'MVK004');

-- Kangundo (4 wards)
INSERT INTO wards (sub_county_id, name, code) VALUES
(3, 'Kangundo North', 'KGD001'),
(3, 'Kangundo Central', 'KGD002'),
(3, 'Kangundo East', 'KGD003'),
(3, 'Kangundo West', 'KGD004');

-- Kathiani (4 wards)
INSERT INTO wards (sub_county_id, name, code) VALUES
(4, 'Kathiani Central', 'KTH001'),
(4, 'Kathiani East', 'KTH002'),
(4, 'Kathiani West', 'KTH003'),
(4, 'Kathiani South', 'KTH004');

-- Yatta (4 wards)
INSERT INTO wards (sub_county_id, name, code) VALUES
(5, 'Yatta Central', 'YTT001'),
(5, 'Yatta North', 'YTT002'),
(5, 'Yatta South', 'YTT003'),
(5, 'Yatta East', 'YTT004');

-- Masinga (4 wards)
INSERT INTO wards (sub_county_id, name, code) VALUES
(6, 'Masinga Central', 'MSG001'),
(6, 'Masinga North', 'MSG002'),
(6, 'Masinga South', 'MSG003'),
(6, 'Masinga East', 'MSG004');

-- Matungulu (4 wards)
INSERT INTO wards (sub_county_id, name, code) VALUES
(7, 'Matungulu Central', 'MTL001'),
(7, 'Matungulu North', 'MTL002'),
(7, 'Matungulu South', 'MTL003'),
(7, 'Matungulu East', 'MTL004');

-- Mwala (4 wards)
INSERT INTO wards (sub_county_id, name, code) VALUES
(8, 'Mwala Central', 'MWL001'),
(8, 'Mwala North', 'MWL002'),
(8, 'Mwala South', 'MWL003'),
(8, 'Mwala East', 'MWL004');

-- Insert default admin user
INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number) VALUES
('admin', 'admin@machakoscounty.go.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'County', 'Administrator', '+254700000000', '12345678');

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'Machakos County Team Registration System', 'Website name'),
('site_description', 'FIFA-style team registration system for Machakos County', 'Website description'),
('registration_enabled', 'true', 'Whether new registrations are enabled'),
('max_players_per_team', '22', 'Maximum players allowed per team'),
('min_players_per_team', '11', 'Minimum players required per team'),
('season_year', '2024', 'Current season year'),
('contact_email', 'info@machakoscounty.go.ke', 'Contact email address'),
('contact_phone', '+254700000000', 'Contact phone number');

-- Create indexes for better performance
CREATE INDEX idx_teams_ward ON teams(ward_id);
CREATE INDEX idx_players_team ON players(team_id);
CREATE INDEX idx_registrations_team ON team_registrations(team_id);
CREATE INDEX idx_registrations_status ON team_registrations(status);
CREATE INDEX idx_player_registrations_player ON player_registrations(player_id);
CREATE INDEX idx_player_registrations_team ON player_registrations(team_id);
CREATE INDEX idx_matches_date ON matches(match_date);
CREATE INDEX idx_player_statistics_player ON player_statistics(player_id);
CREATE INDEX idx_player_statistics_match ON player_statistics(match_id); 