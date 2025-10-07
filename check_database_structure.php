<?php
// check_database_structure.php - Check actual database structure
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['check']) || $_GET['check'] !== 'db2025') {
    die('Access denied');
}

require_once __DIR__ . '/config/config.php';

$db = db();

echo "<h2>Database Structure Check</h2>";

// Check coach_registrations table structure
echo "<h3>coach_registrations table:</h3>";
try {
    $columns = $db->query("DESCRIBE coach_registrations");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Check coaches table structure
echo "<h3>coaches table:</h3>";
try {
    $columns = $db->query("DESCRIBE coaches");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Check users table structure
echo "<h3>users table:</h3>";
try {
    $columns = $db->query("DESCRIBE users");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Sample data from coach_registrations
echo "<h3>Sample coach_registrations data:</h3>";
try {
    $sample = $db->query("SELECT * FROM coach_registrations LIMIT 3");
    if (count($sample) > 0) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (array_keys($sample[0]) as $key) {
            echo "<th>{$key}</th>";
        }
        echo "</tr>";
        foreach ($sample as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No data found";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
