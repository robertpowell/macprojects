<?php
/**
 * MySQL Database Setup Script
 * Alternative to SQLite for systems without pdo_sqlite
 */

echo "=== Quiz System MySQL Setup ===\n\n";

// Check if PDO MySQL is available
if (!extension_loaded('pdo_mysql')) {
    die("Error: PDO MySQL extension is not installed.\n" .
        "Install it with: sudo apt-get install php-mysql\n\n");
}

echo "This script will configure the system to use MySQL instead of SQLite.\n\n";

// Get database details
echo "MySQL Host [localhost]: ";
$host = trim(fgets(STDIN)) ?: 'localhost';

echo "MySQL Port [3306]: ";
$port = trim(fgets(STDIN)) ?: '3306';

echo "Database Name [quiz_system]: ";
$dbName = trim(fgets(STDIN)) ?: 'quiz_system';

echo "MySQL Username: ";
$username = trim(fgets(STDIN));

if (empty($username)) {
    die("Error: Username cannot be empty\n");
}

echo "MySQL Password: ";
$password = trim(fgets(STDIN));

echo "\nTesting connection...\n";

try {
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Connected to MySQL server\n";

    // Create database if it doesn't exist
    echo "Creating database '$dbName'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created/verified\n";

    // Select database
    $pdo->exec("USE `$dbName`");

    // Update db.php to use MySQL
    echo "\nUpdating database configuration...\n";

    $dbPhpPath = __DIR__ . '/includes/db.php';
    $dbPhpContent = file_get_contents($dbPhpPath);

    // Replace SQLite connection with MySQL
    $mysqlConnection = <<<PHP
        try {
            \$dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
            \$this->db = new PDO(\$dsn, '$username', '$password');
            \$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            \$this->initDatabase();
        } catch (PDOException \$e) {
            die('Database connection failed: ' . \$e->getMessage());
        }
PHP;

    $pattern = '/try\s*\{.*?new PDO.*?\}/s';
    $dbPhpContent = preg_replace($pattern, $mysqlConnection, $dbPhpContent, 1);

    // Backup original
    copy($dbPhpPath, $dbPhpPath . '.sqlite.bak');
    file_put_contents($dbPhpPath, $dbPhpContent);

    echo "✓ Database configuration updated\n";
    echo "  (Original saved as db.php.sqlite.bak)\n";

    // Create tables
    echo "\nInitializing database tables...\n";
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/db.php';

    $db = Database::getInstance();
    echo "✓ Database tables created\n";

    // Create sample topic
    try {
        $topicId = $db->createTopic('General Knowledge', 'General knowledge questions');
        echo "✓ Sample topic created\n";
    } catch (Exception $e) {
        echo "  Note: Sample topic may already exist\n";
    }

    echo "\n=== MySQL Setup Complete! ===\n";
    echo "\nConnection Details:\n";
    echo "  Host: $host:$port\n";
    echo "  Database: $dbName\n";
    echo "  Username: $username\n";
    echo "\nNow run: php setup-admin.php\n\n";

} catch (PDOException $e) {
    die("\n✗ Error: " . $e->getMessage() . "\n\n");
}

?>
