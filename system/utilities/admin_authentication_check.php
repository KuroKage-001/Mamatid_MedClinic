<?php
/**
 * Comprehensive Authentication Standardization Script
 * This script standardizes authentication across all admin PHP files
 * - Replaces manual authentication checks with centralized approach
 * - Adds authentication to files that don't have it
 * - Maintains same functionality while improving maintainability
 * 
 * @package    Mamatid Health Center System
 * @subpackage Utilities
 * @version    2.0
 */

// Get the absolute path to the root directory
$rootDir = dirname(dirname(__DIR__));

// Change to root directory
chdir($rootDir);

// Files that should not have authentication check
$excludeFiles = [
    'index.php',
    'client_login.php',
    'client_register.php',
    'client_logout.php',
    'logout.php',
    'system/utilities/congratulation.php',
    'system/security/admin_client_unauthorized_access_control.php',
    'system/utilities/admin_authentication_check.php',
    'system/utilities/client_authentication_check.php'
];

// Root level admin files that need authentication
$rootLevelFiles = [
    'update_user.php',
    'update_general_rbs.php',
    'patient_history.php',
    'medicine_stock.php',
    'medicine_categories.php',
    'medicines.php',
    'general_tetanus_toxoid.php',
    'general_rbs.php',
    'general_family_planning.php',
    'general_family_members.php',
    'general_bp_monitoring.php',
    'general_deworming.php',
    'admin_users_management.php',
    'admin_schedule_plotter.php',
    'admin_report_management.php',
    'admin_hw_schedule_plotter.php',
    'admin_doctor_schedule_plotter.php',
    'admin_appointment_management.php',
    'account_admin_settings.php',
    'admin_employee_time_tracker.php'
];

// Files in subdirectories that need authentication
$subDirectoryFiles = [
    'actions/archive_appointment.php',
    'actions/archive_bp_monitoring.php',
    'actions/archive_deworming.php',
    'actions/archive_family_member.php',
    'actions/archive_family_planning.php',
    'actions/archive_general_rbs.php',
    'actions/archive_tetanus_toxoid.php',
    'actions/unarchive_appointment.php',
    'actions/unarchive_bp_monitoring.php',
    'actions/unarchive_deworming.php',
    'actions/unarchive_family_member.php',
    'actions/unarchive_family_planning.php',
    'actions/unarchive_general_rbs.php',
    'actions/unarchive_tetanus_toxoid.php',
    'actions/delete_schedule.php',
    'actions/admin_book_walkin_appointment.php',
    'actions/admin_switch_account.php',
    'actions/admin_approve_all_doctor_schedules.php',
    'actions/delete_user.php',
    'ajax/get_providers.php',
    'ajax/get_available_slots.php',
    'ajax/admin_check_user_status.php',
    'config/admin_sidebar.php'
];

// Function to determine the correct path to check_auth.php based on file location
function getAuthPath($file) {
    if (strpos($file, 'actions/') === 0 || strpos($file, 'ajax/') === 0) {
        return '../system/utilities/check_auth.php';
    } elseif (strpos($file, 'config/') === 0) {
        return '../system/utilities/check_auth.php';
    } else {
        return './system/utilities/check_auth.php';
    }
}

// Function to replace manual authentication with centralized approach
function replaceManualAuth($file) {
    $content = file_get_contents($file);
    $authPath = getAuthPath($file);
    $originalContent = $content;
    
    // Check if centralized auth is already present
    if (strpos($content, "require_once '$authPath';") !== false) {
        echo "Centralized authentication already exists in $file\n";
        return false;
    }
    
    // Pattern to match manual authentication blocks
    $patterns = [
        // Pattern 1: Basic manual auth check
        '/if\s*\(\s*!\s*isset\s*\(\s*\$_SESSION\s*\[\s*[\'"]user_id[\'"]\s*\]\s*\)\s*\)\s*\{\s*.*?header\s*\(\s*[\'"]location\s*:\s*[^\'\"]*[\'"]\s*\)\s*;\s*.*?exit\s*;\s*\}/s',
        
        // Pattern 2: Auth check with empty() condition
        '/if\s*\(\s*!\s*isset\s*\(\s*\$_SESSION\s*\[\s*[\'"]user_id[\'"]\s*\]\s*\)\s*\|\|\s*empty\s*\(\s*\$_SESSION\s*\[\s*[\'"]user_id[\'"]\s*\]\s*\)\s*\)\s*\{\s*.*?header\s*\(\s*[\'"]location\s*:\s*[^\'\"]*[\'"]\s*\)\s*;\s*.*?exit\s*;\s*\}/s'
    ];
    
    $replaced = false;
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            // Remove the manual auth check
            $content = preg_replace($pattern, '', $content);
            $replaced = true;
            break;
        }
    }
    
    // If we found and removed manual auth, add centralized auth
    if ($replaced || strpos($originalContent, 'if (!isset($_SESSION[\'user_id\'])') !== false) {
        // Find the opening PHP tag
        $pos = strpos($content, '<?php');
        if ($pos !== false) {
            // Add centralized authentication check after the opening PHP tag
            $newContent = substr($content, 0, $pos + 5) . 
                         "\n// Include authentication check\nrequire_once '$authPath';\n" . 
                         substr($content, $pos + 5);
            
            // Clean up any duplicate newlines
            $newContent = preg_replace('/\n{3,}/', "\n\n", $newContent);
            
            file_put_contents($file, $newContent);
            echo "Replaced manual authentication with centralized approach in $file\n";
            return true;
        }
    }
    
    return false;
}

// Function to add authentication to files that don't have any
function addAuthentication($file) {
    $content = file_get_contents($file);
    $authPath = getAuthPath($file);
    
    // Check if any authentication already exists
    if (strpos($content, "require_once '$authPath';") !== false || 
        strpos($content, 'if (!isset($_SESSION[\'user_id\'])') !== false) {
        return false;
    }
    
    // Find the opening PHP tag
    $pos = strpos($content, '<?php');
    if ($pos !== false) {
        // Add authentication check after the opening PHP tag
        $newContent = substr($content, 0, $pos + 5) . 
                     "\n// Include authentication check\nrequire_once '$authPath';\n" . 
                     substr($content, $pos + 5);
        
        file_put_contents($file, $newContent);
        echo "Added authentication check to $file\n";
        return true;
    }
    
    return false;
}

echo "Starting comprehensive authentication standardization...\n";
echo "Current directory: " . getcwd() . "\n\n";

$processed_count = 0;
$skipped_count = 0;
$error_count = 0;

// Process root level files
echo "Processing root level admin files...\n";
foreach ($rootLevelFiles as $file) {
    if (file_exists($file) && !in_array($file, $excludeFiles)) {
        try {
            if (replaceManualAuth($file) || addAuthentication($file)) {
                $processed_count++;
            } else {
                echo "No changes needed for $file\n";
                $skipped_count++;
            }
        } catch (Exception $e) {
            echo "Error processing $file: " . $e->getMessage() . "\n";
            $error_count++;
        }
    } else {
        echo "File not found or excluded: $file\n";
        $skipped_count++;
    }
}

// Process subdirectory files
echo "\nProcessing subdirectory files...\n";
foreach ($subDirectoryFiles as $file) {
    if (file_exists($file) && !in_array($file, $excludeFiles)) {
        try {
            if (replaceManualAuth($file) || addAuthentication($file)) {
                $processed_count++;
            } else {
                echo "No changes needed for $file\n";
                $skipped_count++;
            }
        } catch (Exception $e) {
            echo "Error processing $file: " . $e->getMessage() . "\n";
            $error_count++;
        }
    } else {
        echo "File not found or excluded: $file\n";
        $skipped_count++;
    }
}

echo "\nAuthentication standardization completed.\n";
echo "Files processed: $processed_count\n";
echo "Files skipped: $skipped_count\n";
echo "Errors encountered: $error_count\n";

if ($processed_count > 0) {
    echo "\nSUCCESS: Authentication has been standardized across all admin files.\n";
    echo "All files now use the centralized authentication system while maintaining the same functionality.\n";
}
?> 