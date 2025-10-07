# Email System Fix Instructions

## Problem Identified
Your email system isn't sending auto emails because:
1. **SMTP Configuration Mismatch**: Your `.env` uses port 465 with SSL, but `mailer.php` was configured for port 587 with STARTTLS
2. **Cron Job Not Running**: The email queue processor isn't being executed automatically in production

## Fixes Applied

### 1. Fixed SMTP Configuration
Updated `includes/mailer.php` to match your `.env` settings:
- Changed from STARTTLS (port 587) to SMTPS (port 465)
- Now reads port from environment variable

### 2. Created Diagnostic Tools
- `test_email_queue.php` - Test and diagnose email issues
- `setup_email_cron.php` - Complete setup guide for production

## Next Steps (CRITICAL)

### Step 1: Test the Fix
1. Visit: `https://governorwavinyacup.com/wavinyacup/test_email_queue.php`
2. Click "Test Send Email" to verify SMTP works
3. Click "Process Queue Manually" to send any pending emails

### Step 2: Set Up Cron Job (REQUIRED)
Visit: `https://governorwavinyacup.com/wavinyacup/setup_email_cron.php`

**For cPanel/Shared Hosting:**
1. Go to your hosting control panel
2. Find "Cron Jobs" section
3. Add this command: `* * * * * /usr/bin/php /home/[your-username]/public_html/wavinyacup/cron/send_queued_emails.php`
4. Set to run every minute (or every 5 minutes: `*/5 * * * *`)

**For VPS/Server:**
```bash
crontab -e
# Add this line:
* * * * * /usr/bin/php /path/to/wavinyacup/cron/send_queued_emails.php >> /tmp/email_cron.log 2>&1
```

### Step 3: Verify Everything Works
1. Accept a coach in admin panel
2. Check if emails are queued: `test_email_queue.php`
3. Wait 1-5 minutes for cron job to process
4. Verify coach receives email

## Troubleshooting

### If Test Email Fails:
- Check Gmail app password is still valid
- Verify server allows outbound connections on port 465
- Check error logs for specific SMTP errors

### If Cron Job Doesn't Work:
- Verify PHP path: `which php`
- Check cron job logs
- Test manual execution: `php cron/send_queued_emails.php`
- Contact hosting provider about cron job setup

## Files Modified:
- ✅ `includes/mailer.php` - Fixed SMTP configuration
- ✅ `test_email_queue.php` - Created diagnostic tool
- ✅ `setup_email_cron.php` - Created setup guide

The email system should now work properly once you set up the cron job!
