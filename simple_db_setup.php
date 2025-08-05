<?php
// Simple database setup that creates a basic working database
echo "🔧 Setting up simple database...\n";

try {
    // Try to connect without database first
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "✅ Connected to MySQL!\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS machakos_teams");
    echo "✅ Database created!\n";
    
    // Connect to the database
    $pdo = new PDO("mysql:host=localhost;dbname=machakos_teams", "root", "");
    echo "✅ Connected to machakos_teams database!\n";
    
    // Create basic tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100),
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin', 'coach', 'player') NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            phone VARCHAR(15),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS counties (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(10) UNIQUE NOT NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS sub_counties (
            id INT PRIMARY KEY AUTO_INCREMENT,
            county_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(10) UNIQUE NOT NULL,
            FOREIGN KEY (county_id) REFERENCES counties(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS wards (
            id INT PRIMARY KEY AUTO_INCREMENT,
            sub_county_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(10) UNIQUE NOT NULL,
            FOREIGN KEY (sub_county_id) REFERENCES sub_counties(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS teams (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            ward_id INT NOT NULL,
            team_code VARCHAR(20) UNIQUE NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ward_id) REFERENCES wards(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS players (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            team_id INT,
            position ENUM('goalkeeper', 'defender', 'midfielder', 'forward') NOT NULL,
            jersey_number INT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (team_id) REFERENCES teams(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS coaches (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            team_id INT,
            license_number VARCHAR(50),
            license_type ENUM('basic', 'intermediate', 'advanced') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (team_id) REFERENCES teams(id)
        )"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    echo "✅ Basic tables created!\n";
    
    // Insert basic data
    $pdo->exec("INSERT IGNORE INTO counties (id, name, code) VALUES (1, 'Machakos', 'MCK')");
    $pdo->exec("INSERT IGNORE INTO sub_counties (id, county_id, name, code) VALUES (1, 1, 'Machakos Town', 'MCT')");
    $pdo->exec("INSERT IGNORE INTO wards (id, sub_county_id, name, code) VALUES (1, 1, 'Machakos Central', 'MCT001')");
    
    // Create admin user
    $adminPassword = password_hash('password', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (username, email, password_hash, role, first_name, last_name) VALUES ('admin', 'admin@machakoscounty.go.ke', '$adminPassword', 'admin', 'County', 'Administrator')");
    
    echo "✅ Basic data inserted!\n";
    echo "✅ Admin user created!\n";
    
    echo "\n🎉 Database setup completed!\n";
    echo "📝 Login credentials:\n";
    echo "   Username: admin\n";
    echo "   Password: password\n";
    echo "\n🌐 Test your system at:\n";
    echo "   http://localhost:8000/wavinyacup/admin/teams.php\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n💡 Try this alternative approach:\n";
    echo "1. Open a new terminal\n";
    echo "2. Run: sudo mysql -u root -p\n";
    echo "3. Enter your password when prompted\n";
    echo "4. Run: CREATE DATABASE machakos_teams;\n";
    echo "5. Run: USE machakos_teams;\n";
    echo "6. Copy and paste the schema from database/schema.sql\n";
}
?> 