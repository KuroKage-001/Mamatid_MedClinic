# Mamatid Health Center System Documentation

## System Structure

### Directory Structure

The Mamatid Health Center System is organized into the following directory structure:

```
Mamatid_MedClinic/
├── actions/           # PHP scripts that perform specific actions (delete, update, etc.)
├── ajax/              # AJAX handlers for asynchronous requests
├── common_service/    # Common functions and services used throughout the system
├── config/            # Configuration files, headers, footers, and sidebars
├── database/          # Database schema and SQL files
├── dist/              # Compiled and minified assets
├── pdflib/            # PDF generation libraries
├── plugins/           # Third-party plugins and libraries
├── reports/           # Report generation scripts
├── system/            # System core files
│   ├── database/      # Database connection and configuration
│   ├── security/      # Security-related files
│   └── utilities/     # Utility scripts for system maintenance
├── system_styles/     # Custom CSS styles for the system
└── user_images/       # User profile images and uploads
```

### System Directory

The `system` directory contains core files that are essential to the system's operation but are not directly part of the application's functionality:

#### Database

The `system/database` directory contains database-related files:

- **connection.php**: Database connection configuration and setup.

#### Security

The `system/security` directory contains files related to system security:

- **unauthorized_access.php**: Displayed when a user tries to access a protected page without authentication (401 error).
- **access_denied.php**: Displayed when a logged-in user tries to access a page without proper permissions (403 error).
- **session_config.php**: Configuration for secure sessions and session management.
- **session_fix.php**: Ensures session variables are properly set to prevent errors.

#### Utilities

The `system/utilities` directory contains utility scripts for system maintenance:

- **add_auth_check.php**: A utility script to add authentication checks to PHP files.

### Configuration Directory

The `config` directory contains configuration files and bridge files that maintain backward compatibility:

- **check_auth.php**: Authentication check file included in all protected pages.
- **connection.php**: Bridge file that includes the actual connection file from system/database.
- **session_config.php**: Bridge file that includes the actual session config from system/security.
- **session_fix.php**: Bridge file that includes the actual session fix from system/security.
- **header.php**, **footer.php**, **sidebar.php**: Layout templates.
- **site_css_links.php**, **site_js_links.php**: CSS and JavaScript includes.

### Bridge Files

The system uses bridge files to maintain backward compatibility while improving the directory structure. Bridge files are simple PHP files that include the actual implementation from a different location. This approach allows us to:

1. **Improve Organization**: Move files to more appropriate directories based on their function.
2. **Maintain Compatibility**: Existing code continues to work without requiring changes to include paths.
3. **Simplify Maintenance**: Core functionality is centralized in the system directory.

Bridge files are located in the original locations (e.g., `config/connection.php`) and include the actual implementation from the new location (e.g., `system/database/connection.php`).

## Security Implementation

The system implements security through several layers:

1. **PHP Authentication**: The `check_auth.php` file checks for valid sessions.
2. **Apache Rules**: The `.htaccess` file provides an additional layer of security.
3. **Database Security**: Prepared statements are used to prevent SQL injection.
4. **Session Security**: Secure session handling through `session_config.php` with session timeout and regeneration.
5. **Role-Based Access Control**: The `access_denied.php` page enforces role-based permissions.
6. **Password Encryption**: User passwords are encrypted using MD5 (Note: For production, consider using more secure methods).

## Concurrent User Support

The system **ALREADY SUPPORTS** multiple concurrent users logging in simultaneously. Here's why and how it works:

### Current Concurrent User Support

1. **Separate Session Spaces**: 
   - Admin/Staff: `$_SESSION['user_id']`, `$_SESSION['role']`
   - Clients: `$_SESSION['client_id']`, `$_SESSION['client_name']`

2. **Independent Sessions**: Each user gets their own PHP session ID and data

3. **Proper Logout**: Each user logout only affects their own session

## Production Deployment Guide

### Server Configuration

```apache
# .htaccess additions for production
<IfModule mod_headers.c>
    # Security headers
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>

# Session security
php_value session.cookie_httponly 1
php_value session.cookie_secure 1
php_value session.use_only_cookies 1
```

### PHP Configuration (php.ini)

```ini
# Session Management for Concurrent Users
session.save_handler = files
session.save_path = "/tmp/sessions"
session.gc_maxlifetime = 1800
session.gc_probability = 1
session.gc_divisor = 1000
session.cookie_lifetime = 0
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1

# Memory and Performance
memory_limit = 256M
max_execution_time = 30
max_input_time = 60
post_max_size = 32M
upload_max_filesize = 32M

# Database connections
mysql.default_socket = /var/lib/mysql/mysql.sock
pdo_mysql.default_socket = /var/lib/mysql/mysql.sock
```

### Database Optimization

```sql
-- Add indexes for better concurrent performance
ALTER TABLE users ADD INDEX idx_username_status (user_name, status);
ALTER TABLE clients ADD INDEX idx_email_status (email);
ALTER TABLE appointments ADD INDEX idx_patient_date (patient_name, appointment_date);
ALTER TABLE bp_monitoring ADD INDEX idx_name_date (name, date);
ALTER TABLE family_members ADD INDEX idx_name_date (name, date);

-- Optimize session storage (optional - for high traffic)
CREATE TABLE sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    session_data MEDIUMTEXT,
    last_access TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_last_access (last_access)
);
```

### Security Enhancements

Create `config/security.php`:

```php
<?php
// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting for login attempts
function checkLoginAttempts($identifier) {
    $max_attempts = 5;
    $lockout_time = 900; // 15 minutes
    
    if (!isset($_SESSION['login_attempts'][$identifier])) {
        $_SESSION['login_attempts'][$identifier] = ['count' => 0, 'time' => time()];
    }
    
    $attempts = $_SESSION['login_attempts'][$identifier];
    
    if ($attempts['count'] >= $max_attempts && (time() - $attempts['time']) < $lockout_time) {
        return false; // Account locked
    }
    
    if ((time() - $attempts['time']) > $lockout_time) {
        $_SESSION['login_attempts'][$identifier] = ['count' => 0, 'time' => time()];
    }
    
    return true;
}
?>
```

### Performance Optimization

```php
// config/performance.php
<?php
// Enable output buffering
ob_start();

// Enable compression
if (!ob_get_level()) {
    ob_start("ob_gzhandler");
}

// Cache static resources
header('Cache-Control: public, max-age=86400');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
?>
```

### Testing Concurrent Users

1. **Local Testing**:
   ```bash
   # Open multiple browser windows/tabs
   # Login with different users simultaneously
   # Verify each maintains independent session
   ```

2. **Load Testing**:
   ```bash
   # Use tools like Apache Bench
   ab -n 100 -c 10 http://yoursite.com/dashboard.php
   ```

### Monitoring Concurrent Sessions

Add to your admin dashboard:

```php
// Count active sessions
$active_sessions = glob(session_save_path() . '/sess_*');
$session_count = count($active_sessions);

// Monitor database connections
$query = "SHOW STATUS LIKE 'Threads_connected'";
$stmt = $con->prepare($query);
$stmt->execute();
$connections = $stmt->fetch();
```

### Production Environment Variables

Create `.env` file:

```env
APP_ENV=production
DB_HOST=localhost
DB_NAME=db_mamatid01
DB_USER=your_db_user
DB_PASS=your_secure_password
SESSION_LIFETIME=1800
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

## Maintenance

To maintain the system:

1. **Adding Authentication**: Use the `add_auth_check.php` script to add authentication checks to new PHP files.
2. **Updating Security**: Modify the `check_auth.php` file to change authentication logic.
3. **Error Pages**: Customize the error pages in the `system/security` directory.
4. **Database Configuration**: Update the `system/database/connection.php` file to change database settings.
5. **Session Management**: Modify the `system/security/session_config.php` file to adjust session behavior.

## Security Checklist

- ✅ HTTPS enabled (SSL certificate)
- ✅ Strong database passwords
- ✅ Regular security updates
- ✅ File upload restrictions
- ✅ Input validation and sanitization
- ✅ SQL injection prevention (using PDO)
- ✅ XSS protection
- ✅ CSRF tokens on forms
- ✅ Session security configured
- ✅ Error logging enabled
- ✅ Database backups scheduled

## Scaling for High Concurrent Users

If you expect 100+ concurrent users:

1. **Use Redis for session storage**:
   ```php
   ini_set('session.save_handler', 'redis');
   ini_set('session.save_path', 'tcp://127.0.0.1:6379');
   ```

2. **Database connection pooling**:
   ```php
   // Use persistent connections
   PDO::ATTR_PERSISTENT => true
   ```

3. **Load balancing**: Use multiple web servers behind a load balancer

4. **CDN**: Serve static assets from a Content Delivery Network

## Final Verification

Your system is ready for concurrent users when:

1. Multiple users can login simultaneously ✅
2. Each user has independent session data ✅  
3. One user's logout doesn't affect others ✅
4. Database handles concurrent connections ✅
5. Sessions are properly secured ✅ 

## Path Handling and Directory Structure

The system includes special handling for path references to ensure files can be included from different directory levels.

### Path Fixes Implementation

1. **Relative Path Detection**
   - Added path detection logic in header.php, sidebar.php, site_css_links.php, and site_js_links.php
   - These files now automatically detect if they're included from a subdirectory and adjust paths accordingly

2. **Client Authentication**
   - Updated book_appointment.php to use client authentication
   - Added role checks in role_functions.php to properly handle client permissions
   - Created check_client_auth.php for client-specific pages

3. **Fixed access_denied.php**
   - Simplified the file to work from subdirectories
   - Removed dependencies on header/sidebar includes
   - Added inline CSS and JS references with proper paths

### Authentication Usage

#### For Staff Pages
```php
<?php
include './config/connection.php';
require_once './common_service/role_functions.php';

// Restrict this page to specific roles
requireRole(['admin', 'doctor']);

// OR use convenience functions
requireAdmin(); // For admin-only pages
requireHealthStaff(); // For health workers and doctors
```

#### For Client Pages
```php
<?php
include './config/connection.php';
require_once './common_service/role_functions.php';

// Restrict this page to clients
requireClient();
```

### Troubleshooting Path Issues

If you encounter path issues:
1. Check that the base_path calculation is correct
2. Use browser developer tools to check for 404 errors on resources
3. Clear browser cache and cookies

For authentication issues:
1. Check session variables using test_client_auth.php
2. Verify role assignments in the database
3. Check for permission conflicts in role_functions.php 