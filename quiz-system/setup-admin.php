<?php
/**
 * Admin Setup Script
 * Run this once to create admin credentials
 * Usage: php setup-admin.php
 */

echo "=== Quiz System Admin Setup ===\n\n";

// Check requirements first
echo "Checking system requirements...\n";

if (!extension_loaded('pdo')) {
    die("Error: PDO extension is not installed.\n" .
        "Install it with: sudo apt-get install php-pdo\n\n");
}

if (!extension_loaded('pdo_sqlite') && !extension_loaded('pdo_mysql')) {
    die("Error: Neither PDO SQLite nor PDO MySQL is installed.\n\n" .
        "To install SQLite:\n" .
        "  Ubuntu/Debian: sudo apt-get install php-sqlite3\n" .
        "  CentOS/RHEL: sudo yum install php-pdo\n\n" .
        "Or use MySQL:\n" .
        "  1. Install: sudo apt-get install mysql-server php-mysql\n" .
        "  2. Run: php setup-mysql.php\n\n");
}

if (!extension_loaded('pdo_sqlite')) {
    echo "⚠️  Warning: SQLite not available, but MySQL is.\n";
    echo "   Run 'php setup-mysql.php' to configure MySQL instead.\n";
    echo "   Or install SQLite with: sudo apt-get install php-sqlite3\n\n";
    exit(1);
}

echo "✓ Requirements check passed\n\n";

// Get username
echo "Enter admin username: ";
$username = trim(fgets(STDIN));

if (empty($username)) {
    die("Error: Username cannot be empty\n");
}

// Get password
echo "Enter admin password: ";
$password = trim(fgets(STDIN));

if (empty($password)) {
    die("Error: Password cannot be empty\n");
}

if (strlen($password) < 8) {
    die("Error: Password must be at least 8 characters\n");
}

// Confirm password
echo "Confirm password: ";
$confirmPassword = trim(fgets(STDIN));

if ($password !== $confirmPassword) {
    die("Error: Passwords do not match\n");
}

// Generate password hash
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// For .htpasswd, we need crypt-based hash
$htpasswdHash = crypt($password, base64_encode($password));

// Create .htpasswd file
$htpasswdPath = __DIR__ . '/admin/.htpasswd';
$htpasswdContent = $username . ':' . $htpasswdHash;

if (file_put_contents($htpasswdPath, $htpasswdContent) !== false) {
    chmod($htpasswdPath, 0644);
    echo "\n✓ Admin credentials created successfully!\n";
    echo "  File: $htpasswdPath\n";
    echo "  Username: $username\n";
    echo "\nYou can now access the admin panel at /admin/\n";
} else {
    die("\n✗ Error: Could not create .htpasswd file\n");
}

// Create initial database and sample data
echo "\nInitializing database...\n";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$db = Database::getInstance();

// Create a sample topic
try {
    $topicId = $db->createTopic('General Knowledge', 'General knowledge questions');
    echo "✓ Sample topic created (ID: $topicId)\n";
} catch (Exception $e) {
    echo "  Note: Sample topic may already exist\n";
}

echo "\n=== Setup Complete! ===\n";
echo "Navigate to /admin/ to start managing your quizzes.\n\n";

?>
