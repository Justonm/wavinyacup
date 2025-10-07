<?php
/**
 * =========================================================================
 * Unified Database Setup & Migration Script (v2 - Dynamic)
 * =========================================================================
 * This script automates the entire database setup and migration process.
 *
 * It performs the following actions:
 * 1. Loads database credentials from the `.env` file.
 * 2. Connects to MySQL and creates the database if it doesn't exist.
 * 3. Creates a `migrations` table to track applied SQL scripts.
 * 4. Executes `database/schema.sql` as the foundational schema.
 * 5. Scans the `database/` directory for all other `.sql` files.
 * 6. Applies each migration file in alphabetical order, skipping those already run.
 * =========================================================================
 */

header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); // 10 minutes max execution time

echo "🚀 Starting Unified Database Setup & Migration Script...\n\n";

// --- .env File Loader ---
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("The .env file is missing at path: $path");
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(str_replace(['"', "'"], '', $value));
        if (!getenv($name)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// --- Main Execution Block ---
try {
    // Load .env from the project root
    loadEnv(__DIR__ . '/.env');

    // --- Database Connection ---
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbName = getenv('DB_NAME') ?: 'machakos_teams';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS') ?: '';
    $dbCharset = getenv('DB_CHARSET') ?: 'utf8mb4';

    if (empty($dbUser)) {
        throw new Exception("DB_USER not found in .env file.");
    }

    // Connect without a database to create it
    $pdo = new PDO("mysql:host=$dbHost;charset=$dbCharset", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
    echo "✅ Database '$dbName' created or already exists.\n";

    // Reconnect to the specific database
    $pdo->exec("USE `$dbName`");
    echo "✅ Database connection successful.\n";

    // --- Migration Management ---
    // Create migrations table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ 'migrations' table is ready.\n";

    // Fetch applied migrations
    $appliedMigrations = $pdo->query("SELECT `migration` FROM `migrations`")->fetchAll(PDO::FETCH_COLUMN);

    // --- SQL Execution Function ---
    function executeSqlFile($pdo, $filePath, $migrationName, $appliedMigrations) {
        if (in_array($migrationName, $appliedMigrations)) {
            echo "  -> Skipping already applied migration: $migrationName\n";
            return false;
        }

        echo "  -> Applying migration: $migrationName...\n";
        $sql = file_get_contents($filePath);
        if (empty(trim($sql))) {
            echo "     -> ⚠️ Warning: Empty SQL file. Skipping.\n";
            $pdo->prepare("INSERT INTO `migrations` (`migration`) VALUES (?)")->execute([$migrationName]);
            return true;
        }

        // Split statements, respecting semicolons inside quotes
        $statements = preg_split('/;\s*(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/', $sql);

        try {
            $pdo->beginTransaction();
            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $pdo->exec($statement);
                }
            }
            $pdo->prepare("INSERT INTO `migrations` (`migration`) VALUES (?)")->execute([$migrationName]);
            $pdo->commit();
            echo "     -> ✅ Success.\n";
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "     -> ❌ ERROR: " . $e->getMessage() . "\n";
            // Optionally, re-throw or exit on critical error
            // exit(1);
            return false;
        }
    }

    // --- STEP 1: Import Base Schema ---
    echo "\n🔧 Step 1: Processing `database/schema.sql`...\n";
    $schemaPath = __DIR__ . '/database/schema.sql';
    if (file_exists($schemaPath)) {
        executeSqlFile($pdo, $schemaPath, 'schema.sql', $appliedMigrations);
    } else {
        echo "  -> ⚠️ `database/schema.sql` not found. Skipping base schema import.\n";
    }

    // --- STEP 2: Apply All Other Migrations ---
    echo "\n🔧 Step 2: Applying all other migrations from `database/` directory...\n";
    $migrationsDir = __DIR__ . '/database/';
    $sqlFiles = glob($migrationsDir . '*.sql');
    sort($sqlFiles); // Ensure alphabetical order

    $migrationsApplied = 0;
    foreach ($sqlFiles as $file) {
        $migrationName = basename($file);
        if ($migrationName === 'schema.sql' || $migrationName === 'setup_database.sql') {
            continue; // Skip schema file as it's handled, and setup_database.sql is for manual setup
        }
        if (executeSqlFile($pdo, $file, $migrationName, $appliedMigrations)) {
            $migrationsApplied++;
        }
        // Refresh applied migrations list after each run
        $appliedMigrations = $pdo->query("SELECT `migration` FROM `migrations`")->fetchAll(PDO::FETCH_COLUMN);
    }

    if ($migrationsApplied > 0) {
        echo "\n✅ Applied $migrationsApplied new migration(s).\n";
    } else {
        echo "\n✅ Database schema is already up-to-date.\n";
    }

    echo "\n🎉🎉🎉 Database setup and migration completed successfully! 🎉🎉🎉\n";

} catch (Exception $e) {
    echo "\n❌ A critical error occurred: " . $e->getMessage() . "\n";
    exit(1);
}
?>