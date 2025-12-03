<?php
/**
 * Database Setup Script
 * Run ini sekali untuk setup database dan tables
 */

// Database Configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ecommerce_db';

// Koneksi ke MySQL tanpa database terlebih dahulu
try {
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to MySQL Server\n";
} catch(PDOException $e) {
    die("✗ Connection failed: " . $e->getMessage());
}

// Buat database jika belum ada
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$db_name' created or already exists\n";
} catch(PDOException $e) {
    die("✗ Error creating database: " . $e->getMessage());
}

// Koneksi ke database yang baru dibuat
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database '$db_name'\n";
} catch(PDOException $e) {
    die("✗ Connection to database failed: " . $e->getMessage());
}

// Baca file schema.sql
$schema_file = __DIR__ . '/schema.sql';
if (!file_exists($schema_file)) {
    die("✗ schema.sql not found at: $schema_file");
}

echo "\n📄 Reading schema.sql...\n";
$sql_content = file_get_contents($schema_file);

// Split SQL statements (simple parser)
$statements = array_filter(
    array_map('trim', explode(';', $sql_content)),
    function($statement) {
        return !empty($statement) && !preg_match('/^--/', $statement);
    }
);

echo "Found " . count($statements) . " SQL statements\n\n";

// Execute each statement
$count = 0;
foreach ($statements as $statement) {
    try {
        $pdo->exec($statement . ';');
        $count++;
        echo "✓ Executed statement $count\n";
    } catch(PDOException $e) {
        echo "✗ Error executing statement $count: " . $e->getMessage() . "\n";
        echo "   SQL: " . substr($statement, 0, 100) . "...\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ DATABASE SETUP COMPLETE!\n";
echo str_repeat("=", 50) . "\n\n";

// Verify tables
$tables_query = $pdo->query("SHOW TABLES");
$tables = $tables_query->fetchAll(PDO::FETCH_COLUMN);

echo "📊 Created Tables:\n";
foreach ($tables as $table) {
    $count_query = $pdo->query("SELECT COUNT(*) FROM `$table`");
    $count = $count_query->fetchColumn();
    echo "  • $table ($count rows)\n";
}

echo "\n✅ Ready to use! Homepage: http://localhost/ecommerce/index.php\n";
?>
