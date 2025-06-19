<?php
/**
 * Script to add authentication check to all PHP files
 * Run this script once to update all PHP files in the system
 * 
 * @package    Mamatid Health Center System
 * @subpackage Utilities
 * @version    1.0
 */

// Change to root directory
chdir('../../');

// List of files that should not have authentication check
$exclude_files = [
    'index.php',
    'client_login.php',
    'client_register.php',
    'client_logout.php',
    'congratulation.php',
    'system/security/unauthorized_access.php',
    'system/utilities/add_auth_check.php'
];

// List of directories to exclude
$exclude_dirs = [
    'config',
    'common_service',
    'ajax',
    'actions',
    'plugins',
    'pdflib',
    'dist',
    'system'
];

// Function to check if a file should be processed
function should_process_file($file, $exclude_files, $exclude_dirs) {
    // Get the filename without path
    $filename = basename($file);
    
    // Check if file is in exclude list
    if (in_array($file, $exclude_files)) {
        return false;
    }
    
    // Check if file is in excluded directory
    foreach ($exclude_dirs as $dir) {
        if (strpos($file, "/$dir/") !== false || strpos($file, "\\$dir\\") !== false) {
            return false;
        }
    }
    
    return true;
}

// Function to add authentication check to a PHP file
function add_auth_check($file) {
    // Read file content
    $content = file_get_contents($file);
    
    // Check if authentication check is already added
    if (strpos($content, 'require_once \'./config/check_auth.php\';') !== false) {
        echo "Authentication check already exists in $file\n";
        return;
    }
    
    // Find the opening PHP tag
    $pos = strpos($content, '<?php');
    if ($pos === false) {
        echo "No PHP opening tag found in $file\n";
        return;
    }
    
    // Add authentication check after the opening PHP tag
    $new_content = substr($content, 0, $pos + 5) . "\n// Include authentication check\nrequire_once './config/check_auth.php';\n" . substr($content, $pos + 5);
    
    // Write updated content back to file
    file_put_contents($file, $new_content);
    echo "Added authentication check to $file\n";
}

echo "Starting authentication check addition process...\n";
echo "Current directory: " . getcwd() . "\n";

// Get all PHP files in the current directory and subdirectories
$directory = new RecursiveDirectoryIterator('.');
$iterator = new RecursiveIteratorIterator($directory);
$files = new RegexIterator($iterator, '/\.php$/i', RecursiveRegexIterator::GET_MATCH);

$processed_count = 0;
$skipped_count = 0;

// Process each PHP file
foreach ($files as $file) {
    $file_path = $file[0];
    
    // Skip files that should not be processed
    if (!should_process_file($file_path, $exclude_files, $exclude_dirs)) {
        echo "Skipping file: $file_path\n";
        $skipped_count++;
        continue;
    }
    
    // Add authentication check to the file
    add_auth_check($file_path);
    $processed_count++;
}

echo "Authentication check addition completed.\n";
echo "Files processed: $processed_count\n";
echo "Files skipped: $skipped_count\n";
?> 