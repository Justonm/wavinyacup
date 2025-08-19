# Google OAuth2 Setup Guide for Admin Authentication

## 🔐 **Enhanced Admin Security Setup**

Your admin login now uses Gmail OAuth2 for maximum security. Follow these steps to complete the setup:

## **Step 1: Create Google OAuth2 Credentials**

### 1.1 Go to Google Cloud Console
- Visit: [Google Cloud Console](https://console.cloud.google.com/)
- Sign in with your `governorwavinyacup@gmail.com` account

### 1.2 Create or Select Project
- Click "Select a project" → "New Project"
- Project name: "Wavinya Cup Registration System"
- Click "Create"

### 1.3 Enable Google+ API
- Go to "APIs & Services" → "Library"
- Search for "Google+ API"
- Click "Enable"

### 1.4 Create OAuth2 Credentials
- Go to "APIs & Services" → "Credentials"
- Click "Create Credentials" → "OAuth client ID"
- Application type: "Web application"
- Name: "Wavinya Cup Admin Login"

### 1.5 Configure Authorized URLs
**Authorized JavaScript origins:**
```
http://127.0.0.1:8000
http://localhost:8000
```

**Authorized redirect URIs:**
```
http://127.0.0.1:8000/auth/gmail_callback.php
http://localhost:8000/auth/gmail_callback.php
```

### 1.6 Copy Credentials
- Copy the **Client ID** and **Client Secret**
- Update your `.env` file:
```env
GOOGLE_CLIENT_ID=your-actual-client-id-here
GOOGLE_CLIENT_SECRET=your-actual-client-secret-here
```

## **Step 2: Test Admin Authentication**

### 2.1 Access Admin Login
Visit: `http://127.0.0.1:8000/auth/admin_login.php`

### 2.2 Click "Sign in with Gmail"
- You'll be redirected to Google
- Sign in with `governorwavinyacup@gmail.com`
- Grant permissions to the application

### 2.3 Access Admin Dashboard
- After successful authentication, you'll be redirected to the admin dashboard
- Your session will be secure with 4-hour timeout

## **🛡️ Security Features**

✅ **OAuth2 Authentication** - No passwords stored locally
✅ **2-Factor Authentication** - Uses Gmail's built-in 2FA
✅ **Session Timeout** - 4-hour automatic logout
✅ **Authorized Email Check** - Only `governorwavinyacup@gmail.com` allowed
✅ **CSRF Protection** - State parameter validation
✅ **Session Regeneration** - New session ID on login

## **🔧 Admin Access URLs**

- **Secure Admin Login:** `http://127.0.0.1:8000/auth/admin_login.php`
- **Admin Dashboard:** `http://127.0.0.1:8000/admin/dashboard.php`
- **Regular User Login:** `http://127.0.0.1:8000/auth/login.php` (for coaches, captains, players)

## **📧 Authorized Admin Emails**

Currently authorized: `governorwavinyacup@gmail.com`

To add more admin emails, edit `/auth/gmail_oauth.php` line 19:
```php
$this->authorized_emails = [
    'governorwavinyacup@gmail.com',
    'another-admin@gmail.com' // Add more as needed
];
```

## **🚨 Important Notes**

1. **Production Setup:** For production, update `APP_URL` in `.env` to your actual domain
2. **HTTPS Required:** Google OAuth2 requires HTTPS in production
3. **Domain Verification:** Add your production domain to Google Console
4. **Backup Access:** Keep the regular login system for emergency access

Your admin authentication is now enterprise-grade secure! 🔐
