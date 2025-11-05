<?php
/**
 * System Requirements Checker
 * Verifies all required PHP extensions are installed
 */

echo "=== Quiz System Requirements Check ===\n\n";

$requirements = [
    'PHP Version' => [
        'test' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'current' => PHP_VERSION,
        'required' => '7.4.0 or higher'
    ],
    'PDO Extension' => [
        'test' => extension_loaded('pdo'),
        'current' => extension_loaded('pdo') ? 'Installed' : 'Missing',
        'required' => 'Required'
    ],
    'PDO SQLite Driver' => [
        'test' => extension_loaded('pdo_sqlite'),
        'current' => extension_loaded('pdo_sqlite') ? 'Installed' : 'Missing',
        'required' => 'Required (or use MySQL)'
    ],
    'JSON Extension' => [
        'test' => extension_loaded('json'),
        'current' => extension_loaded('json') ? 'Installed' : 'Missing',
        'required' => 'Required'
    ],
    'Session Support' => [
        'test' => function_exists('session_start'),
        'current' => function_exists('session_start') ? 'Available' : 'Missing',
        'required' => 'Required'
    ],
    'File Permissions' => [
        'test' => is_writable(__DIR__ . '/data') && is_writable(__DIR__ . '/logs'),
        'current' => is_writable(__DIR__ . '/data') && is_writable(__DIR__ . '/logs') ? 'Writable' : 'Not writable',
        'required' => 'data/ and logs/ must be writable'
    ]
];

$allPassed = true;

foreach ($requirements as $name => $check) {
    $status = $check['test'] ? '✓' : '✗';
    $color = $check['test'] ? '' : '';

    echo sprintf("%-25s %s %s\n", $name . ':', $status, $check['current']);

    if (!$check['test']) {
        $allPassed = false;
        echo "  Required: {$check['required']}\n";
    }
}

echo "\n";

if (!$allPassed) {
    echo "❌ Some requirements are not met. Please fix the issues above.\n\n";

    echo "=== Installation Instructions ===\n\n";

    // SQLite specific
    if (!extension_loaded('pdo_sqlite')) {
        echo "To install PDO SQLite:\n\n";
        echo "Ubuntu/Debian:\n";
        echo "  sudo apt-get update\n";
        echo "  sudo apt-get install php-sqlite3\n";
        echo "  sudo systemctl restart apache2\n\n";

        echo "CentOS/RHEL:\n";
        echo "  sudo yum install php-pdo\n";
        echo "  sudo systemctl restart httpd\n\n";

        echo "macOS (Homebrew):\n";
        echo "  brew install php\n";
        echo "  # SQLite is usually included\n\n";

        echo "Alternatively, you can use MySQL instead:\n";
        echo "  1. Install MySQL: sudo apt-get install mysql-server php-mysql\n";
        echo "  2. Run: php setup-mysql.php\n";
        echo "  3. Follow the prompts to configure MySQL\n\n";
    }

    // File permissions
    if (!is_writable(__DIR__ . '/data') || !is_writable(__DIR__ . '/logs')) {
        echo "To fix file permissions:\n";
        echo "  chmod 777 data logs assets/qrcodes\n\n";
    }

    exit(1);
}

echo "✅ All requirements met! You can now run setup-admin.php\n";
echo "\nNext steps:\n";
echo "  php setup-admin.php\n\n";

?>
