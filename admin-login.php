<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: admin-dashboard.php');
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/config/config.php';

// Check for logout message
$logoutMessage = '';
if (isset($_SESSION['logout_message'])) {
    $logoutMessage = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}

// Set page variables
$pageTitle = "Admin Login - " . APP_NAME;
$pageDescription = "Login for admin panel to manage volunteers and requests.";
$contentFile = "content/admin-login-content.php";

// Include layout
include __DIR__ . '/layout.php';
?>
