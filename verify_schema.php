<?php
// verify_schema.php

echo "🚀 Starting database schema verification...\n";

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

function tableExists($pdo, $table) {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = :tablename");
        $stmt->execute(['dbname' => $_ENV['DB_NAME'] ?? 'machakos_teams', 'tablename' => $table]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = :tablename AND COLUMN_NAME = :columnname");
        $stmt->execute([
            'dbname' => $_ENV['DB_NAME'] ?? 'machakos_teams',
            'tablename' => $table,
            'columnname' => $column
        ]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // This can happen if the table itself doesn't exist
        return false;
    }
}

$schema = [
    'users' => [
        'id', 'username', 'email', 'password_hash', 'role', 'first_name', 'last_name', 'phone', 'id_number', 'is_active', 'created_at', 'updated_at', 'last_login', 'approval_status', 'temp_password', 'approved_by', 'approved_at', 'rejection_reason', 'profile_image_path'
    ],
    'coaches' => [
        'id', 'user_id', 'team_id'
    ],
    'teams' => [
        'id', 'name', 'ward_id', 'sub_county_id', 'county_id', 'captain_id', 'coach_id', 'created_at', 'team_image_path'
    ],
    'players' => [
        'id', 'user_id', 'team_id', 'date_of_birth', 'position', 'is_captain', 'player_image_path', 'id_card_image_path'
    ],
    'activity_log' => [
        'id', 'user_id', 'action', 'description', 'ip_address', 'user_agent', 'timestamp'
    ]
];

$errors = 0;
$warnings = 0;
echo "🧐 Verifying schema...\n";

// --- DIAGNOSTIC: List all tables ---
echo "\n📋 Listing all tables found in database...\n";
$allTablesStmt = $pdo->query("SHOW TABLES");
$allTables = $allTablesStmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($allTables)) {
    echo "  -> No tables found.\n";
} else {
    foreach ($allTables as $tableName) {
        echo "  -> Found table: $tableName\n";
    }
}
echo "----------------------------------------\n\n";

foreach ($schema as $table => $columns) {
    if (tableExists($pdo, $table)) {
        echo "  ✅ Table `$table` exists.\n";
        foreach ($columns as $column) {
            if (columnExists($pdo, $table, $column)) {
                // echo "    ✅ Column `$column` exists.\n";
            } else {
                echo "    ❌ Column `$column` is MISSING in table `$table`.\n";
                $errors++;
            }
        }
    } else {
        // Check if this is an old table name that was replaced
        $isOld = false;
        if ($table === 'coaches' && !tableExists($pdo, 'coaches') && tableExists($pdo, 'coach_users')) {
            echo "  ⚠️  Warning: Table `coaches` not found, but legacy table `coach_users` exists. Consider migrating data.\n";
            $warnings++;
            $isOld = true;
        }

        if(!$isOld) {
            echo "  ❌ Table `$table` is MISSING.\n";
            $errors++;
        }
    }
}

echo "\n";

if ($errors == 0) {
    echo "🎉 Success! Your database schema is correct.\n";
} else {
    echo "🚨 Found $errors error(s) in your database schema. Please fix them before proceeding.\n";
}

if ($warnings > 0) {
    echo "🔍 Found $warnings warning(s). Please review them.\n";
}

echo "\nScript finished.\n";
?>
