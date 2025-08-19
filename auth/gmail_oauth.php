<?php
/**
 * Gmail OAuth2 Authentication Handler
 * Provides secure admin authentication using Google OAuth2
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

class GmailOAuth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $authorized_emails;
    
    public function __construct() {
        // OAuth2 credentials (to be set in .env)
        $this->client_id = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $this->client_secret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
        $this->redirect_uri = $_ENV['APP_URL'] . '/auth/gmail_callback.php';
        
        // Authorized admin emails
        $this->authorized_emails = [
            'governorwavinyacup@gmail.com' // Main admin email
        ];
    }
    
    /**
     * Generate OAuth2 authorization URL
     */
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'email profile',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $this->generateState()
        ];
        
        $_SESSION['oauth_state'] = $params['state'];
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    /**
     * Handle OAuth2 callback and authenticate user
     */
    public function handleCallback($code, $state) {
        // Verify state parameter
        if (!isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
            throw new Exception('Invalid state parameter');
        }
        
        unset($_SESSION['oauth_state']);
        
        // Exchange code for access token
        $token_data = $this->exchangeCodeForToken($code);
        
        // Get user info from Google
        $user_info = $this->getUserInfo($token_data['access_token']);
        
        // Verify user is authorized admin
        if (!in_array($user_info['email'], $this->authorized_emails)) {
            throw new Exception('Unauthorized email address');
        }
        
        // Create or update admin session
        $this->createAdminSession($user_info);
        
        return $user_info;
    }
    
    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken($code) {
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirect_uri
        ];
        
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('Failed to exchange code for token');
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get user information from Google API
     */
    private function getUserInfo($access_token) {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('Failed to get user info');
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Create secure admin session
     */
    private function createAdminSession($user_info) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_email'] = $user_info['email'];
        $_SESSION['admin_name'] = $user_info['name'];
        $_SESSION['admin_picture'] = $user_info['picture'] ?? '';
        $_SESSION['login_time'] = time();
        $_SESSION['user_role'] = 'admin';
        $_SESSION['auth_method'] = 'gmail_oauth';
        
        // Log successful admin login
        log_activity(null, 'admin_oauth_login', 'Admin authenticated via Gmail OAuth: ' . $user_info['email']);
        
        // Update or create admin record in database
        $this->updateAdminRecord($user_info);
    }
    
    /**
     * Update admin record in database
     */
    private function updateAdminRecord($user_info) {
        try {
            $db = db();
            
            // Check if admin exists
            $admin = $db->fetchRow("SELECT * FROM users WHERE email = ? AND role = 'admin'", [$user_info['email']]);
            
            if ($admin) {
                // Update existing admin
                $db->query("UPDATE users SET last_login = NOW(), is_active = 1 WHERE id = ?", [$admin['id']]);
                $_SESSION['user_id'] = $admin['id'];
            } else {
                // Create new admin record using a prepared statement
                $db->query("
                    INSERT INTO users (username, email, role, is_active, created_at, last_login)
                    VALUES (?, ?, 'admin', 1, NOW(), NOW())
                ", [
                    explode('@', $user_info['email'])[0],
                    $user_info['email']
                ]);
                
                // Get the ID of the new user to set in the session
                $_SESSION['user_id'] = $db->lastInsertId();
            }
        } catch (Exception $e) {
            error_log("Failed to update admin record: " . $e->getMessage());
        }
    }
    
    /**
     * Generate secure state parameter
     */
    private function generateState() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Check if current session is valid admin
     */
    public static function isValidAdminSession() {
        if (!isset($_SESSION['admin_authenticated']) || !$_SESSION['admin_authenticated']) {
            return false;
        }
        
        // Check session timeout (4 hours)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 14400) {
            self::logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Logout admin user
     */
    public static function logout() {
        // Clear admin session variables
        unset($_SESSION['admin_authenticated']);
        unset($_SESSION['admin_email']);
        unset($_SESSION['admin_name']);
        unset($_SESSION['admin_picture']);
        unset($_SESSION['login_time']);
        unset($_SESSION['user_role']);
        unset($_SESSION['auth_method']);
        
        // Regenerate session ID
        session_regenerate_id(true);
    }
}