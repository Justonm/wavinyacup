# cPanel Deployment Guide for Wavinya Cup System

## Prerequisites
- cPanel hosting account with PHP 7.4+ and MySQL
- SSH access (optional but recommended)
- File Manager access in cPanel

## Step 1: Upload Files
1. Compress your entire project folder into a ZIP file
2. In cPanel File Manager, navigate to `public_html`
3. Upload and extract the ZIP file
4. Move all files from the extracted folder to `public_html` root

## Step 2: Database Setup
1. In cPanel, go to "MySQL Databases"
2. Create a new database (e.g., `your_username_wavinyacup`)
3. Create a database user with a strong password
4. Add the user to the database with ALL PRIVILEGES
5. Import your database schema using phpMyAdmin:
   - Upload `database/schema.sql`
   - Run `database/add_image_fields.sql` if needed

## Step 3: Environment Configuration
1. Edit `.env` file with your production settings:
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=your_username_wavinyacup
DB_USER=your_username_dbuser
DB_PASS=your_strong_password
DB_CHARSET=utf8mb4

# Email Configuration (Gmail SMTP)
APP_EMAIL=your_email@gmail.com
ADMIN_EMAILS=your_email@gmail.com
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD="your_app_password"
SMTP_ENCRYPTION=ssl

# App Configuration
APP_URL=https://yourdomain.com
```

## Step 4: Set Directory Permissions
Set these permissions via File Manager or SSH:
```bash
chmod 755 uploads/
chmod 755 uploads/coaches/
chmod 755 uploads/players/
chmod 755 uploads/teams/
chmod 755 sessions/
chmod 644 .env
chmod 644 .htaccess
```

## Step 5: Setup Cron Jobs
In cPanel "Cron Jobs", add:
```
# Send queued emails every minute
* * * * * /usr/bin/php /home/your_username/public_html/cron/send_queued_emails.php

# Process coach approvals every 5 minutes
*/5 * * * * /usr/bin/php /home/your_username/public_html/cron/process_approved_coaches.php
```

## Step 6: Gmail App Password Setup
1. Enable 2-Factor Authentication on your Gmail account
2. Go to Google Account Settings > Security > App passwords
3. Generate an app password for "Mail"
4. Use this 16-character password in your `.env` file

## Step 7: Test the System
1. Visit your domain
2. Test coach registration
3. Test admin login
4. Check email functionality using `test_email_production.php?test=email123`
5. Monitor email queue at `admin/process_emails.php`

## Troubleshooting

### Email Issues
- Check Gmail app password is correct
- Verify SMTP settings in `.env`
- Test with `test_email_production.php?test=email123`
- Manually process emails at `admin/process_emails.php`

### Database Issues
- Verify database credentials in `.env`
- Check if all tables were created properly
- Ensure database user has proper permissions

### File Upload Issues
- Check upload directory permissions (755)
- Verify PHP upload limits in cPanel or .htaccess
- Check file size limits

### Session Issues
- Ensure sessions directory exists and is writable
- Check session configuration in `config/config.php`

## Security Notes
- Change default passwords
- Keep `.env` file secure (protected by .htaccess)
- Regularly update the system
- Monitor error logs in cPanel

## Performance Optimization
- Enable gzip compression (included in .htaccess)
- Use caching where possible
- Optimize images before upload
- Monitor database performance

## Maintenance
- Regularly backup database
- Monitor email queue for failed emails
- Check error logs periodically
- Update coach and player information as needed
