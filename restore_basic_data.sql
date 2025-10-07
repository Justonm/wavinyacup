-- Restore basic data for coach registration to work
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

-- Insert default admin user (password: password)
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
