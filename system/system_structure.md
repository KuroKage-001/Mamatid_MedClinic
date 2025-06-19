# Mamatid Health Center System Structure

## Directory Structure

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

## System Directory

The `system` directory contains core files that are essential to the system's operation but are not directly part of the application's functionality:

### Database

The `system/database` directory contains database-related files:

- **connection.php**: Database connection configuration and setup.

### Security

The `system/security` directory contains files related to system security:

- **unauthorized_access.php**: Displayed when a user tries to access a protected page without authentication (401 error).
- **access_denied.php**: Displayed when a logged-in user tries to access a page without proper permissions (403 error).
- **session_config.php**: Configuration for secure sessions and session management.
- **session_fix.php**: Ensures session variables are properly set to prevent errors.

### Utilities

The `system/utilities` directory contains utility scripts for system maintenance:

- **add_auth_check.php**: A utility script to add authentication checks to PHP files.

## Configuration Directory

The `config` directory contains configuration files and bridge files that maintain backward compatibility:

- **check_auth.php**: Authentication check file included in all protected pages.
- **connection.php**: Bridge file that includes the actual connection file from system/database.
- **session_config.php**: Bridge file that includes the actual session config from system/security.
- **session_fix.php**: Bridge file that includes the actual session fix from system/security.
- **header.php**, **footer.php**, **sidebar.php**: Layout templates.
- **site_css_links.php**, **site_js_links.php**: CSS and JavaScript includes.

## Bridge Files

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

## Maintenance

To maintain the system:

1. **Adding Authentication**: Use the `add_auth_check.php` script to add authentication checks to new PHP files.
2. **Updating Security**: Modify the `check_auth.php` file to change authentication logic.
3. **Error Pages**: Customize the error pages in the `system/security` directory.
4. **Database Configuration**: Update the `system/database/connection.php` file to change database settings.
5. **Session Management**: Modify the `system/security/session_config.php` file to adjust session behavior.