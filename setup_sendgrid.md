# SendGrid Email Setup Guide

Since Gmail App Passwords and Less Secure Apps are not available for your account, here's how to set up SendGrid for reliable email delivery.

## Step 1: Create SendGrid Account
1. Go to [https://sendgrid.com/](https://sendgrid.com/)
2. Click "Start for Free"
3. Sign up with your email
4. Verify your email address

## Step 2: Create API Key
1. Login to SendGrid dashboard
2. Go to Settings → API Keys
3. Click "Create API Key"
4. Choose "Restricted Access"
5. Give permissions for "Mail Send" only
6. Copy the generated API key

## Step 3: Update Configuration
Replace `your-sendgrid-api-key` in `.env` file with your actual API key:
```
SMTP_PASSWORD=SG.your-actual-api-key-here
```

## Step 4: Verify Sender Identity
1. Go to Settings → Sender Authentication
2. Click "Verify a Single Sender"
3. Add: governorwavinyacup@gmail.com
4. Fill out the form and verify

## Alternative: Mailtrap (Testing Only)
If you prefer testing first:
1. Go to [https://mailtrap.io/](https://mailtrap.io/)
2. Sign up for free account
3. Get SMTP credentials from your inbox
4. Update .env with Mailtrap settings

## Benefits
- ✅ 100 emails/day free with SendGrid
- ✅ Professional email delivery
- ✅ No Gmail restrictions
- ✅ Detailed delivery analytics
- ✅ Works immediately after setup
