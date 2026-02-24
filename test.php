<?php
/**
 * Quick test script for Visitor Management System
 * Run: php test.php
 */

echo "=== SAP Visitor Management System Test ===\n\n";

// Test 1: Check required files
echo "1. Checking required files...\n";
$required_files = [
    'index.php',
    'checkin.php',
    'checkout.php',
    'confirmation.php',
    'includes/config.php',
    'includes/db.php',
    'includes/helpers.php',
    'api/checkin.php',
    'api/checkout.php',
    'admin/index.php',
    'admin/dashboard.php',
    'sql/schema.sql'
];

$missing = [];
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $missing[] = $file;
    }
}

if (empty($missing)) {
    echo "   ✓ All required files present\n";
} else {
    echo "   ✗ Missing files: " . implode(', ', $missing) . "\n";
}

// Test 2: Check PHP version
echo "\n2. Checking PHP version...\n";
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    echo "   ✓ PHP version " . PHP_VERSION . " (OK)\n";
} else {
    echo "   ✗ PHP version " . PHP_VERSION . " (Requires 8.0+)\n";
}

// Test 3: Check required extensions
echo "\n3. Checking required extensions...\n";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$missing_ext = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_ext[] = $ext;
    }
}

if (empty($missing_ext)) {
    echo "   ✓ All required extensions loaded\n";
} else {
    echo "   ✗ Missing extensions: " . implode(', ', $missing_ext) . "\n";
}

// Test 4: Check database connection (if configured)
echo "\n4. Checking database configuration...\n";
if (file_exists('.env')) {
    echo "   ✓ .env file exists\n";

    // Try to connect
    try {
        require_once 'includes/config.php';
        require_once 'includes/db.php';
        $db = getDB();
        echo "   ✓ Database connection successful\n";

        // Test query
        $stmt = $db->query("SELECT 1");
        echo "   ✓ Database query successful\n";

    } catch (Exception $e) {
        echo "   ✗ Database error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ! .env file not found (copy .env.example to .env)\n";
}

// Test 5: Check syntax of key files
echo "\n5. Checking PHP syntax...\n";
$files_to_check = ['index.php', 'checkin.php', 'api/checkin.php', 'includes/helpers.php'];
$syntax_errors = [];
foreach ($files_to_check as $file) {
    $output = [];
    $return = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);
    if ($return !== 0) {
        $syntax_errors[] = $file . ": " . implode(" ", $output);
    }
}

if (empty($syntax_errors)) {
    echo "   ✓ No syntax errors found\n";
} else {
    foreach ($syntax_errors as $error) {
        echo "   ✗ $error\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nTo start the application:\n";
echo "  1. Configure database in .env file\n";
echo "  2. Import sql/schema.sql to MySQL\n";
echo "  3. Run: php -S localhost:8000\n";
echo "  4. Open: http://localhost:8000\n";
