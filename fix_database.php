<?php
/**
 * Intelligent Database Migration Script v2
 *
 * This script connects to the database using credentials from the .env file
 * and safely applies all necessary schema changes. It checks for the existence
 * of tables and columns before attempting to create them.
 */

echo "🚀 Starting database migration...\n";

// --- .env File Loader ---
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("The .env file is missing at path: $path");
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(str_replace('"', '', $value)); // Remove quotes from value
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

try {
    // Load .env from the project root
    loadEnv(__DIR__ . '/.env');

    // --- Database Connection ---
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbName = $_ENV['DB_NAME'] ?? 'machakos_teams';
    $dbUser = $_ENV['DB_USER'] ?? 'root';
    $dbPass = $_ENV['DB_PASS'] ?? '';
    $dbCharset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

    if (empty($dbUser)) {
        throw new Exception("DB_USER not found in .env file.");
    }

    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=$dbCharset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    echo "✅ Database connection successful.\n";

} catch (Exception $e) {
    echo "❌ Configuration or Connection Error: " . $e->getMessage() . "\n";
    exit(1);
}

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE :column");
        $stmt->execute(['column' => $column]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function tableExists($pdo, $table) {
    try {
        $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
    } catch (Exception $e) {
        return false;
    }
    return true;
}

// --- MIGRATION TASKS ---
$migrations = [
    "ALTER TABLE `users` ADD COLUMN `last_login` TIMESTAMP NULL DEFAULT NULL" => !columnExists($pdo, 'users', 'last_login'),
    "ALTER TABLE `users` ADD COLUMN `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved'" => !columnExists($pdo, 'users', 'approval_status'),
    "ALTER TABLE `users` ADD COLUMN `temp_password` VARCHAR(255) NULL" => !columnExists($pdo, 'users', 'temp_password'),
    "ALTER TABLE `users` ADD COLUMN `approved_by` INT NULL" => !columnExists($pdo, 'users', 'approved_by'),
    "ALTER TABLE `users` ADD COLUMN `approved_at` TIMESTAMP NULL" => !columnExists($pdo, 'users', 'approved_at'),
    "ALTER TABLE `users` ADD COLUMN `rejection_reason` TEXT NULL" => !columnExists($pdo, 'users', 'rejection_reason'),
    "ALTER TABLE `users` ADD COLUMN `profile_image_path` VARCHAR(255) NULL" => !columnExists($pdo, 'users', 'profile_image_path'),
    "ALTER TABLE `coaches` ADD COLUMN `team_id` INT NULL" => !columnExists($pdo, 'coaches', 'team_id'),
    "ALTER TABLE `players` ADD COLUMN `player_image_path` VARCHAR(255) NULL" => !columnExists($pdo, 'players', 'player_image_path'),
    "ALTER TABLE `players` ADD COLUMN `id_card_image_path` VARCHAR(255) NULL" => !columnExists($pdo, 'players', 'id_card_image_path'),
    "ALTER TABLE `teams` ADD COLUMN `team_image_path` VARCHAR(255) NULL" => !columnExists($pdo, 'teams', 'team_image_path'),
    "CREATE TABLE `activity_log` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `action` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `ip_address` VARCHAR(45),
        `user_agent` VARCHAR(255),
        `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    )" => !tableExists($pdo, 'activity_log')
];

echo "🔧 Applying necessary migrations...\n";
$migrationsApplied = 0;

foreach ($migrations as $query => $shouldRun) {
    if ($shouldRun) {
        try {
            $pdo->exec($query);
            echo "  -> Applied: " . substr(preg_replace('/\\s+/', ' ', $query), 0, 80) . "...\n";
            $migrationsApplied++;
        } catch (PDOException $e) {
            echo "  -> ❌ Error applying migration: " . $e->getMessage() . "\n";
        }
    }
}

if ($migrationsApplied > 0) {
    echo "✅ Applied $migrationsApplied new migration(s).\n";
} else {
    echo "✅ Database schema is already up to date.\n";
}

echo "🎉 Migration script finished.\n";
?>