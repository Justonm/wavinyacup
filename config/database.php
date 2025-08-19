<?php
/**
 * Database Configuration Constants
 *
 * This file sets up database connection constants from environment variables.
 * The actual database connection logic is in `includes/db.php`.
 */

// Load environment variables using Dotenv
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Set database constants from .env or fallback defaults
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'machakos_teams');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'Tyron@1.');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');