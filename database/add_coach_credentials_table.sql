-- Add coach_credentials table to store generated login credentials
CREATE TABLE IF NOT EXISTS coach_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL,
    password_plain VARCHAR(255) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES coach_registrations(id) ON DELETE CASCADE,
    INDEX idx_coach_id (coach_id),
    INDEX idx_email (email)
);
