<?php
// Database setup script
echo "Setting up database...\n";

// Try to connect to MySQL without database first
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "Connected to MySQL successfully!\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS machakos_teams");
    echo "Database 'machakos_teams' created or already exists.\n";
    
    // Use the database
    $pdo->exec("USE machakos_teams");
    echo "Using database 'machakos_teams'.\n";
    
    // Read and execute schema
    $schema = file_get_contents('database/schema.sql');
    if ($schema) {
        $pdo->exec($schema);
        echo "Database schema imported successfully!\n";
    } else {
        echo "Could not read schema file.\n";
    }
    
    echo "Database setup completed!\n";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "Trying alternative connection...\n";
    
    // Try with different credentials
    try {
        $pdo = new PDO("mysql:host=localhost", "root", "password");
        echo "Connected with password!\n";
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS machakos_teams");
        echo "Database 'machakos_teams' created or already exists.\n";
        
        // Use the database
        $pdo->exec("USE machakos_teams");
        echo "Using database 'machakos_teams'.\n";
        
        // Read and execute schema
        $schema = file_get_contents('database/schema.sql');
        if ($schema) {
            $pdo->exec($schema);
            echo "Database schema imported successfully!\n";
        } else {
            echo "Could not read schema file.\n";
        }
        
        echo "Database setup completed!\n";
        
    } catch (PDOException $e2) {
        echo "Alternative connection also failed: " . $e2->getMessage() . "\n";
        echo "Please set up MySQL manually:\n";
        echo "1. Start MySQL: sudo systemctl start mysql\n";
        echo "2. Create database: CREATE DATABASE machakos_teams;\n";
        echo "3. Import schema: mysql machakos_teams < database/schema.sql\n";
    }
}
?> 