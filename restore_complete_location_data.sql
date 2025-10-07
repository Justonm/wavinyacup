-- Restore Complete Location Data for Machakos County
-- This script restores all sub counties and wards data

-- First, ensure we have the county data
INSERT IGNORE INTO counties (id, name, code) VALUES 
(1, 'Machakos', 'MACH');

-- Insert Sub Counties
INSERT IGNORE INTO sub_counties (id, name, code, county_id) VALUES
(1, 'Machakos Town', 'MACH_TOWN', 1),
(2, 'Kangundo', 'KANG', 1),
(3, 'Matungulu', 'MATU', 1),
(4, 'Kathiani', 'KATH', 1),
(5, 'Mavoko', 'MAVO', 1),
(6, 'Masinga', 'MASI', 1),
(7, 'Yatta', 'YATT', 1),
(8, 'Mwala', 'MWAL', 1);

-- Insert Wards for each Sub County

-- Machakos Town Sub County Wards
INSERT IGNORE INTO wards (id, name, code, sub_county_id) VALUES
(1, 'Kalama', 'KALA', 1),
(2, 'Kola', 'KOLA', 1),
(3, 'Machakos Central', 'MACH_CENT', 1),
(4, 'Mumbuni North', 'MUMB_N', 1),
(5, 'Mumbuni South', 'MUMB_S', 1),
(6, 'Mutituni', 'MUTI', 1),
(7, 'Muvuti/Kiima-Kimwe', 'MUVU_KIIM', 1);

-- Kangundo Sub County Wards
INSERT IGNORE INTO wards (id, name, code, sub_county_id) VALUES
(8, 'Kangundo North', 'KANG_N', 2),
(9, 'Kangundo Central', 'KANG_CENT', 2),
(10, 'Kangundo East', 'KANG_E', 2),
(11, 'Kangundo West', 'KANG_W', 2);

-- Matungulu Sub County Wards
INSERT IGNORE INTO wards (id, name, code, sub_county_id) VALUES
(12, 'Matungulu North', 'MATU_N', 3),
(13, 'Matungulu West', 'MATU_W', 3),
(14, 'Matungulu East', 'MATU_E', 3),
(15, 'Ekalakala', 'EKAL', 3),
(16, 'Kyeleni', 'KYEL', 3);

-- Kathiani Sub County Wards
INSERT IGNORE INTO wards (id, name, code, sub_county_id) VALUES
(17, 'Kathiani Central', 'KATH_CENT', 4),
(18, 'Lower Kaewa/Kaani', 'LOW_KAEW', 4),
(19, 'Upper Kaewa/Iveti', 'UPP_KAEW', 4),
(20, 'Mitaboni', 'MITA', 4);

-- Mavoko Sub County Wards
INSERT IGNORE INTO wards (id, name, code, sub_county_id) VALUES
(21, 'Athi River', 'ATHI', 5),
(22, 'Kinanie', 'KINA', 5),
(23, 'Muthwani', 'MUTH', 5),
(24, 'Syokimau/Mulolongo', 'SYOK_MUL', 5);

-- Masinga Sub County Wards
INSERT IGNORE INTO wards (id, name, code, sub_county_id) VALUES
(25, 'Masinga Central', 'MASI_CENT', 6),
(26, 'Ekalakala', 'EKAL_MAS', 6),
(27, 'Muthesya', 'MUTH_MAS', 6),
(28, 'Ndithini', 'NDIT', 6),
(29, 'Kivaa', 'KIVA', 6);

-- Yatta Sub County Wards
INSERT IGNORE INTO wards (id, name, code, sub_county_id) VALUES
(30, 'Ikombe', 'IKOM', 7),
(31, 'Katangi', 'KATA', 7),
(32, 'Kithimani', 'KITH', 7),
(33, 'Matuu', 'MATU_YAT', 7),
(34, 'Ndalani', 'NDAL', 7),
(35, 'Yatta/Kithimani', 'YATT_KITH', 7);

-- Mwala Sub County Wards
INSERT IGNORE INTO wards (id, name, code, sub_county_id) VALUES
(36, 'Kibauni', 'KIBA', 8),
(37, 'Makutano/Mwala', 'MAKU_MWAL', 8),
(38, 'Masii', 'MASI_MWAL', 8),
(39, 'Mbiuni', 'MBIU', 8),
(40, 'Wamunyu', 'WAMU', 8);

-- Create default admin user if not exists
INSERT IGNORE INTO users (id, first_name, last_name, email, password_hash, role, approval_status, created_at) VALUES
(1, 'System', 'Administrator', 'admin@governorwavinyacup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved', NOW());

-- Insert system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('app_name', 'Governor Wavinya Cup', 'Application name'),
('registration_open', '1', 'Whether registration is open'),
('max_players_per_team', '25', 'Maximum players per team'),
('tournament_year', '2024', 'Current tournament year');

-- Reset AUTO_INCREMENT values to continue from the last inserted ID
ALTER TABLE sub_counties AUTO_INCREMENT = 9;
ALTER TABLE wards AUTO_INCREMENT = 41;
ALTER TABLE users AUTO_INCREMENT = 2;
