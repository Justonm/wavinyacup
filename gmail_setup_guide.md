# Gmail SMTP Setup Guide for New Accounts

Since your Gmail account is new (less than a week), here's the complete setup process:

## Step 1: Enable 2-Factor Authentication (Required)
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Click "2-Step Verification" → "Get Started"
3. Add your phone number for SMS verification
4. Complete the setup process

## Step 2: Wait 24-48 Hours
- New Gmail accounts need time before security features activate
- 2FA must be active for at least 24 hours
- Google needs to verify account legitimacy

## Step 3: Generate App Password (After 2FA is active)
1. Return to [Google Account Security](https://myaccount.google.com/security)
2. Look for "App passwords" (may take 24-48 hours to appear)
3. Select "Mail" → "Other (custom name)" → "Wavinya Cup"
4. Copy the 16-character password

## Step 4: Update Configuration
Replace your regular password in `.env` with the App Password:
```
SMTP_PASSWORD=your-16-char-app-password
```

## Alternative: Try Current Setup First
Your current configuration might work with new Gmail accounts:
- Gmail sometimes allows regular passwords for new accounts temporarily
- Test with `http://127.0.0.1:8000/test_email.php`

## If Gmail Still Doesn't Work
1. **Enable "Less secure app access"** - May become available after 2FA setup
2. **Use Gmail with OAuth2** - More complex but most secure
3. **Wait longer** - Some accounts need up to 7 days

## Troubleshooting
- **"Username and Password not accepted"** → Need App Password
- **"Less secure apps blocked"** → Enable 2FA first
- **"App passwords not available"** → Wait 24-48 hours after 2FA

## Benefits of Gmail SMTP
- ✅ 500 emails/day free
- ✅ Excellent deliverability
- ✅ Professional appearance
- ✅ Reliable infrastructure
- ✅ Detailed error reporting
