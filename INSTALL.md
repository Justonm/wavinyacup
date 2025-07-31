# Installation Guide - Machakos County Team Registration System

## Prerequisites

- cPanel hosting with PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache web server
- SSL certificate (recommended)

## Step 1: Database Setup

1. **Create Database in cPanel**
   - Log into your cPanel
   - Go to "MySQL Databases"
   - Create a new database (e.g., `machakos_teams`)
   - Create a database user with full privileges
   - Note down the database name, username, and password

2. **Import Database Schema**
   - Go to "phpMyAdmin" in cPanel
   - Select your database
   - Click "Import"
   - Choose the file `database/schema.sql`
   - Click "Go" to import

## Step 2: File Upload

1. **Upload Files to cPanel**
   - Go to "File Manager" in cPanel
   - Navigate to `public_html` or your domain directory
   - Upload all files from this project
   - Ensure proper file permissions (644 for files, 755 for directories)

2. **Set File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 644 config/database.php
   ```

## Step 3: Configuration

1. **Database Configuration**
   - Edit `config/database.php`
   - Update the following variables:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'your_database_name');
     define('DB_USER', 'your_database_username');
     define('DB_PASS', 'your_database_password');
     ```

2. **Application Configuration**
   - Edit `config/config.php`
   - Update `APP_URL` to your domain
   - Update email settings if needed
   - Set `DEBUG_MODE` to `false` in production

## Step 4: Email Configuration (Optional)

1. **SMTP Settings**
   - Update SMTP settings in `config/config.php`
   - Use your cPanel email credentials
   - Test email functionality

## Step 5: Security Setup

1. **SSL Certificate**
   - Enable SSL in cPanel
   - Update `APP_URL` to use HTTPS

2. **File Security**
   - Ensure `config/` directory is not publicly accessible
   - Set proper file permissions

## Step 6: Initial Setup

1. **Default Admin Account**
   - Username: `admin`
   - Password: `password` (change immediately after first login)
   - Email: `admin@machakoscounty.go.ke`

2. **First Login**
   - Access your domain
   - Login with admin credentials
   - Change password immediately
   - Configure system settings

## Step 7: User Management

1. **Create User Accounts**
   - County Administrators
   - Sub-County Administrators
   - Ward Administrators
   - Coaches
   - Captains

2. **Role Assignment**
   - Assign appropriate roles to users
   - Set up permissions for each level

## Step 8: Testing

1. **Test Registration Process**
   - Register a test team
   - Register test players
   - Test approval workflow

2. **Test Reports**
   - Generate various reports
   - Verify data accuracy

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify database credentials
   - Check if database exists
   - Ensure MySQL service is running

2. **Permission Denied**
   - Check file permissions
   - Ensure uploads directory is writable

3. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions

4. **Email Not Working**
   - Check SMTP settings
   - Verify email credentials
   - Test with simple email first

### Performance Optimization

1. **Database Optimization**
   - Enable query caching
   - Optimize database indexes
   - Regular database maintenance

2. **File Optimization**
   - Enable GZIP compression
   - Optimize images
   - Use CDN for static assets

## Maintenance

1. **Regular Backups**
   - Database backups (daily)
   - File backups (weekly)
   - Test restore procedures

2. **Updates**
   - Keep PHP version updated
   - Update dependencies
   - Security patches

3. **Monitoring**
   - Monitor disk space
   - Check error logs
   - Performance monitoring

## Support

For technical support:
- Email: support@machakoscounty.go.ke
- Phone: +254700000000
- Documentation: Available in the docs/ folder

## Security Checklist

- [ ] SSL certificate installed
- [ ] Default admin password changed
- [ ] File permissions set correctly
- [ ] Database credentials secured
- [ ] Error reporting disabled in production
- [ ] Regular backups configured
- [ ] Firewall rules configured
- [ ] Antivirus software installed

## Performance Checklist

- [ ] GZIP compression enabled
- [ ] Database indexes optimized
- [ ] Image optimization completed
- [ ] CDN configured (if applicable)
- [ ] Caching enabled
- [ ] Database queries optimized

## Backup Procedures

### Database Backup
```bash
mysqldump -u username -p database_name > backup.sql
```

### File Backup
```bash
tar -czf backup.tar.gz /path/to/application
```

### Automated Backup Script
Create a cron job for automated backups:
```bash
0 2 * * * /usr/bin/mysqldump -u username -p password database_name > /backup/db_$(date +\%Y\%m\%d).sql
```

## Emergency Procedures

1. **System Down**
   - Check error logs
   - Verify database connection
   - Restart web server if needed

2. **Data Loss**
   - Restore from latest backup
   - Verify data integrity
   - Update users about downtime

3. **Security Breach**
   - Change all passwords
   - Review access logs
   - Update security measures 