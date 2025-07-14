# Client Portal

This directory contains all client-side files for the Mamatid Health Center system.

## Directory Structure

```
client_portal/
├── auth/
│   ├── client_forgot_password.php
│   └── client_reset_password.php
├── config/
│   └── client_ui/
│       ├── client_footer.php
│       ├── client_header.php
│       └── client_sidebar.php
├── account_client_settings.php
├── client_appointment_booking.php
├── client_dashboard.php
├── client_login.php
├── client_logout.php
├── client_register.php
├── .htaccess
└── README.md
```

## File Descriptions

### Main Client Files
- **client_login.php** - Client login page
- **client_register.php** - Client registration page
- **client_dashboard.php** - Client dashboard after login
- **client_appointment_booking.php** - Appointment booking interface
- **client_logout.php** - Client logout handler
- **account_client_settings.php** - Client account settings and profile management

### Authentication Files (auth/)
- **client_forgot_password.php** - Password reset request form
- **client_reset_password.php** - Password reset form with token validation

### UI Components (config/client_ui/)
- **client_header.php** - Common header for all client pages
- **client_footer.php** - Common footer for all client pages
- **client_sidebar.php** - Navigation sidebar for client pages

## Features

### Client Registration & Authentication
- User registration with profile picture upload
- Secure login with session management
- Password recovery via email
- Session isolation from admin accounts

### Account Management
- Profile picture upload and management
- Personal information updates
- Password change functionality
- Account settings customization

### Appointment Management
- View available doctor and staff schedules
- Book appointments with real-time availability
- Cancel future appointments
- View appointment history

### Dashboard Features
- Overview of all appointments (upcoming, completed, cancelled)
- Recent status updates
- Quick access to booking new appointments

## Security Features

- Session isolation between admin and client accounts
- CSRF protection
- Input validation and sanitization
- Secure password reset tokens
- Profile picture upload validation
- Directory security with .htaccess

## Dependencies

The client portal depends on the following shared system files:
- `../system/utilities/check_client_auth.php` - Client authentication check
- `../system/security/admin_client_session_isolation.php` - Session isolation
- `../config/db_connection.php` - Database connection
- `../system/phpmailer/` - Email functionality

## Navigation

From the root directory, clients can access the portal at:
- Main portal: `client_portal/client_login.php`
- Registration: `client_portal/client_register.php`

The main site index.php provides links to both admin and client portals.

## Installation Notes

All file paths have been updated to work from the client_portal directory structure. The system maintains backward compatibility with existing database structures and shared utilities. All client-specific functionality is now contained within this directory for better organization and security. 