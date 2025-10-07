# Wavinyacup Email System - Quick Reference Links

## Main Email Management Links

### 1. Bulk Email System (Generate Coach Credentials)
```
https://governorwavinyacup.com/wavinyacup/admin/working_bulk_email.php?access=working2025
```
**Purpose:** Generates new passwords for approved coaches and queues credential emails

### 2. Email Monitor Dashboard
```
https://governorwavinyacup.com/wavinyacup/admin/email_monitor.php?access=monitor2025
```
**Purpose:** Real-time email queue monitoring, cron job status, manual email processing

### 3. View Email Logs (Password Viewing)
```
https://governorwavinyacup.com/wavinyacup/admin/view_email_log.php?access=viewlog2025&file=FILENAME
```
**Purpose:** View generated email logs with passwords to hash
**Note:** Replace FILENAME with actual log file name (e.g., coach_credentials_20250912.json)

## Workflow Steps

1. **Generate Emails:** Run bulk email system (#1)
2. **Monitor Status:** Check email monitor (#2) 
3. **View Passwords:** Use view email logs (#3) to see passwords that need hashing
4. **Process Queue:** Use monitor dashboard to manually run cron if needed

## Access Codes Summary
- Bulk Email: `access=working2025`
- Email Monitor: `access=monitor2025` 
- View Logs: `access=viewlog2025`

## Notes
- All systems use existing email queue infrastructure
- Passwords are generated automatically and logged
- Cron job processes queued emails automatically
- Manual processing available via monitor dashboard
