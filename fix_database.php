<?php
// Simple database fix script
echo "🔧 Fixing database connection...\n";

// Try to connect to MySQL with different methods
$connections = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'password'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'password']
];

$pdo = null;
foreach ($connections as $conn) {
    try {
        $pdo = new PDO("mysql:host={$conn['host']}", $conn['user'], $conn['pass']);
        echo "✅ Connected to MySQL using {$conn['user']}@{$conn['host']}\n";
        break;
    } catch (PDOException $e) {
        echo "❌ Failed: {$conn['user']}@{$conn['host']} - " . $e->getMessage() . "\n";
    }
}

if (!$pdo) {
    echo "\n🚨 Cannot connect to MySQL. Please try these steps:\n";
    echo "1. Start MySQL: sudo systemctl start mysql\n";
    echo "2. Reset MySQL password: sudo mysql_secure_installation\n";
    echo "3. Or create a .env file with correct database credentials\n";
    exit(1);
}

// Create database
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS machakos_teams");
    echo "✅ Database 'machakos_teams' created/verified\n";
    
    $pdo->exec("USE machakos_teams");
    echo "✅ Using database 'machakos_teams'\n";
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "📦 Importing database schema...\n";
        $schema = file_get_contents('database/schema.sql');
        
        // Remove the CREATE DATABASE and USE statements since we're already connected
        $schema = preg_replace('/CREATE DATABASE.*?;/i', '', $schema);
        $schema = preg_replace('/USE.*?;/i', '', $schema);
        
        // Split and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    echo "⚠️  Warning: " . $e->getMessage() . "\n";
                }
            }
        }
        echo "✅ Database schema imported!\n";
    } else {
        echo "✅ Database tables already exist\n";
    }
    
    // Verify admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        echo "👤 Creating admin user...\n";
        $pdo->exec("INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, id_number) VALUES ('admin', 'admin@machakoscounty.go.ke', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'County', 'Administrator', '+254700000000', '12345678')");
        echo "✅ Admin user created!\n";
    } else {
        echo "✅ Admin user already exists\n";
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "📝 Default admin credentials:\n";
    echo "   Username: admin\n";
    echo "   Password: password\n";
    echo "\n🌐 Your system should now work at:\n";
    echo "   http://localhost:8000/wavinyacup/admin/teams.php\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?> 