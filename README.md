# Mamatid_MedClinic
Development of Health Center Management System for Local Barangay Health Center in Mamatid, Cabuyao City

## Authentication System

The system includes a comprehensive authentication mechanism to protect pages from unauthorized access:

### Key Components

1. **check_auth.php**: Located in the config folder, this file checks if a user is logged in before allowing access to protected pages.

2. **unauthorized_access.php**: Located in the system/security folder, this page is displayed when someone tries to access a protected page without logging in.

3. **add_auth_check.php**: Located in the system/utilities folder, this script can be used to add authentication checks to all PHP files in the system.

4. **.htaccess**: Contains rules to redirect unauthorized access attempts to the unauthorized_access.php page.

### How Authentication Works

- When a user tries to access a protected page without logging in, they are redirected to the unauthorized_access.php page.
- The unauthorized_access.php page displays a 401 error and provides links to the login page.
- The check_auth.php file is included in all protected pages to ensure that only authenticated users can access them.

### Adding Authentication to New Pages

For any new PHP page that should be protected by authentication:

```php
<?php
// Include authentication check
require_once './config/check_auth.php';

// Rest of your code here
?>
```

### Bulk Adding Authentication

The system includes a script called `add_auth_check.php` (in the system/utilities folder) that can be run to automatically add the authentication check to all PHP files in the system. To use it:

1. Make sure you have a backup of your files.
2. Run the script: `php system/utilities/add_auth_check.php`
3. The script will add the authentication check to all PHP files except those in the exclude list.

### Excluded Files

The following files are excluded from authentication checks:

- index.php
- client_login.php
- client_register.php
- client_logout.php
- unauthorized_access.php
- congratulation.php

### Excluded Directories

Files in the following directories are excluded from authentication checks:

- config
- common_service
- ajax
- actions
- plugins
- pdflib
- dist

## Security Features

The system includes several security features:

1. **Session Management**: Sessions are used to track user authentication status.
2. **Password Encryption**: Passwords are encrypted using MD5 (Note: For production, consider using more secure methods like bcrypt).
3. **Access Control**: Different user roles have different access levels.
4. **Security Headers**: HTTP headers are set to prevent common web vulnerabilities.
5. **Protected Directories**: Sensitive directories are protected from direct access.
