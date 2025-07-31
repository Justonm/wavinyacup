# Machakos County Team Registration System
## Complete System Documentation

---

## Table of Contents
1. [System Overview](#system-overview)
2. [Installation Guide](#installation-guide)
3. [System Architecture](#system-architecture)
4. [Database Schema](#database-schema)
5. [User Guide](#user-guide)
6. [Admin Guide](#admin-guide)
7. [Technical Specifications](#technical-specifications)
8. [Troubleshooting](#troubleshooting)
9. [API Documentation](#api-documentation)
10. [Security Features](#security-features)

---

## System Overview

### Purpose
The Machakos County Team Registration System is a comprehensive web-based application designed to manage football team registrations, players, coaches, and administrative functions for Machakos County. The system provides a centralized platform for tracking teams, managing registrations, and generating reports.

### Key Features
- **Team Management**: Register and manage football teams
- **Player Registration**: Track individual players and their details
- **Coach Management**: Manage coach information and licenses
- **Registration Workflow**: Streamlined approval process
- **Reporting**: Comprehensive analytics and reporting
- **Admin Dashboard**: Full administrative control panel
- **User Authentication**: Secure login system

### Target Users
- **County Administrators**: Full system access
- **Team Managers**: Team registration and management
- **Players**: Individual registration
- **Coaches**: Coach registration and profile management

---

## Installation Guide

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server
- Composer (for dependency management)

### Step-by-Step Installation

#### 1. System Requirements Check
```bash
# Check PHP version
php -v

# Check MySQL
mysql --version

# Check if required PHP extensions are installed
php -m | grep -E "(pdo|mysqli|session|json)"
```

#### 2. Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE machakos_teams;
USE machakos_teams;

# Import schema
mysql -u root -p machakos_teams < database/schema.sql
```

#### 3. Configuration
1. Copy `config/config.example.php` to `config/config.php`
2. Update database credentials in `config/database.php`
3. Set up email configuration in `config/config.php`

#### 4. Web Server Setup

**Option A: PHP Built-in Server (Development)**
```bash
cd /path/to/machakos-teams
php -S localhost:8000
```

**Option B: Apache Setup**
```bash
# Create virtual host
sudo ln -s /path/to/machakos-teams /var/www/html/machakos-teams
sudo chown -R www-data:www-data /path/to/machakos-teams
```

#### 5. Initial Setup
1. Access `http://localhost:8000/setup.php`
2. Follow the setup wizard
3. Create admin user account

### Default Credentials
- **Admin Email**: `admin@machakoscounty.go.ke`
- **Admin Password**: `password`

---

## System Architecture

### Technology Stack
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5.3
- **Charts**: Chart.js
- **Icons**: Font Awesome 6.0

### Directory Structure
```
machakos-teams/
├── admin/                 # Admin panel files
│   ├── dashboard.php     # Main dashboard
│   ├── teams.php         # Teams management
│   ├── players.php       # Players management
│   ├── coaches.php       # Coaches management
│   ├── registrations.php # Registration approvals
│   ├── reports.php       # Analytics and reports
│   └── settings.php      # System settings
├── auth/                 # Authentication files
│   ├── login.php         # Login page
│   └── logout.php        # Logout handler
├── config/               # Configuration files
│   ├── config.php        # Main configuration
│   └── database.php      # Database configuration
├── database/             # Database files
│   └── schema.sql        # Database schema
├── docs/                 # Documentation
├── sessions/             # Session storage
└── index.php             # Main entry point
```

### Core Components

#### 1. Configuration System (`config/config.php`)
- Application constants
- Session management
- Helper functions
- Permission system

#### 2. Database Layer (`config/database.php`)
- PDO database connection
- Query execution methods
- Data validation

#### 3. Authentication System (`auth/`)
- User login/logout
- Session management
- Permission checking

#### 4. Admin Panel (`admin/`)
- Dashboard with statistics
- CRUD operations for all entities
- Reporting and analytics

---

## Database Schema

### Core Tables

#### 1. Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'player', 'coach') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 2. Teams Table
```sql
CREATE TABLE teams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    team_code VARCHAR(20) UNIQUE NOT NULL,
    ward_id INT NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ward_id) REFERENCES wards(id)
);
```

#### 3. Players Table
```sql
CREATE TABLE players (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    team_id INT,
    ward_id INT,
    position VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (ward_id) REFERENCES wards(id)
);
```

#### 4. Coaches Table
```sql
CREATE TABLE coaches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    team_id INT,
    ward_id INT,
    license_number VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (ward_id) REFERENCES wards(id)
);
```

#### 5. Team Registrations Table
```sql
CREATE TABLE team_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    team_id INT NOT NULL,
    ward_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (ward_id) REFERENCES wards(id)
);
```

### Geographic Tables

#### 6. Sub Counties Table
```sql
CREATE TABLE sub_counties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL
);
```

#### 7. Wards Table
```sql
CREATE TABLE wards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    sub_county_id INT NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    FOREIGN KEY (sub_county_id) REFERENCES sub_counties(id)
);
```

---

## User Guide

### Getting Started

#### 1. Accessing the System
1. Open your web browser
2. Navigate to `http://localhost:8000`
3. You'll be redirected to the login page

#### 2. Login Process
1. Enter your email address
2. Enter your password
3. Click "Login"
4. You'll be redirected to the admin dashboard

#### 3. Navigation
The system uses a sidebar navigation with the following sections:
- **Dashboard**: Overview and statistics
- **Teams**: Manage football teams
- **Players**: Manage player registrations
- **Coaches**: Manage coach information
- **Registrations**: Approve team registrations
- **Reports**: View analytics and reports
- **Settings**: System configuration

### Dashboard Overview

#### Statistics Cards
The dashboard displays four key metrics:
- **Active Teams**: Number of currently active teams
- **Registered Players**: Total number of registered players
- **Coaches**: Number of registered coaches
- **Pending Approvals**: Teams awaiting approval

#### Charts
- **Teams by Sub-County**: Bar chart showing team distribution
- **Recent Teams**: List of recently registered teams

### Managing Teams

#### Viewing Teams
1. Click "Teams" in the sidebar
2. View all teams in a table format
3. Use search and filter options

#### Adding a New Team
1. Click "Add New Team" button
2. Fill in team details:
   - Team name
   - Ward selection
   - Team code
3. Click "Save"

#### Editing Teams
1. Click the edit icon (pencil) next to a team
2. Modify the required fields
3. Click "Update"

#### Deleting Teams
1. Click the delete icon (trash) next to a team
2. Confirm the deletion

### Managing Players

#### Viewing Players
1. Click "Players" in the sidebar
2. View all players with their team associations
3. Filter by team or status

#### Adding Players
1. Click "Add New Player" button
2. Fill in player details:
   - Personal information
   - Team assignment
   - Position
3. Click "Save"

### Managing Coaches

#### Viewing Coaches
1. Click "Coaches" in the sidebar
2. View all coaches with their licenses
3. Filter by team or status

#### Adding Coaches
1. Click "Add New Coach" button
2. Fill in coach details:
   - Personal information
   - License number
   - Team assignment
3. Click "Save"

### Registration Approvals

#### Viewing Registrations
1. Click "Registrations" in the sidebar
2. View pending, approved, and rejected registrations
3. Filter by status

#### Approving Registrations
1. Click the checkmark icon next to a pending registration
2. Confirm the approval
3. The team status will be updated

#### Rejecting Registrations
1. Click the X icon next to a pending registration
2. Provide rejection reason
3. Confirm the rejection

### Reports and Analytics

#### Accessing Reports
1. Click "Reports" in the sidebar
2. View various statistics and charts
3. Export data as needed

#### Available Reports
- **Teams by Sub-County**: Geographic distribution
- **Player Statistics**: Registration trends
- **Coach Statistics**: License compliance
- **Registration Trends**: Approval rates

### System Settings

#### General Settings
1. Click "Settings" in the sidebar
2. Navigate to "General Settings" tab
3. Modify application settings:
   - Application name
   - Contact email
   - Timezone

#### Email Configuration
1. Go to "Email Settings" tab
2. Configure SMTP settings:
   - SMTP host
   - SMTP port
   - Username and password

#### Security Settings
1. Go to "Security" tab
2. Configure:
   - Debug mode
   - Maintenance mode
   - Session timeout

---

## Admin Guide

### Administrative Functions

#### User Management
- Create new user accounts
- Assign roles and permissions
- Deactivate user accounts
- Reset passwords

#### System Monitoring
- Monitor user activity
- Track system performance
- View error logs
- Monitor database usage

#### Data Management
- Backup database
- Restore from backup
- Export data
- Import data

### Security Best Practices

#### Password Policy
- Enforce strong passwords
- Regular password updates
- Account lockout after failed attempts

#### Access Control
- Role-based permissions
- Session management
- IP restrictions (if needed)

#### Data Protection
- Encrypt sensitive data
- Regular backups
- Secure file permissions

### Backup Procedures

#### Automated Backups
1. Set up cron job for daily backups
2. Store backups securely
3. Test restore procedures regularly

#### Manual Backups
```bash
# Database backup
mysqldump -u root -p machakos_teams > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz /path/to/machakos-teams
```

### Maintenance Procedures

#### Regular Maintenance
- Monitor disk space
- Check database performance
- Update system logs
- Review error logs

#### System Updates
- Backup before updates
- Test in staging environment
- Deploy during low-traffic periods
- Monitor after deployment

---

## Technical Specifications

### System Requirements

#### Server Requirements
- **Operating System**: Linux/Windows/macOS
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Memory**: Minimum 512MB RAM
- **Storage**: Minimum 1GB free space

#### PHP Extensions Required
- PDO
- PDO_MySQL
- mysqli
- session
- json
- mbstring
- openssl

#### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Performance Specifications

#### Response Times
- Page load: < 3 seconds
- Database queries: < 1 second
- File uploads: < 10 seconds

#### Concurrent Users
- Recommended: 50 concurrent users
- Maximum: 100 concurrent users

#### Database Performance
- Query optimization
- Indexed tables
- Connection pooling

### Security Specifications

#### Authentication
- Password hashing (bcrypt)
- Session management
- CSRF protection
- SQL injection prevention

#### Data Protection
- Input validation
- Output escaping
- File upload restrictions
- Secure headers

---

## Troubleshooting

### Common Issues

#### 1. Login Problems
**Symptoms**: Cannot log in, redirect loops
**Solutions**:
- Check database connection
- Verify user credentials
- Clear browser cache
- Check session configuration

#### 2. Database Connection Errors
**Symptoms**: "Database connection failed"
**Solutions**:
- Verify MySQL is running
- Check database credentials
- Test connection manually
- Check firewall settings

#### 3. Session Issues
**Symptoms**: Logged out frequently, session warnings
**Solutions**:
- Check session directory permissions
- Verify session configuration
- Clear session files
- Check PHP session settings

#### 4. File Upload Problems
**Symptoms**: Cannot upload files, permission errors
**Solutions**:
- Check file permissions
- Verify upload directory exists
- Check file size limits
- Validate file types

### Error Logs

#### PHP Error Log
```bash
# Check PHP error log
tail -f /var/log/php_errors.log
```

#### MySQL Error Log
```bash
# Check MySQL error log
tail -f /var/log/mysql/error.log
```

#### Apache Error Log
```bash
# Check Apache error log
tail -f /var/log/apache2/error.log
```

### Debug Mode

#### Enabling Debug Mode
1. Edit `config/config.php`
2. Set `DEBUG_MODE = true`
3. Check error logs for detailed information

#### Common Debug Steps
1. Check browser console for JavaScript errors
2. Verify network requests in browser dev tools
3. Test database queries manually
4. Check file permissions

---

## API Documentation

### Authentication Endpoints

#### Login
```
POST /auth/login.php
Content-Type: application/x-www-form-urlencoded

Parameters:
- email: string (required)
- password: string (required)

Response:
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 1,
        "username": "admin",
        "role": "admin"
    }
}
```

#### Logout
```
GET /auth/logout.php

Response:
{
    "success": true,
    "message": "Logged out successfully"
}
```

### Team Management API

#### Get All Teams
```
GET /api/teams.php

Response:
{
    "success": true,
    "teams": [
        {
            "id": 1,
            "name": "Team Name",
            "team_code": "T001",
            "ward_name": "Ward Name",
            "status": "active"
        }
    ]
}
```

#### Create Team
```
POST /api/teams.php
Content-Type: application/json

{
    "name": "Team Name",
    "ward_id": 1,
    "team_code": "T001"
}

Response:
{
    "success": true,
    "message": "Team created successfully",
    "team_id": 1
}
```

### Player Management API

#### Get All Players
```
GET /api/players.php

Response:
{
    "success": true,
    "players": [
        {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "email": "john@example.com",
            "team_name": "Team Name",
            "position": "Forward"
        }
    ]
}
```

### Registration API

#### Get Registrations
```
GET /api/registrations.php

Response:
{
    "success": true,
    "registrations": [
        {
            "id": 1,
            "team_name": "Team Name",
            "ward_name": "Ward Name",
            "status": "pending",
            "created_at": "2025-07-31 10:00:00"
        }
    ]
}
```

---

## Security Features

### Authentication Security

#### Password Security
- Bcrypt hashing with salt
- Minimum password length: 8 characters
- Password complexity requirements
- Account lockout after failed attempts

#### Session Security
- Secure session configuration
- Session timeout: 60 minutes
- Session regeneration on login
- CSRF token protection

### Data Protection

#### Input Validation
- Server-side validation for all inputs
- SQL injection prevention
- XSS protection
- File upload validation

#### Output Security
- HTML entity encoding
- Content Security Policy headers
- Secure HTTP headers
- Data sanitization

### Access Control

#### Role-Based Permissions
- Admin: Full system access
- Manager: Team management
- Player: Personal profile
- Coach: Coach profile

#### Session Management
- Secure session storage
- Session timeout
- Automatic logout
- Session hijacking protection

### File Security

#### Upload Security
- File type validation
- File size limits
- Secure file storage
- Virus scanning (recommended)

#### Directory Security
- Secure file permissions
- Protected configuration files
- Backup security
- Log file protection

---

## Conclusion

The Machakos County Team Registration System provides a comprehensive solution for managing football team registrations and administrative functions. With its user-friendly interface, robust security features, and comprehensive reporting capabilities, the system is ready for production use.

### Support and Maintenance

For technical support or questions about the system:
- Email: admin@machakoscounty.go.ke
- System Administrator: County IT Department
- Documentation: Available in the `/docs` directory

### Future Enhancements

Potential improvements for future versions:
- Mobile application
- SMS notifications
- Advanced reporting
- Integration with other county systems
- Multi-language support

---

*Documentation Version: 1.0*  
*Last Updated: July 31, 2025*  
*System Version: 1.0* 