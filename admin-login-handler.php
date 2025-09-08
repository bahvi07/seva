<?php
// Load configuration
require_once __DIR__ . '/config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Use constants defined in config.php
    $adminUsername = ADMIN_USERNAME;
    $adminPasswordHash = ADMIN_PASSWORD_HASH;
    
    // Debug: Check if credentials are loaded
    if (empty($adminUsername) || empty($adminPasswordHash)) {
        error_log('Admin credentials not properly configured');
        error_log('ADMIN_USERNAME: ' . (defined('ADMIN_USERNAME') ? 'Defined' : 'Not defined'));
        error_log('ADMIN_PASSWORD_HASH: ' . (defined('ADMIN_PASSWORD_HASH') ? 'Defined' : 'Not defined'));
    }
    
    // Verify credentials
    if ($username === $adminUsername && password_verify($password, $adminPasswordHash)) {
        // Set session variables
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_username'] = $username;
        
        // Redirect to dashboard
        header('Location: admin-dashboard.php');
        exit;
    } else {
        // Invalid credentials
        $_SESSION['login_error'] = 'Invalid username or password';
        header('Location: admin-login.php');
        exit;
    }
} else {
    // If not a POST request, redirect to login
    header('Location: admin-login.php');
    exit;
}
