<?php
/**
 * Script to add client authentication check to client-specific PHP files
 * Run this script once to update all client PHP files in the system
 * 
 * @package    Mamatid Health Center System
 * @subpackage Utilities
 * @version    1.0
 */

// Get the absolute path to the root directory
$rootDir = dirname(dirname(__DIR__));

// Change to root directory
chdir($rootDir);

// List of client files that should have client authentication check
$clientFiles = [
    'client_dashboard.php',
    'client_appointment_booking.php',
    'account_client_settings.php'
];

// List of files that should not have client authentication check
$excludeFiles = [
    'client_login.php',
    'client_register.php',
    'client_logout.php'
];

// Function to add client authentication check to a PHP file
function add_client_auth_check($file) {
    // Read file content
    $content = file_get_contents($file);
    
    // Check if client authentication check is already added
    if (strpos($content, 'require_once \'./system/utilities/check_client_auth.php\';') !== false) {
        echo "Client authentication check already exists in $file\n";
        return;
    }
    
    // Find the opening PHP tag
    $pos = strpos($content, '<?php');
    if ($pos === false) {
        echo "No PHP opening tag found in $file\n";
        return;
    }
    
    // Add client authentication check after the opening PHP tag
    $newContent = substr($content, 0, $pos + 5) . "\n// Include client authentication check\nrequire_once './system/utilities/check_client_auth.php';\n" . substr($content, $pos + 5);
    
    // Write updated content back to file
    file_put_contents($file, $newContent);
    echo "Added client authentication check to $file\n";
}

echo "Starting client authentication check addition process...\n";
echo "Current directory: " . getcwd() . "\n";

$processed_count = 0;
$skipped_count = 0;

// Process each client file
foreach ($clientFiles as $file) {
    if (file_exists($file) && !in_array($file, $excludeFiles)) {
        add_client_auth_check($file);
        $processed_count++;
    } else {
        echo "Skipping file: $file (not found or excluded)\n";
        $skipped_count++;
    }
}

echo "Client authentication check addition completed.\n";
echo "Files processed: $processed_count\n";
echo "Files skipped: $skipped_count\n";
?> 