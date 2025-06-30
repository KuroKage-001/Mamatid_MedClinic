# Mamatid_MedClinic
Development of Health Center Management System for Local Barangay Health Center in Mamatid, Cabuyao City

## Overview
The Mamatid Health Center System is a comprehensive web-based application designed to manage various aspects of a community health center's operations. This document provides technical information about the system's architecture, file structure, and functionality.

## System Architecture
The system follows a modular architecture with separate components for different functionalities:

1. **User Interface Layer**: HTML templates with PHP for dynamic content generation
2. **Business Logic Layer**: PHP scripts for processing data and implementing business rules
3. **Data Access Layer**: PDO-based database interactions for secure and efficient data operations
4. **Authentication Layer**: Session-based authentication with role-based access control

## File Structure

### Root Directory
Contains main application pages and entry points:
- **index.php**: Main entry point/login page
- **admin_dashboard.php**: Administrator dashboard
- **client_dashboard.php**: Client/patient dashboard
- Various feature-specific PHP files (e.g., general_bp_monitoring.php)

### /config
Configuration files and UI components:
- **db_db_connection.php**: Bridge file that includes the actual connection file from system/database.
- **admin_header.php**, **admin_footer.php**: Admin UI components
- **client_ui/**: Client-specific UI components
- **site_css_links.php**, **site_js_links.php**: Centralized CSS/JS management

### /system
Core system files and utilities:
- **database/**: Database-related files
  - **db_db_connection.php**: Database connection configuration and setup.
  - **db_mamatid01.sql**: Database schema and initial data
- **security/**: Authentication and security functions
- **utilities/**: Helper functions and utilities
- **reminders/**: Appointment reminder system
- **fpdf182/**: PDF generation library
- **phpmailer/**: Email functionality

### /actions
Backend processing scripts:
- **delete_*.php**: Record deletion handlers
- **archive_*.php**: Record archiving handlers
- Other action-specific handlers

### /ajax
AJAX request handlers for dynamic content:
- **get_*.php**: Data retrieval handlers
- **check_*.php**: Validation handlers
- Other AJAX-specific handlers

### /reports
Report generation scripts:
- **print_*.php**: PDF report generators

### /plugins
Third-party libraries and plugins:
- Various CSS/JS libraries (Bootstrap, jQuery, etc.)

## Authentication System

The system includes a comprehensive authentication mechanism to protect pages from unauthorized access:

### Key Components

1. **check_auth.php**: Located in the system/utilities folder, this file checks if a user is logged in before allowing access to protected pages.

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
require_once './system/utilities/check_auth.php';

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

## System Organization

The system follows a structured organization pattern:

1. **Bridge Pattern**: Some files serve as bridges to maintain backward compatibility while allowing for code reorganization.
Bridge files are located in the original locations (e.g., `config/db_db_connection.php`) and include the actual implementation from the new location (e.g., `system/database/db_db_connection.php`).

2. **Separation of Concerns**:
   - UI components are separated from business logic
   - Database access is centralized
   - Authentication is handled separately from application logic

3. **Consistent Naming Conventions**:
   - Files are named according to their functionality
   - Related files follow consistent naming patterns
   - Database tables match their corresponding features

## Database Structure

The database (`db_mamatid01`) contains the following key tables:

1. **users**: System users (staff, administrators)
2. **patients**: Patient records
3. **bp_monitoring**: Blood pressure monitoring records
4. **family_planning**: Family planning service records
5. **tetanus_toxoid**: Tetanus toxoid vaccination records
6. **random_blood_sugar**: Blood sugar monitoring records
7. **deworming**: Deworming service records
8. **family_members**: Family member records
9. **appointments**: Patient appointment records
10. **medicines**: Medicine inventory
11. **medicine_categories**: Categories for medicines
12. **medicine_stock**: Medicine stock tracking

## Key Features

### Patient Management
- Patient registration and profile management
- Family member tracking
- Medical history tracking

### Health Services
- Blood pressure monitoring
- Family planning services
- Tetanus toxoid vaccination
- Blood sugar monitoring
- Deworming services

### Appointment System
- Appointment scheduling
- Automated reminders
- Appointment status tracking

### Inventory Management
- Medicine inventory tracking
- Stock level monitoring
- Categorization of medicines

### Reporting
- Service-specific reports
- Patient history reports
- Inventory reports

## Development Guidelines

### Adding New Features
1. **Create UI Components**: Add necessary HTML/PHP files following existing patterns
2. **Implement Business Logic**: Create processing scripts in appropriate directories
3. **Database Integration**: Update database schema if needed
4. **Authentication**: Implement proper access control

### Modifying Existing Features
1. **Locate Related Files**: Identify all files related to the feature
2. **Update Database**: Make necessary schema changes
3. **Update Business Logic**: Modify processing scripts
4. **Update UI**: Make UI changes as needed

### System Configuration
1. **Environment Setup**: Configure web server (Apache recommended) with PHP 7.2+
2. **Database Setup**: Import `system/database/db_mamatid01.sql`
3. **File Permissions**: Set appropriate permissions for uploaded files
4. **Database Configuration**: Update the `system/database/db_db_connection.php` file to change database settings.

## Security Features

The system includes several security features:

1. **Session Management**: Sessions are used to track user authentication status.
2. **Password Encryption**: Passwords are encrypted using MD5 (Note: For production, consider using more secure methods like bcrypt).
3. **Access Control**: Different user roles have different access levels.
4. **Security Headers**: HTTP headers are set to prevent common web vulnerabilities.
5. **Protected Directories**: Sensitive directories are protected from direct access.

## Code Examples

### Database Connection
```php
include './config/db_db_connection.php';
```

### Query Execution (PDO)
```php
try {
    $stmt = $con->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $ex) {
    echo $ex->getMessage();
}
```

## Maintenance Tasks

### Regular Backups
1. Database backups should be performed daily
2. File system backups should be performed weekly
3. Backup verification should be performed regularly

### System Updates
1. PHP and web server should be kept updated
2. Third-party libraries should be updated periodically
3. Security patches should be applied promptly

### Performance Optimization
1. Database queries should be optimized
2. Image and file sizes should be optimized
3. Caching should be implemented where appropriate

## Troubleshooting

### Common Issues
1. **Database Connection Errors**: Check database credentials in `system/database/db_db_connection.php`
2. **Session Timeouts**: Adjust timeout settings in `system/database/db_db_connection.php`
3. **File Upload Issues**: Check file permissions and PHP configuration
4. **PDF Generation Errors**: Ensure FPDF library is properly installed

### Error Logging
1. PHP errors are logged in the server's error log
2. Custom error logging is implemented for critical operations
3. Database errors are captured and logged

## Future Enhancements
1. Upgrade password hashing to bcrypt or Argon2
2. Implement two-factor authentication
3. Enhance reporting capabilities
4. Develop mobile application interface
5. Implement telemedicine features

---

This documentation is maintained by the system development team and should be updated as the system evolves.
