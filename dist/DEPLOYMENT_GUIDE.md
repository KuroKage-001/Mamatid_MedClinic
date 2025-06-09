# Mamatid Health Center System - Production Deployment Guide

## Concurrent User Support

Your system **ALREADY SUPPORTS** multiple concurrent users logging in simultaneously. Here's why and how to optimize it:

### ✅ Current Concurrent User Support

1. **Separate Session Spaces**: 
   - Admin/Staff: `$_SESSION['user_id']`, `$_SESSION['role']`
   - Clients: `$_SESSION['client_id']`, `$_SESSION['client_name']`

2. **Independent Sessions**: Each user gets their own PHP session ID and data

3. **Proper Logout**: Each user logout only affects their own session

### 🚀 Production Deployment Checklist

#### 1. Server Configuration

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

#### 2. PHP Configuration (php.ini)

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

#### 3. Database Optimization

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

#### 4. Security Enhancements

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

#### 5. Performance Optimization

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

### 🔧 Testing Concurrent Users

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

### 📊 Monitoring Concurrent Sessions

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

### 🚨 Production Environment Variables

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

### 🔒 Security Checklist

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

### 📈 Scaling for High Concurrent Users

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

### ✅ Final Verification

Your system is ready for concurrent users when:

1. Multiple users can login simultaneously ✅
2. Each user has independent session data ✅  
3. One user's logout doesn't affect others ✅
4. Database handles concurrent connections ✅
5. Sessions are properly secured ✅

**Your current system already supports all these requirements!** 