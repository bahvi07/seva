<?php
/**
 * Seva Connect - Configuration File
 * Loads environment variables from .env file
 */

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        if (strpos($line, '=') === false) {
            continue; // Skip lines without =
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remove quotes if present
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        }
        
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Load .env file
$envPath = __DIR__ . '/.env';
loadEnv($envPath);

// Database Configuration
define('DB_HOST', getenv('DB_HOST'));
define('DB_PORT', getenv('DB_PORT'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));

// Application Configuration
define('APP_NAME', getenv('APP_NAME'));
define('APP_URL', getenv('APP_URL'));
define('APP_ENV', getenv('APP_ENV'));
define('APP_DEBUG', filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN));

// Admin Configuration
define('ADMIN_USERNAME', getenv('ADMIN_USERNAME'));
define('ADMIN_PASSWORD_HASH', getenv('ADMIN_PASSWORD_HASH'));

// Email Configuration
define('MAIL_HOST', getenv('MAIL_HOST'));
define('MAIL_PORT', getenv('MAIL_PORT'));
define('MAIL_USERNAME', getenv('MAIL_USERNAME'));
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD'));
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION'));
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS'));
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME'));

// Contact Information
define('CONTACT_PHONE', getenv('CONTACT_PHONE'));
define('CONTACT_EMAIL', getenv('CONTACT_EMAIL'));
define('CONTACT_ADDRESS', getenv('CONTACT_ADDRESS'));

// Emergency Contact
define('EMERGENCY_PHONE', getenv('EMERGENCY_PHONE'));
define('EMERGENCY_EMAIL', getenv('EMERGENCY_EMAIL'));

// Feature Flags
define('ENABLE_EMAIL_NOTIFICATIONS', filter_var(getenv('ENABLE_EMAIL_NOTIFICATIONS'), FILTER_VALIDATE_BOOLEAN));
define('ENABLE_SMS_NOTIFICATIONS', filter_var(getenv('ENABLE_SMS_NOTIFICATIONS'), FILTER_VALIDATE_BOOLEAN));
define('ENABLE_VOLUNTEER_APPROVAL', filter_var(getenv('ENABLE_VOLUNTEER_APPROVAL'), FILTER_VALIDATE_BOOLEAN));
define('ENABLE_REQUEST_APPROVAL', filter_var(getenv('ENABLE_REQUEST_APPROVAL'), FILTER_VALIDATE_BOOLEAN));

// Security Configuration
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 86400 * 30)); // 30 days
define('PASSWORD_MIN_LENGTH', (int)(getenv('PASSWORD_MIN_LENGTH') ?: 8));

// Database Connection Function (MySQLi)
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to utf8mb4
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        if (APP_DEBUG) {
            die('Database connection failed: ' . $e->getMessage());
        } else {
            die('Database connection failed. Please try again later.');
        }
    }
}

// Global database connection variable
$conn = null;

// Function to get global connection
function getGlobalConnection() {
    global $conn;
    if ($conn === null) {
        $conn = getDBConnection();
    }
    return $conn;
}

// Function to close connection
function closeDBConnection($connection = null) {
    global $conn;
    if ($connection) {
        $connection->close();
    } elseif ($conn) {
        $conn->close();
        $conn = null;
    }
}

// Utility Functions
function env($key, $default = null) {
    return getenv($key) ?: $default;
}

function isProduction() {
    return APP_ENV === 'production';
}

function isDevelopment() {
    return APP_ENV === 'development';
}

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session Configuration - FIXED: Only set if no active session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Site URLs and Paths
define('SITE_ROOT', __DIR__);
define('BASE_URL', rtrim(APP_URL, '/'));
define('UPLOADS_DIR', SITE_ROOT . '/uploads');
define('LOGS_DIR', SITE_ROOT . '/logs');

// Create necessary directories
if (!file_exists(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}
if (!file_exists(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0755, true);
}

// Helper function for safe SQL queries
function sanitizeInput($input) {
    global $conn;
    if ($conn === null) {
        $conn = getDBConnection();
    }
    return $conn->real_escape_string(trim($input));
}

// Helper function for prepared statements
function prepareQuery($query) {
    $conn = getGlobalConnection();
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        if (APP_DEBUG) {
            die('Prepare failed: ' . $conn->error);
        } else {
            die('Database error occurred.');
        }
    } 
    return $stmt;
}
?>