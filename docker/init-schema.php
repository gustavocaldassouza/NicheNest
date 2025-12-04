<?php
/**
 * Database Schema Initialization Script
 * Checks if tables exist and creates them if not
 */

$maxRetries = 30;
$retryDelay = 2;

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'nichenest';
$dbUser = getenv('DB_USER') ?: 'nichenest';
$dbPass = getenv('DB_PASS') ?: 'nichenest123';

echo "Database Schema Initializer\n";
echo "===========================\n";
echo "Host: $dbHost, Database: $dbName\n\n";

// Wait for database connection
$pdo = null;
for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
    try {
        echo "Attempt $attempt/$maxRetries: Connecting to database...\n";
        $pdo = new PDO(
            "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        echo "✓ Connected to database!\n\n";
        break;
    } catch (PDOException $e) {
        echo "✗ Connection failed: " . $e->getMessage() . "\n";
        if ($attempt < $maxRetries) {
            echo "  Retrying in {$retryDelay}s...\n";
            sleep($retryDelay);
        }
    }
}

if (!$pdo) {
    echo "\n✗ FATAL: Could not connect to database after $maxRetries attempts\n";
    exit(1);
}

// Check if users table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $result->rowCount() > 0;
    
    if ($tableExists) {
        echo "✓ Tables already exist, skipping schema initialization\n";
        exit(0);
    }
    
    echo "✗ Tables not found, initializing schema...\n\n";
} catch (PDOException $e) {
    echo "Error checking tables: " . $e->getMessage() . "\n";
    exit(1);
}

// Load and execute schema
$schemaFile = '/var/www/html/data/schema.sql';
if (!file_exists($schemaFile)) {
    echo "✗ FATAL: Schema file not found: $schemaFile\n";
    exit(1);
}

$schema = file_get_contents($schemaFile);
echo "Loading schema from: $schemaFile\n";
echo "Schema size: " . strlen($schema) . " bytes\n\n";

// Split by semicolons (handling multi-line statements)
// Remove comments and split statements
$schema = preg_replace('/--.*$/m', '', $schema);
$schema = preg_replace('/\/\*.*?\*\//s', '', $schema);

$statements = array_filter(
    array_map('trim', explode(';', $schema)),
    fn($s) => !empty($s)
);

echo "Executing " . count($statements) . " SQL statements...\n\n";

$success = 0;
$failed = 0;

foreach ($statements as $i => $statement) {
    try {
        $pdo->exec($statement);
        $success++;
        // Show first 50 chars of statement
        $preview = substr(preg_replace('/\s+/', ' ', $statement), 0, 60);
        echo "  ✓ " . ($i + 1) . ": $preview...\n";
    } catch (PDOException $e) {
        $failed++;
        $preview = substr(preg_replace('/\s+/', ' ', $statement), 0, 40);
        echo "  ✗ " . ($i + 1) . ": $preview... - " . $e->getMessage() . "\n";
    }
}

echo "\n===========================\n";
echo "Schema initialization complete!\n";
echo "Success: $success, Failed: $failed\n";

// Verify tables were created
$result = $pdo->query("SHOW TABLES");
$tables = $result->fetchAll(PDO::FETCH_COLUMN);
echo "\nTables in database: " . implode(', ', $tables) . "\n";

exit($failed > 0 ? 1 : 0);
