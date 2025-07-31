# Machakos County Team Registration System

A FIFA-style team registration system designed for Machakos County, Kenya. This system manages team registration from player level up through the administrative hierarchy: Player → Captain → Coach → Ward → Sub-County → County.

## Administrative Structure

### Machakos County Sub-Counties:
1. **Machakos Town** - 5 Wards
2. **Mavoko** - 4 Wards  
3. **Kangundo** - 4 Wards
4. **Kathiani** - 4 Wards
5. **Yatta** - 4 Wards
6. **Masinga** - 4 Wards
7. **Matungulu** - 4 Wards
8. **Mwala** - 4 Wards

### Total: 8 Sub-Counties, 33 Wards

## Features

- **Multi-level Registration**: Player → Captain → Coach → Ward → Sub-County → County
- **Team Management**: Create, edit, and manage teams
- **Player Registration**: Individual player profiles with statistics
- **Administrative Dashboard**: County-level oversight
- **Reporting System**: Generate reports at all levels
- **User Authentication**: Role-based access control
- **Mobile Responsive**: Works on all devices

## Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Deployment**: cPanel compatible
- **Security**: Password hashing, SQL injection prevention, XSS protection

## Installation

1. Upload files to cPanel
2. Import database schema
3. Configure database connection
4. Set up email settings
5. Configure file permissions

## Database Structure

- `users` - User accounts and authentication
- `teams` - Team information
- `players` - Player profiles
- `coaches` - Coach information
- `wards` - Ward data
- `sub_counties` - Sub-county data
- `counties` - County data
- `registrations` - Registration records

## Security Features

- Password hashing with bcrypt
- Session management
- CSRF protection
- Input validation and sanitization
- SQL injection prevention
- XSS protection

## License

MIT License 